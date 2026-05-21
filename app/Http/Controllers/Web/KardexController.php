<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KardexController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        return view('inventory.kardex', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Request $request, Product $product): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $warehouseId = $request->integer('warehouse_id') ?: null;
        $warehouse = $warehouseId ? Warehouse::query()->findOrFail($warehouseId) : null;
        $movements = InventoryMovement::query()
            ->with(['warehouse.branch', 'user'])
            ->where('product_id', $product->id)
            ->when($warehouseId, fn ($query) => $query->where('warehouse_id', $warehouseId))
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $balance = 0;
        $movements = $movements->map(function (InventoryMovement $movement) use (&$balance): InventoryMovement {
            $balance += (int) $movement->quantity;
            $movement->setAttribute('running_balance', $balance);

            return $movement;
        });

        return view('inventory.partials.kardex-detail', [
            'product' => $product,
            'warehouse' => $warehouse,
            'movements' => $movements,
        ]);
    }
}
