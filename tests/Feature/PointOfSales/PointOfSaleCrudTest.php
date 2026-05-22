<?php

namespace Tests\Feature\PointOfSales;

use App\Models\Branch;
use App\Models\Company;
use App\Models\PointOfSale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PointOfSaleCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_point_of_sale_requires_warehouse(): void
    {
        $user = $this->userWithPermissions(['point-of-sales.create']);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);

        $this
            ->actingAs($user)
            ->post(route('point-of-sales.store'), [
                'branch_id' => $branch->id,
                'warehouse_id' => null,
                'name' => 'Caja mostrador',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('warehouse_id');
    }

    public function test_user_can_create_point_of_sale_linked_to_warehouse(): void
    {
        $user = $this->userWithPermissions(['point-of-sales.create']);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $cashier = User::factory()->create(['company_id' => $branch->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();

        $this
            ->actingAs($user)
            ->post(route('point-of-sales.store'), [
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'name' => 'POS principal',
                'users' => [$cashier->id],
                'is_active' => '1',
            ])
            ->assertRedirect(route('point-of-sales.index'));

        $this->assertDatabaseHas('point_of_sales', [
            'branch_id' => $branch->id,
            'warehouse_id' => $warehouse->id,
            'name' => 'POS principal',
            'code' => $branch->id.'-'.$warehouse->id.'-000001',
            'sequence_number' => 1,
        ]);
        $this->assertDatabaseHas('point_of_sale_user', [
            'point_of_sale_id' => PointOfSale::query()->where('warehouse_id', $warehouse->id)->value('id'),
            'user_id' => $cashier->id,
        ]);
    }

    public function test_point_of_sale_rejects_warehouse_already_linked_to_another_point_of_sale(): void
    {
        $user = $this->userWithPermissions(['point-of-sales.create']);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $warehouse = Warehouse::factory()->for($branch)->create();

        PointOfSale::factory()->for($branch)->create([
            'warehouse_id' => $warehouse->id,
            'code' => $branch->id.'-'.$warehouse->id.'-000001',
            'sequence_number' => 1,
        ]);

        $this
            ->actingAs($user)
            ->post(route('point-of-sales.store'), [
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'name' => 'POS duplicado',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('warehouse_id');
    }

    public function test_point_of_sale_rejects_warehouse_from_another_branch(): void
    {
        $user = $this->userWithPermissions(['point-of-sales.create']);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $otherBranch = Branch::factory()->create(['company_id' => $user->company_id]);
        $otherWarehouse = Warehouse::factory()->for($otherBranch)->create();

        $this
            ->actingAs($user)
            ->post(route('point-of-sales.store'), [
                'branch_id' => $branch->id,
                'warehouse_id' => $otherWarehouse->id,
                'name' => 'POS inconsistente',
                'is_active' => '1',
            ])
            ->assertSessionHasErrors('warehouse_id');
    }

    public function test_user_can_update_point_of_sale_warehouse_link(): void
    {
        $user = $this->userWithPermissions(['point-of-sales.update']);
        $branch = Branch::factory()->create(['company_id' => $user->company_id]);
        $cashier = User::factory()->create(['company_id' => $branch->company_id]);
        $oldWarehouse = Warehouse::factory()->for($branch)->create();
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create([
            'warehouse_id' => $oldWarehouse->id,
            'code' => $branch->id.'-'.$oldWarehouse->id.'-000001',
            'sequence_number' => 1,
        ]);

        $this
            ->actingAs($user)
            ->put(route('point-of-sales.update', $pointOfSale), [
                'branch_id' => $branch->id,
                'warehouse_id' => $warehouse->id,
                'name' => 'POS actualizado',
                'users' => [$cashier->id],
                'is_active' => '1',
            ])
            ->assertRedirect(route('point-of-sales.index'));

        $this->assertDatabaseHas('point_of_sales', [
            'id' => $pointOfSale->id,
            'warehouse_id' => $warehouse->id,
            'name' => 'POS actualizado',
            'code' => $branch->id.'-'.$warehouse->id.'-000001',
        ]);
        $this->assertDatabaseHas('point_of_sale_user', [
            'point_of_sale_id' => $pointOfSale->id,
            'user_id' => $cashier->id,
        ]);
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['company_id' => Company::factory()]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
        }

        $user->givePermissionTo($permissions);

        return $user;
    }
}
