<?php

namespace App\Services;

use App\Enums\InventoryMovementType;
use App\Models\CashRegister;
use App\Models\InventoryMovement;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function createFromPos(array $data, CashRegister $cashRegister, User $user): Sale
    {
        return DB::transaction(function () use ($data, $cashRegister, $user): Sale {
            $cashRegister = CashRegister::query()
                ->with('pointOfSale.warehouse')
                ->whereKey($cashRegister->id)
                ->where('status', 'open')
                ->lockForUpdate()
                ->firstOrFail();

            $pointOfSale = $cashRegister->pointOfSale;
            $warehouseId = (int) $pointOfSale->warehouse_id;
            $items = $this->normalizeItems($data['items']);

            foreach ($items as $item) {
                $this->ensureStock($item, $warehouseId);
            }

            $subtotal = collect($items)->sum('line_subtotal');
            $discount = collect($items)->sum('discount');
            $total = collect($items)->sum('subtotal');
            $sequence = $this->nextSequence((int) $pointOfSale->id);

            $sale = Sale::query()->create([
                'customer_id' => $data['customer_id'] ?? null,
                'branch_id' => $cashRegister->branch_id,
                'warehouse_id' => $warehouseId,
                'user_id' => $user->id,
                'cash_register_id' => $cashRegister->id,
                'point_of_sale_id' => $pointOfSale->id,
                'receipt_number' => $this->receiptFor($pointOfSale->code, $sequence),
                'sequence_number' => $sequence,
                'sale_date' => now(),
                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => 0,
                'total' => $total,
                'status' => 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($items as $item) {
                $sale->details()->create([
                    'product_id' => $item['product_id'],
                    'presentation_id' => $item['presentation_id'],
                    'presentation_name' => $item['presentation_name'],
                    'package_quantity' => $item['package_quantity'],
                    'units_per_package' => $item['units_per_package'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'],
                    'subtotal' => $item['subtotal'],
                ]);

                InventoryMovement::query()->create([
                    'product_id' => $item['product_id'],
                    'presentation_id' => $item['presentation_id'],
                    'presentation_name' => $item['presentation_name'],
                    'warehouse_id' => $warehouseId,
                    'user_id' => $user->id,
                    'type' => InventoryMovementType::Sale,
                    'quantity' => $item['quantity'] * -1,
                    'package_quantity' => $item['package_quantity'] * -1,
                    'units_per_package' => $item['units_per_package'],
                    'reference_id' => $sale->id,
                    'reference_type' => 'sale',
                    'notes' => $data['notes'] ?? null,
                ]);
            }

            return $sale->load(['details.product', 'details.presentation', 'pointOfSale', 'cashRegister']);
        });
    }

    private function normalizeItems(array $items): array
    {
        $productIds = collect($items)->pluck('product_id')->filter()->map(fn ($id): int => (int) $id)->unique();
        $presentationIds = collect($items)->pluck('presentation_id')->filter()->map(fn ($id): int => (int) $id)->unique();
        $products = Product::query()->whereIn('id', $productIds)->where('is_active', true)->get()->keyBy('id');
        $presentations = Presentation::query()->whereIn('id', $presentationIds)->where('is_active', true)->get()->keyBy('id');
        $normalized = [];

        foreach ($items as $index => $item) {
            $product = $products->get((int) ($item['product_id'] ?? 0));
            $presentation = $presentations->get((int) ($item['presentation_id'] ?? 0));

            if (! $product || ! $presentation) {
                throw ValidationException::withMessages([
                    'items.'.($index).'.product_id' => 'Selecciona producto y presentacion activos.',
                ]);
            }

            $packages = (int) $item['package_quantity'];
            $units = (int) $presentation->units_per_package;
            $unitPrice = round((float) $item['unit_price'], 2);
            $discount = round((float) ($item['discount'] ?? 0), 2);
            $lineSubtotal = round($packages * $unitPrice, 2);
            $subtotal = max(0, round($lineSubtotal - $discount, 2));

            $key = $product->id.'-'.$presentation->id.'-'.$unitPrice.'-'.$discount;
            $normalized[$key] = [
                'product_id' => $product->id,
                'presentation_id' => $presentation->id,
                'presentation_name' => $presentation->name,
                'package_quantity' => ($normalized[$key]['package_quantity'] ?? 0) + $packages,
                'units_per_package' => $units,
                'quantity' => ($normalized[$key]['quantity'] ?? 0) + ($packages * $units),
                'unit_price' => $unitPrice,
                'discount' => ($normalized[$key]['discount'] ?? 0) + $discount,
                'line_subtotal' => ($normalized[$key]['line_subtotal'] ?? 0) + $lineSubtotal,
                'subtotal' => ($normalized[$key]['subtotal'] ?? 0) + $subtotal,
            ];
        }

        if ($normalized === []) {
            throw ValidationException::withMessages(['items' => 'Agrega al menos un producto.']);
        }

        return array_values($normalized);
    }

    private function ensureStock(array $item, int $warehouseId): void
    {
        $stock = (int) InventoryMovement::query()
            ->where('product_id', $item['product_id'])
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity');

        $packages = (int) InventoryMovement::query()
            ->where('product_id', $item['product_id'])
            ->where('warehouse_id', $warehouseId)
            ->where('presentation_id', $item['presentation_id'])
            ->sum('package_quantity');

        if ($stock < $item['quantity'] || $packages < $item['package_quantity']) {
            throw ValidationException::withMessages([
                'items' => 'Stock insuficiente para '.$item['presentation_name'].'.',
            ]);
        }
    }

    private function nextSequence(int $pointOfSaleId): int
    {
        return ((int) Sale::query()
            ->where('point_of_sale_id', $pointOfSaleId)
            ->lockForUpdate()
            ->max('sequence_number')) + 1;
    }

    private function receiptFor(string $pointCode, int $sequence): string
    {
        return $pointCode.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
