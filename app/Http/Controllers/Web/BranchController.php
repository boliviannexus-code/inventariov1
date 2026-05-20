<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Models\Branch;
use App\Services\BranchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function __construct(
        private readonly BranchService $branches
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', Branch::class);

        return view('branches.index', [
            'branches' => $this->branches->paginate(),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Branch::class);

        if ($request->ajax()) {
            return view('branches.partials.create-form');
        }

        return view('branches.create');
    }

    public function store(StoreBranchRequest $request): JsonResponse|RedirectResponse
    {
        $branch = $this->branches->create($request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sucursal creada correctamente.',
                'data' => [
                    'id' => $branch->id,
                ],
            ], 201);
        }

        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal creada correctamente.');
    }

    public function show(Request $request, Branch $branch): View
    {
        $this->authorize('view', $branch);

        $branch->loadCount('warehouses');

        if ($request->ajax()) {
            return view('branches.partials.show', compact('branch'));
        }

        return view('branches.show', compact('branch'));
    }

    public function edit(Request $request, Branch $branch): View
    {
        $this->authorize('update', $branch);

        if ($request->ajax()) {
            return view('branches.partials.edit-form', compact('branch'));
        }

        return view('branches.edit', compact('branch'));
    }

    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse|RedirectResponse
    {
        $branch = $this->branches->update($branch, $request->validated());

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sucursal actualizada correctamente.',
                'data' => [
                    'id' => $branch->id,
                ],
            ]);
        }

        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal actualizada correctamente.');
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        $this->authorize('delete', $branch);

        $this->branches->delete($branch);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal eliminada correctamente.');
    }
}
