<?php

namespace App\Repositories;

use App\Models\Branch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BranchRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Branch::query()
            ->withCount('warehouses')
            ->latest()
            ->paginate($perPage);
    }

    public function active(): Collection
    {
        return Branch::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function create(array $data): Branch
    {
        return Branch::create($data);
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch->update($data);

        return $branch->refresh()->loadCount('warehouses');
    }

    public function delete(Branch $branch): bool
    {
        return (bool) $branch->delete();
    }
}
