<?php

namespace App\Http\Controllers\Web;

use App\Enums\InventoryMovementType;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\MeasurementUnit;
use App\Models\PaymentMethod;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class AdminDataTableController extends Controller
{
    public function products(): JsonResponse
    {
        abort_unless(auth()->user()?->can('products.view'), 403);

        $query = Product::query()
            ->with('media')
            ->select('products.*', 'categories.name as category_name', 'measurement_units.name as measurement_unit_name', 'measurement_units.abbreviation as measurement_unit_abbreviation')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('measurement_units', 'measurement_units.id', '=', 'products.measurement_unit_id');

        return DataTables::eloquent($query)
            ->addColumn('image', fn (Product $product): string => view('products.partials.image-thumb', compact('product'))->render())
            ->editColumn('purchase_price', fn (Product $product): string => money_format_decimal($product->purchase_price))
            ->editColumn('sale_price', fn (Product $product): string => money_format_decimal($product->sale_price))
            ->editColumn('is_active', fn (Product $product): string => $this->statusBadge($product->is_active))
            ->addColumn('actions', fn (Product $product): string => view('products.partials.actions', compact('product'))->render())
            ->rawColumns(['image', 'is_active', 'actions'])
            ->toJson();
    }

    public function productPresentations(): JsonResponse
    {
        abort_unless(auth()->user()?->can('product-presentations.view'), 403);

        $query = Presentation::query()->select('presentations.*');

        return DataTables::eloquent($query)
            ->editColumn('is_active', fn (Presentation $presentation): string => $this->statusBadge($presentation->is_active))
            ->addColumn('factor', fn (Presentation $presentation): string => '1 '.$presentation->name.' = '.$presentation->units_per_package.' unidades base')
            ->addColumn('actions', fn (Presentation $productPresentation): string => view('product-presentations.partials.actions', compact('productPresentation'))->render())
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function categories(): JsonResponse
    {
        abort_unless(auth()->user()?->can('categories.view'), 403);

        return DataTables::eloquent(Category::query()->select('categories.*'))
            ->editColumn('is_active', fn (Category $category): string => $this->statusBadge($category->is_active))
            ->editColumn('created_at', fn (Category $category): string => $category->created_at?->format('Y-m-d') ?? '')
            ->addColumn('actions', fn (Category $category): string => view('categories.partials.actions', compact('category'))->render())
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function measurementUnits(): JsonResponse
    {
        abort_unless(auth()->user()?->can('measurement-units.view'), 403);

        return DataTables::eloquent(MeasurementUnit::query()->select('measurement_units.*'))
            ->editColumn('is_active', fn (MeasurementUnit $measurementUnit): string => $this->statusBadge($measurementUnit->is_active))
            ->editColumn('created_at', fn (MeasurementUnit $measurementUnit): string => $measurementUnit->created_at?->format('Y-m-d') ?? '')
            ->addColumn('actions', fn (MeasurementUnit $measurementUnit): string => view('measurement-units.partials.actions', compact('measurementUnit'))->render())
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function paymentMethods(): JsonResponse
    {
        abort_unless(auth()->user()?->can('payment-methods.view'), 403);

        return DataTables::eloquent(PaymentMethod::query()->select('payment_methods.*'))
            ->editColumn('is_active', fn (PaymentMethod $paymentMethod): string => $this->statusBadge($paymentMethod->is_active))
            ->editColumn('created_at', fn (PaymentMethod $paymentMethod): string => $paymentMethod->created_at?->format('Y-m-d') ?? '')
            ->addColumn('actions', fn (PaymentMethod $paymentMethod): string => view('payment-methods.partials.actions', compact('paymentMethod'))->render())
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function suppliers(): JsonResponse
    {
        abort_unless(auth()->user()?->can('suppliers.view'), 403);

        return DataTables::eloquent(Supplier::query()->select('suppliers.*'))
            ->editColumn('is_active', fn (Supplier $supplier): string => $this->statusBadge($supplier->is_active))
            ->editColumn('created_at', fn (Supplier $supplier): string => $supplier->created_at?->format('Y-m-d') ?? '')
            ->addColumn('actions', fn (Supplier $supplier): string => view('suppliers.partials.actions', compact('supplier'))->render())
            ->rawColumns(['is_active', 'actions'])
            ->toJson();
    }

    public function purchases(): JsonResponse
    {
        abort_unless(auth()->user()?->can('purchases.view'), 403);

        $query = Purchase::query()
            ->select('purchases.*', 'suppliers.name as supplier_name', 'warehouses.name as warehouse_name', 'users.name as user_name')
            ->leftJoin('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'purchases.warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'purchases.user_id');

        return DataTables::eloquent($query)
            ->editColumn('purchase_date', fn (Purchase $purchase): string => $purchase->purchase_date?->format('Y-m-d') ?? '')
            ->editColumn('total', fn (Purchase $purchase): string => money_format_decimal($purchase->total))
            ->addColumn('actions', fn (Purchase $purchase): string => '<a class="btn btn-outline-secondary btn-sm" href="'.route('purchases.show', $purchase).'">Ver</a>')
            ->rawColumns(['actions'])
            ->toJson();
    }

    public function sales(): JsonResponse
    {
        abort_unless(auth()->user()?->can('sales.view'), 403);

        $query = Sale::query()
            ->select('sales.*', DB::raw('COALESCE(customers.name, sales.customer_name) as customer_name'), 'branches.name as branch_name', 'warehouses.name as warehouse_name', 'users.name as user_name')
            ->leftJoin('customers', 'customers.id', '=', 'sales.customer_id')
            ->leftJoin('branches', 'branches.id', '=', 'sales.branch_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'sales.warehouse_id')
            ->leftJoin('users', 'users.id', '=', 'sales.user_id');

        return DataTables::eloquent($query)
            ->editColumn('sale_date', fn (Sale $sale): string => $sale->sale_date?->format('Y-m-d H:i') ?? '')
            ->editColumn('total', fn (Sale $sale): string => money_format_decimal($sale->total))
            ->addColumn('payments', fn (Sale $sale): string => $sale->payments()
                ->orderBy('id')
                ->get()
                ->map(fn ($payment): string => $payment->payment_method_name.' '.money_format_decimal($payment->amount))
                ->implode(' / '))
            ->toJson();
    }

    public function stock(Request $request): JsonResponse
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $query = InventoryMovement::query()
            ->select([
                'products.id as product_id',
                'products.name as product_name',
                'measurement_units.abbreviation as measurement_unit_abbreviation',
                'products.is_active as product_is_active',
                'products.minimum_stock',
                'categories.id as category_id',
                'categories.name as category_name',
                'warehouses.id as warehouse_id',
                'warehouses.name as warehouse_name',
                'branches.name as branch_name',
                DB::raw('SUM(inventory_movements.quantity) as stock'),
            ])
            ->join('products', 'products.id', '=', 'inventory_movements.product_id')
            ->leftJoin('measurement_units', 'measurement_units.id', '=', 'products.measurement_unit_id')
            ->leftJoin('categories', 'categories.id', '=', 'products.category_id')
            ->join('warehouses', 'warehouses.id', '=', 'inventory_movements.warehouse_id')
            ->leftJoin('branches', 'branches.id', '=', 'warehouses.branch_id')
            ->when($request->filled('warehouse_id'), fn ($query) => $query->where('warehouses.id', $request->integer('warehouse_id')))
            ->when($request->filled('category_id'), fn ($query) => $query->where('categories.id', $request->integer('category_id')))
            ->when($request->filled('product_id'), fn ($query) => $query->where('products.id', $request->integer('product_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('products.is_active', $request->boolean('status')))
            ->groupBy(
                'products.id',
                'products.name',
                'measurement_units.abbreviation',
                'products.is_active',
                'products.minimum_stock',
                'categories.id',
                'categories.name',
                'warehouses.id',
                'warehouses.name',
                'branches.name',
            )
            ->when($request->boolean('low_stock'), fn ($query) => $query->havingRaw('SUM(inventory_movements.quantity) <= products.minimum_stock'))
            ->havingRaw('SUM(inventory_movements.quantity) <> 0');

        return DataTables::eloquent($query)
            ->editColumn('stock', fn ($row): string => $this->stockBadge((int) $row->stock, (int) $row->minimum_stock, $row->measurement_unit_abbreviation))
            ->addColumn('presentations', fn ($row): string => $this->presentationBreakdown((int) $row->product_id, (int) $row->warehouse_id))
            ->addColumn('status', fn ($row): string => $this->statusBadge((bool) $row->product_is_active))
            ->addColumn('actions', fn ($row): string => $this->stockActions((int) $row->product_id, (int) $row->warehouse_id))
            ->rawColumns(['stock', 'presentations', 'status', 'actions'])
            ->toJson();
    }

    public function kardex(): JsonResponse
    {
        abort_unless(auth()->user()?->can('inventory.view'), 403);

        $query = InventoryMovement::query()
            ->select('inventory_movements.*', 'products.name as product_name', 'warehouses.name as warehouse_name', 'branches.name as branch_name', 'users.name as user_name')
            ->leftJoin('products', 'products.id', '=', 'inventory_movements.product_id')
            ->leftJoin('warehouses', 'warehouses.id', '=', 'inventory_movements.warehouse_id')
            ->leftJoin('branches', 'branches.id', '=', 'warehouses.branch_id')
            ->leftJoin('users', 'users.id', '=', 'inventory_movements.user_id');

        return DataTables::eloquent($query)
            ->editColumn('created_at', fn (InventoryMovement $movement): string => $movement->created_at?->format('Y-m-d H:i') ?? '')
            ->editColumn('type', fn (InventoryMovement $movement): string => $movement->type instanceof InventoryMovementType ? $movement->type->label() : (string) $movement->type)
            ->addColumn('presentation', fn (InventoryMovement $movement): string => $movement->presentation_name
                ? $movement->presentation_name.' x '.$movement->units_per_package.' ('.abs((int) $movement->package_quantity).' empaques)'
                : '-')
            ->editColumn('quantity', fn (InventoryMovement $movement): string => '<span class="badge text-bg-'.($movement->quantity > 0 ? 'success' : 'danger').'">'.$movement->quantity.'</span>')
            ->rawColumns(['quantity'])
            ->toJson();
    }

    private function statusBadge(bool $active): string
    {
        return '<span class="badge text-bg-'.($active ? 'success' : 'secondary').'">'.($active ? 'Activo' : 'Inactivo').'</span>';
    }

    private function stockBadge(int $stock, int $minimumStock, ?string $unit = null): string
    {
        $tone = $stock <= $minimumStock ? 'warning' : 'primary';
        $label = $stock.($unit ? ' '.$unit : '');

        return '<span class="badge text-bg-'.$tone.'">'.$label.'</span>';
    }

    private function presentationBreakdown(int $productId, int $warehouseId): string
    {
        $rows = InventoryMovement::query()
            ->select([
                DB::raw('COALESCE(presentation_name, "Unidad base") as presentation_name'),
                'units_per_package',
                DB::raw('SUM(package_quantity) as packages'),
                DB::raw('SUM(quantity) as units'),
            ])
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->whereNotNull('presentation_id')
            ->groupBy('presentation_name', 'units_per_package')
            ->havingRaw('SUM(package_quantity) <> 0')
            ->orderBy('presentation_name')
            ->get();

        if ($rows->isEmpty()) {
            return '<span class="text-muted">Sin presentaciones</span>';
        }

        return $rows
            ->map(fn ($row): string => '<span class="badge text-bg-light me-1 mb-1">'.(int) $row->packages.' '.$row->presentation_name.' <span class="text-muted">('.(int) $row->units.' u.)</span></span>')
            ->implode('');
    }

    private function stockActions(int $productId, int $warehouseId): string
    {
        if (! auth()->user()?->can('inventory.movements')) {
            return '';
        }

        $hasPackages = InventoryMovement::query()
            ->select('presentation_id')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('units_per_package', '>', 1)
            ->groupBy('presentation_id')
            ->havingRaw('SUM(package_quantity) > 0')
            ->exists();

        if (! $hasPackages) {
            return '<span class="text-muted">-</span>';
        }

        $url = route('inventory.defragment', [
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ]);

        return '<a class="btn btn-outline-primary btn-sm" href="'.$url.'" data-modal-url="'.$url.'" data-modal-title="Desfragmentar empaque">Desfragmentar</a>';
    }
}
