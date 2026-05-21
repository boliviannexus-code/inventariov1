<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\PointOfSale;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PointOfSale>
 */
class PointOfSaleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'warehouse_id' => fn (array $attributes): int => Warehouse::factory()
                ->for(Branch::query()->find($attributes['branch_id']))
                ->create()
                ->id,
            'name' => fake()->unique()->words(2, true),
            'code' => fake()->unique()->bothify('PV-###'),
            'sequence_number' => 1,
            'is_active' => true,
        ];
    }

    public function forWarehouse(int $warehouseId): static
    {
        return $this->state(fn (): array => ['warehouse_id' => $warehouseId]);
    }
}
