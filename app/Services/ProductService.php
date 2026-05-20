<?php

namespace App\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function __construct(
        private readonly ProductRepository $products
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->products->paginate($perPage);
    }

    public function active(): Collection
    {
        return $this->products->active();
    }

    public function create(array $data): Product
    {
        $product = $this->products->create($this->normalize($data, true));

        Log::info('Product created', ['product_id' => $product->id]);

        return $product;
    }

    public function update(Product $product, array $data): Product
    {
        $product = $this->products->update($product, $this->normalize($data));

        Log::info('Product updated', ['product_id' => $product->id]);

        return $product;
    }

    public function delete(Product $product): bool
    {
        $deleted = $this->products->delete($product);

        Log::warning('Product deleted', ['product_id' => $product->id]);

        return $deleted;
    }

    private function normalize(array $data, ?bool $defaultActive = null): array
    {
        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        } elseif ($defaultActive !== null) {
            $data['is_active'] = $defaultActive;
        }

        return $data;
    }
}
