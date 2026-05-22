<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Support\CompanyContext;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        Gate::authorize('dashboard.view');

        $products = CompanyContext::scope(Product::query());
        $categories = CompanyContext::scope(Category::query());

        return view('dashboard.index', [
            'dashboardCompany' => CompanyContext::activeCompany(),
            'totalProducts' => (clone $products)->count(),
            'activeProducts' => (clone $products)->where('is_active', true)->count(),
            'totalCategories' => (clone $categories)->count(),
            'activeCategories' => (clone $categories)->where('is_active', true)->count(),
        ]);
    }
}
