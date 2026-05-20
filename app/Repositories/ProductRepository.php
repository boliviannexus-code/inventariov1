<?php

namespace App\Repositories;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProductRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return Product::query()
            ->with('category')
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): Product
    {
        return Product::create($data)->load('category');
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);

        return $product->refresh()->load('category');
    }

    public function delete(Product $product): bool
    {
        return (bool) $product->delete();
    }
}
