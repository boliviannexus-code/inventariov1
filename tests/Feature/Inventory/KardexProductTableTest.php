<?php

namespace Tests\Feature\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Branch;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class KardexProductTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_kardex_page_has_stock_like_filters_and_product_table(): void
    {
        $user = $this->userWithInventoryAccess();

        $this
            ->actingAs($user)
            ->get(route('inventory.kardex'))
            ->assertOk()
            ->assertSee('kardex-filters')
            ->assertSee('Producto')
            ->assertSee('Acciones');
    }

    public function test_kardex_datatable_returns_products_with_view_kardex_action(): void
    {
        $user = $this->userWithInventoryAccess();
        $category = Category::factory()->create();
        $product = Product::factory()->for($category)->create(['name' => 'Arroz Especial']);
        $warehouse = Warehouse::factory()->for(Branch::factory())->create();

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 10,
            'package_quantity' => 10,
            'units_per_package' => 1,
            'reference_type' => 'test',
        ]);

        $this
            ->actingAs($user)
            ->getJson(route('datatables.kardex', [
                'draw' => 1,
                'start' => 0,
                'length' => 10,
                'category_id' => $category->id,
            ]))
            ->assertOk()
            ->assertJsonPath('data.0.product_name', 'Arroz Especial')
            ->assertJsonPath('data.0.movements_count', 1)
            ->assertJsonFragment(['warehouse_name' => $warehouse->name])
            ->assertSee('Ver kardex', false);
    }

    public function test_product_kardex_detail_shows_movements_for_selected_product_and_warehouse(): void
    {
        $user = $this->userWithInventoryAccess();
        $product = Product::factory()->create(['name' => 'Azucar Blanca']);
        $warehouse = Warehouse::factory()->for(Branch::factory())->create(['name' => 'Almacen Central']);

        InventoryMovement::query()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'user_id' => $user->id,
            'type' => InventoryMovementType::Purchase,
            'quantity' => 25,
            'package_quantity' => 25,
            'units_per_package' => 1,
            'reference_type' => 'test',
            'notes' => 'Ingreso inicial',
        ]);

        $this
            ->actingAs($user)
            ->get(route('inventory.kardex.show', [
                'product' => $product,
                'warehouse_id' => $warehouse->id,
            ]))
            ->assertOk()
            ->assertSee('Azucar Blanca')
            ->assertSee('Almacen Central')
            ->assertSee('Ingreso inicial')
            ->assertSee('25');
    }

    private function userWithInventoryAccess(): User
    {
        Permission::findOrCreate('inventory.view');

        $user = User::factory()->create();
        $user->givePermissionTo('inventory.view');

        return $user;
    }
}
