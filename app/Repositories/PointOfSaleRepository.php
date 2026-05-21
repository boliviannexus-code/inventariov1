<?php

namespace App\Repositories;

use App\Models\PointOfSale;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PointOfSaleRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return PointOfSale::query()
            ->with(['branch', 'warehouse', 'users'])
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): PointOfSale
    {
        return PointOfSale::query()->create($data)->load(['branch', 'warehouse', 'users']);
    }

    public function update(PointOfSale $pointOfSale, array $data): PointOfSale
    {
        $pointOfSale->update($data);

        return $pointOfSale->refresh()->load(['branch', 'warehouse', 'users']);
    }

    public function delete(PointOfSale $pointOfSale): bool
    {
        return (bool) $pointOfSale->delete();
    }
}
