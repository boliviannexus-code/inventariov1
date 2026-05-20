<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $products,
        private readonly CategoryService $categories
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Product::class);

        return view('products.index', [
            'products' => $this->products->paginate(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Product::class);

        if ($request->ajax()) {
            return view('products.partials.create-form', [
                'categories' => $this->categories->active(),
            ]);
        }

        return view('products.create', [
            'categories' => $this->categories->active(),
        ]);
    }

    public function store(StoreProductRequest $request): JsonResponse|RedirectResponse
    {
        $product = $this->products->create($request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Producto creado correctamente.',
                'data' => [
                    'id' => $product->id,
                ],
            ], 201);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto creado correctamente.');
    }

    public function show(Request $request, Product $product): View
    {
        $this->authorize('view', $product);

        if ($request->ajax()) {
            return view('products.partials.show', [
                'product' => $product->load('category'),
            ]);
        }

        return view('products.show', [
            'product' => $product->load('category'),
        ]);
    }

    public function edit(Request $request, Product $product): View
    {
        $this->authorize('update', $product);

        if ($request->ajax()) {
            return view('products.partials.edit-form', [
                'product' => $product,
                'categories' => $this->categories->active(),
            ]);
        }

        return view('products.edit', [
            'product' => $product,
            'categories' => $this->categories->active(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): JsonResponse|RedirectResponse
    {
        $product = $this->products->update($product, $request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Producto actualizado correctamente.',
                'data' => [
                    'id' => $product->id,
                ],
            ]);
        }

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->authorize('delete', $product);

        $this->products->delete($product);

        return redirect()
            ->route('products.index')
            ->with('success', 'Producto eliminado correctamente.');
    }
}
