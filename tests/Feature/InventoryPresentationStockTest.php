<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\MeasurementUnit;
use App\Models\Product;
use App\Models\Presentation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryPresentationStockTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_tracks_total_stock_and_packages_by_presentation(): void
    {
        $user = User::factory()->create();
        $warehouse = Warehouse::factory()->for(Branch::factory())->create();
        $unit = MeasurementUnit::query()->where('abbreviation', 'un')->firstOrFail();
        $product = Product::factory()
            ->for(Category::factory())
            ->for($unit, 'measurementUnit')
            ->create();

        $box10 = Presentation::factory()->create([
            'name' => 'Caja x 10',
            'units_per_package' => 10,
        ]);
        $box20 = Presentation::factory()->create([
            'name' => 'Caja x 20',
            'units_per_package' => 20,
        ]);

        app(InventoryService::class)->register([
            'operation' => 'in',
            'warehouse_id' => $warehouse->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'presentation_id' => $box10->id,
                    'package_quantity' => 5,
                ],
                [
                    'product_id' => $product->id,
                    'presentation_id' => $box20->id,
                    'package_quantity' => 3,
                ],
            ],
        ], $user->id);

        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'presentation_id' => $box10->id,
            'presentation_name' => 'Caja x 10',
            'package_quantity' => 5,
            'units_per_package' => 10,
            'quantity' => 50,
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'presentation_id' => $box20->id,
            'presentation_name' => 'Caja x 20',
            'package_quantity' => 3,
            'units_per_package' => 20,
            'quantity' => 60,
        ]);

        $this->assertSame(110, (int) $product->inventoryMovements()->sum('quantity'));
    }
}
