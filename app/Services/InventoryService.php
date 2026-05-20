<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\Presentation;
use App\Repositories\InventoryMovementRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class InventoryService
{
    public function __construct(
        private readonly InventoryMovementRepository $movements
    ) {}

    public function stockSummary(): Collection
    {
        return $this->movements->stockSummary();
    }

    public function latestMovements(int $perPage = 20): LengthAwarePaginator
    {
        return $this->movements->latest($perPage);
    }

    public function register(array $data, int $userId): void
    {
        $items = $this->normalizeItems($data['items'] ?? []);
        $operation = $data['operation'];
        $notes = $data['notes'] ?? null;

        DB::transaction(function () use ($data, $items, $operation, $notes, $userId): void {
            match ($operation) {
                'in' => $this->registerAdjustmentIn((int) $data['warehouse_id'], $items, $notes, $userId),
                'out' => $this->registerAdjustmentOut((int) $data['warehouse_id'], $items, $notes, $userId),
                'transfer' => $this->registerTransfer((int) $data['source_warehouse_id'], (int) $data['target_warehouse_id'], $items, $notes, $userId),
            };
        });

        Log::info('Inventory movement registered', [
            'operation' => $operation,
            'items' => count($items),
            'user_id' => $userId,
        ]);
    }

    public function stockMap(): array
    {
        $map = [];

        $this->stockSummary()->each(function ($row) use (&$map): void {
            $map[$row->warehouse_id][$row->product_id] = (int) $row->stock;
        });

        return $map;
    }

    private function registerAdjustmentIn(int $warehouseId, array $items, ?string $notes, int $userId): void
    {
        foreach ($items as $item) {
            $this->movements->create([
                'product_id' => $item['product_id'],
                'presentation_id' => $item['presentation_id'],
                'presentation_name' => $item['presentation_name'],
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'type' => InventoryMovementType::AdjustmentIn,
                'quantity' => $item['quantity'],
                'package_quantity' => $item['package_quantity'],
                'units_per_package' => $item['units_per_package'],
                'reference_type' => 'manual_adjustment',
                'notes' => $notes,
            ]);
        }
    }

    private function registerAdjustmentOut(int $warehouseId, array $items, ?string $notes, int $userId): void
    {
        foreach ($items as $item) {
            $this->ensureStock($item['product_id'], $warehouseId, $item['quantity']);
            $this->ensurePresentationStock($item, $warehouseId);

            $this->movements->create([
                'product_id' => $item['product_id'],
                'presentation_id' => $item['presentation_id'],
                'presentation_name' => $item['presentation_name'],
                'warehouse_id' => $warehouseId,
                'user_id' => $userId,
                'type' => InventoryMovementType::AdjustmentOut,
                'quantity' => $item['quantity'] * -1,
                'package_quantity' => $item['package_quantity'] !== null ? $item['package_quantity'] * -1 : null,
                'units_per_package' => $item['units_per_package'],
                'reference_type' => 'manual_adjustment',
                'notes' => $notes,
            ]);
        }
    }

    private function registerTransfer(int $sourceWarehouseId, int $targetWarehouseId, array $items, ?string $notes, int $userId): void
    {
        if ($sourceWarehouseId === $targetWarehouseId) {
            throw ValidationException::withMessages([
                'target_warehouse_id' => 'El almacen origen y destino deben ser diferentes.',
            ]);
        }

        $referenceId = now()->format('ymdHis').random_int(100, 999);

        foreach ($items as $item) {
            $this->ensureStock($item['product_id'], $sourceWarehouseId, $item['quantity']);
            $this->ensurePresentationStock($item, $sourceWarehouseId);

            $this->movements->create([
                'product_id' => $item['product_id'],
                'presentation_id' => $item['presentation_id'],
                'presentation_name' => $item['presentation_name'],
                'warehouse_id' => $sourceWarehouseId,
                'user_id' => $userId,
                'type' => InventoryMovementType::TransferOut,
                'quantity' => $item['quantity'] * -1,
                'package_quantity' => $item['package_quantity'] !== null ? $item['package_quantity'] * -1 : null,
                'units_per_package' => $item['units_per_package'],
                'reference_id' => $referenceId,
                'reference_type' => 'warehouse_transfer',
                'notes' => $notes,
            ]);

            $this->movements->create([
                'product_id' => $item['product_id'],
                'presentation_id' => $item['presentation_id'],
                'presentation_name' => $item['presentation_name'],
                'warehouse_id' => $targetWarehouseId,
                'user_id' => $userId,
                'type' => InventoryMovementType::TransferIn,
                'quantity' => $item['quantity'],
                'package_quantity' => $item['package_quantity'],
                'units_per_package' => $item['units_per_package'],
                'reference_id' => $referenceId,
                'reference_type' => 'warehouse_transfer',
                'notes' => $notes,
            ]);
        }
    }

    private function ensureStock(int $productId, int $warehouseId, int $quantity): void
    {
        $currentStock = $this->movements->productStock($productId, $warehouseId);

        if ($currentStock < $quantity) {
            throw ValidationException::withMessages([
                'items' => 'Stock insuficiente para uno o mas productos seleccionados.',
            ]);
        }
    }

    private function ensurePresentationStock(array $item, int $warehouseId): void
    {
        if ($item['presentation_id'] === null || $item['package_quantity'] === null) {
            return;
        }

        $currentPackages = $this->movements->productPresentationPackages(
            $item['product_id'],
            $warehouseId,
            $item['presentation_id']
        );

        if ($currentPackages < $item['package_quantity']) {
            throw ValidationException::withMessages([
                'items' => 'Stock insuficiente para la presentacion seleccionada.',
            ]);
        }
    }

    private function normalizeItems(array $items): array
    {
        $normalized = [];
        $presentationIds = collect($items)
            ->pluck('presentation_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();
        $presentations = Presentation::query()
            ->whereIn('id', $presentationIds)
            ->get()
            ->keyBy('id');

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $presentationId = isset($item['presentation_id']) ? (int) $item['presentation_id'] : (int) ($item['product_presentation_id'] ?? 0);
            $presentation = $presentationId > 0 ? $presentations->get($presentationId) : null;
            $packageQuantity = $presentation ? (int) ($item['package_quantity'] ?? $item['quantity'] ?? 0) : null;
            $unitsPerPackage = $presentation?->units_per_package ?? 1;
            $quantity = $presentation ? $packageQuantity * $unitsPerPackage : (int) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $key = $productId.'-'.($presentation?->id ?? 'base').'-'.$unitsPerPackage;
            $normalized[$key] = [
                'product_id' => $productId,
                'presentation_id' => $presentation?->id,
                'presentation_name' => $presentation?->name,
                'package_quantity' => $presentation ? ($normalized[$key]['package_quantity'] ?? 0) + $packageQuantity : null,
                'units_per_package' => $unitsPerPackage,
                'quantity' => ($normalized[$key]['quantity'] ?? 0) + $quantity,
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages([
                'items' => 'Agrega al menos un producto al movimiento.',
            ]);
        }

        return array_values($normalized);
    }
}
