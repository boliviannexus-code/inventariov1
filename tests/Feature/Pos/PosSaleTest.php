<?php

namespace Tests\Feature\Pos;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\InventoryMovement;
use App\Models\PointOfSale;
use App\Models\Presentation;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PosSaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_pos_sale_with_open_cash_register(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create();
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create(['warehouse_id' => $warehouse->id]);
        $pointOfSale->users()->sync([$user->id]);
        $cashRegister = CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $product = Product::factory()->create(['sale_price' => 3]);
        $presentation = Presentation::factory()->create([
            'name' => 'Caja x 10',
            'units_per_package' => 10,
        ]);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'presentation_name' => $presentation->name,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 50,
            'package_quantity' => 5,
            'units_per_package' => 10,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 2,
                        'unit_price' => 30,
                        'discount' => 5,
                    ],
                ],
            ])
            ->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('sales', [
            'cash_register_id' => $cashRegister->id,
            'point_of_sale_id' => $pointOfSale->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'subtotal' => '60.00',
            'discount' => '5.00',
            'total' => '55.00',
            'status' => 'completed',
        ]);
        $this->assertDatabaseHas('sale_details', [
            'product_id' => $product->id,
            'presentation_id' => $presentation->id,
            'package_quantity' => 2,
            'units_per_package' => 10,
            'quantity' => 20,
            'subtotal' => '55.00',
        ]);
        $this->assertDatabaseHas('inventory_movements', [
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'type' => InventoryMovementType::Sale->value,
            'quantity' => -20,
            'package_quantity' => -2,
            'reference_type' => 'sale',
        ]);
    }

    public function test_pos_sale_requires_open_cash_register(): void
    {
        $user = $this->userWithPosAccess();
        $product = Product::factory()->create();
        $presentation = Presentation::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 1,
                        'unit_price' => 10,
                    ],
                ],
            ])
            ->assertStatus(422);
    }

    public function test_pos_sale_rejects_insufficient_stock(): void
    {
        $user = $this->userWithPosAccess();
        $pointOfSale = PointOfSale::factory()->create();
        $pointOfSale->users()->sync([$user->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $pointOfSale->branch_id,
            'user_id' => $user->id,
            'status' => 'open',
        ]);
        $product = Product::factory()->create();
        $presentation = Presentation::factory()->create(['units_per_package' => 10]);

        $this
            ->actingAs($user)
            ->post(route('pos.sales.store'), [
                'items' => [
                    [
                        'product_id' => $product->id,
                        'presentation_id' => $presentation->id,
                        'package_quantity' => 1,
                        'unit_price' => 10,
                    ],
                ],
            ])
            ->assertSessionHasErrors('items');
    }

    private function userWithPosAccess(): User
    {
        Permission::findOrCreate('pos.access');

        $user = User::factory()->create();
        $user->givePermissionTo('pos.access');

        return $user;
    }
}
