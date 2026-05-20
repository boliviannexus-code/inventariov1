<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSupplierRequest;
use App\Http\Requests\UpdateSupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()?->can('suppliers.view'), 403);

        return view('suppliers.index');
    }

    public function create(Request $request): View
    {
        abort_unless(auth()->user()?->can('suppliers.create'), 403);

        if ($request->ajax()) {
            return view('suppliers.partials.create-form');
        }

        return view('suppliers.create');
    }

    public function store(StoreSupplierRequest $request): JsonResponse|RedirectResponse
    {
        $supplier = Supplier::query()->create($request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Proveedor creado correctamente.',
                'data' => [
                    'id' => $supplier->id,
                ],
            ], 201);
        }

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor creado correctamente.');
    }

    public function show(Request $request, Supplier $supplier): View
    {
        abort_unless(auth()->user()?->can('suppliers.view'), 403);

        if ($request->ajax()) {
            return view('suppliers.partials.show', compact('supplier'));
        }

        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Request $request, Supplier $supplier): View
    {
        abort_unless(auth()->user()?->can('suppliers.update'), 403);

        if ($request->ajax()) {
            return view('suppliers.partials.edit-form', compact('supplier'));
        }

        return view('suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): JsonResponse|RedirectResponse
    {
        $supplier->update($request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Proveedor actualizado correctamente.',
                'data' => [
                    'id' => $supplier->id,
                ],
            ]);
        }

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor actualizado correctamente.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        abort_unless(auth()->user()?->can('suppliers.delete'), 403);

        $supplier->delete();

        return redirect()
            ->route('suppliers.index')
            ->with('success', 'Proveedor eliminado correctamente.');
    }
}
