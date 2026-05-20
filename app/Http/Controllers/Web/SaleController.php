<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('sales.view'), 403);

        return view('sales.index');
    }
}
