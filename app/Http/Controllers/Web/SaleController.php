<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CashRegister;
use App\Services\CashRegisterService;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private readonly CashRegisterService $cashRegisters
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('sales.view'), 403);

        $cashRegisters = CashRegister::query()
            ->with(['pointOfSale', 'branch', 'user'])
            ->withCount('sales')
            ->withSum('sales as sales_total', 'total')
            ->withSum('expenses as expenses_total', 'amount')
            ->latest('opened_at')
            ->paginate(15);

        return view('sales.index', compact('cashRegisters'));
    }

    public function show(CashRegister $cashRegister): View
    {
        abort_unless(auth()->user()?->can('sales.view'), 403);

        $cashRegister->load(['pointOfSale', 'branch', 'user']);

        return view('sales.show', [
            'cashRegister' => $cashRegister,
            'cashSummary' => $this->cashRegisters->cashSummary($cashRegister),
        ]);
    }
}
