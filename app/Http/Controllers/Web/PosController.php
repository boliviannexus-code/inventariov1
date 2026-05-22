<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CloseCashRegisterRequest;
use App\Http\Requests\OpenCashRegisterRequest;
use App\Http\Requests\StoreCashRegisterExpenseRequest;
use App\Http\Requests\StorePosSaleRequest;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\PaymentMethod;
use App\Models\PointOfSale;
use App\Models\Product;
use App\Services\CashRegisterService;
use App\Services\SaleService;
use App\Support\CompanyContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly CashRegisterService $cashRegisters,
        private readonly SaleService $sales
    ) {}

    public function index(Request $request): View
    {
        abort_unless(($request->user()?->can('pos.access') ?? false) && CompanyContext::canOperate($request->user()), 403);

        $openRegister = $this->cashRegisters->openRegisterFor($request->user());

        return view('pos.index', [
            'openRegister' => $openRegister,
            'pointOfSales' => $this->pointOfSalesFor($request),
            'customers' => Customer::query()
                ->select(['id', 'name', 'document_number'])
                ->withCount('sales')
                ->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->where('is_active', true)
                ->whereNotNull('document_number')
                ->orderBy('name')
                ->get(),
            'paymentMethods' => PaymentMethod::query()
                ->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
            'products' => Product::query()
                ->with('measurementUnit')
                ->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId))
                ->where('is_active', true)
                ->orderBy('name')
                ->get(),
            'stockAvailability' => $openRegister ? $this->stockAvailability((int) $openRegister->pointOfSale->warehouse_id) : [],
            'cashSummary' => $openRegister ? $this->cashRegisters->cashSummary($openRegister) : null,
        ]);
    }

    public function open(OpenCashRegisterRequest $request): RedirectResponse
    {
        $cashRegister = $this->cashRegisters->openForUser($request->validated(), $request->user());

        return redirect()
            ->route('pos.index')
            ->with('success', 'Caja abierta correctamente en '.$cashRegister->pointOfSale?->name.'.');
    }

    public function sale(StorePosSaleRequest $request): RedirectResponse
    {
        $openRegister = $this->cashRegisters->openRegisterFor($request->user());

        abort_if(! $openRegister, 422, 'Debes abrir caja antes de vender.');

        $sale = $this->sales->createFromPos($request->validated(), $openRegister, $request->user());

        return redirect()
            ->route('pos.index')
            ->with('success', 'Venta registrada correctamente. Comprobante: '.$sale->receipt_number);
    }

    public function expense(StoreCashRegisterExpenseRequest $request): RedirectResponse
    {
        $expense = $this->cashRegisters->registerExpense($request->validated(), $request->user());

        return redirect()
            ->route('pos.index')
            ->with('success', 'Egreso registrado correctamente por '.money_format_decimal($expense->amount).'.');
    }

    public function close(CloseCashRegisterRequest $request): RedirectResponse
    {
        $cashRegister = $this->cashRegisters->closeForUser($request->validated(), $request->user());

        return redirect()
            ->route('pos.index')
            ->with('success', 'Caja cerrada correctamente en '.$cashRegister->pointOfSale?->name.'.');
    }

    private function pointOfSalesFor(Request $request)
    {
        $query = PointOfSale::query()
            ->with(['branch', 'warehouse'])
            ->where('is_active', true)
            ->when(CompanyContext::id($request->user()), fn ($query, $companyId) => $query->where('company_id', $companyId))
            ->whereHas('users', fn ($users) => $users->whereKey($request->user()->id))
            ->orderBy('name');

        return $query->get();
    }

    private function stockAvailability(int $warehouseId): array
    {
        $stock = InventoryMovement::query()
            ->select('product_id', DB::raw('SUM(quantity) as stock'))
            ->where('warehouse_id', $warehouseId)
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity) > 0')
            ->pluck('stock', 'product_id');

        $presentations = InventoryMovement::query()
            ->select([
                'inventory_movements.product_id',
                'inventory_movements.presentation_id',
                DB::raw('COALESCE(inventory_movements.presentation_name, presentations.name) as name'),
                'inventory_movements.units_per_package',
                DB::raw('SUM(inventory_movements.package_quantity) as packages'),
                DB::raw('SUM(inventory_movements.quantity) as units'),
            ])
            ->leftJoin('presentations', 'presentations.id', '=', 'inventory_movements.presentation_id')
            ->where('inventory_movements.warehouse_id', $warehouseId)
            ->whereNotNull('inventory_movements.presentation_id')
            ->groupBy('inventory_movements.product_id', 'inventory_movements.presentation_id', 'inventory_movements.presentation_name', 'presentations.name', 'inventory_movements.units_per_package')
            ->havingRaw('SUM(inventory_movements.package_quantity) > 0')
            ->orderBy('inventory_movements.units_per_package')
            ->get()
            ->groupBy('product_id')
            ->map(fn ($rows) => $rows->map(fn ($row): array => [
                'id' => (int) $row->presentation_id,
                'name' => (string) $row->name,
                'units_per_package' => (int) $row->units_per_package,
                'packages' => (int) $row->packages,
                'units' => (int) $row->units,
            ])->values()->all());

        return $stock
            ->mapWithKeys(fn ($value, $productId): array => [
                (int) $productId => [
                    'stock' => (int) $value,
                    'presentations' => $presentations->get((int) $productId, []),
                ],
            ])
            ->all();
    }
}
