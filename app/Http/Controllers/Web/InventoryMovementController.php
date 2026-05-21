<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStockDefragmentationRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InventoryMovementController extends Controller
{
    public function __construct(
        private readonly InventoryService $inventory
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        return view('inventory.index', [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'products' => Product::query()->orderBy('name')->get(['id', 'name']),
            'stockSummary' => $this->inventory->stockSummary(),
            'warehouses' => Warehouse::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function defragment(Request $request): View
    {
        abort_unless(auth()->user()?->can('inventory.movements'), 403);

        $product = Product::query()->findOrFail($request->integer('product_id'));
        $warehouse = Warehouse::query()->findOrFail($request->integer('warehouse_id'));
        $presentations = $this->inventory->defragmentablePresentations($product->id, $warehouse->id);

        return view('inventory.partials.defragment-form', compact('product', 'warehouse', 'presentations'));
    }

    public function storeDefragmentation(StoreStockDefragmentationRequest $request): JsonResponse|RedirectResponse
    {
        $this->inventory->defragmentPackage($request->validated(), (int) $request->user()->id);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Empaque desfragmentado correctamente.',
            ]);
        }

        return redirect()->route('inventory.index')->with('success', 'Empaque desfragmentado correctamente.');
    }
}
