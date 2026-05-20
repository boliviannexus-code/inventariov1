<?php

namespace App\Repositories;

use App\Models\InventoryMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class InventoryMovementRepository
{
    public function latest(int $perPage = 20): LengthAwarePaginator
    {
        return InventoryMovement::query()
            ->with(['product', 'presentation', 'warehouse.branch', 'user'])
            ->latest()
            ->paginate($perPage);
    }

    public function stockSummary(): Collection
    {
        return InventoryMovement::query()
            ->select('product_id', 'warehouse_id', DB::raw('SUM(quantity) as stock'))
            ->with(['product.category', 'warehouse.branch'])
            ->groupBy('product_id', 'warehouse_id')
            ->havingRaw('SUM(quantity) <> 0')
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get();
    }

    public function stockByWarehouse(int $warehouseId): Collection
    {
        return InventoryMovement::query()
            ->select('product_id', DB::raw('SUM(quantity) as stock'))
            ->where('warehouse_id', $warehouseId)
            ->groupBy('product_id')
            ->pluck('stock', 'product_id');
    }

    public function productStock(int $productId, int $warehouseId): int
    {
        return (int) InventoryMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity');
    }

    public function productPresentationPackages(int $productId, int $warehouseId, int $presentationId): int
    {
        return (int) InventoryMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('presentation_id', $presentationId)
            ->sum('package_quantity');
    }

    public function create(array $data): InventoryMovement
    {
        return InventoryMovement::create($data);
    }
}
