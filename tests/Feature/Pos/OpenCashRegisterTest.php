<?php

namespace Tests\Feature\Pos;

use App\Models\Branch;
use App\Models\CashRegister;
use App\Models\PointOfSale;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class OpenCashRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_cash_register_for_assigned_point_of_sale(): void
    {
        $user = $this->userWithPosAccess();
        $branch = Branch::factory()->create();
        $warehouse = Warehouse::factory()->for($branch)->create();
        $pointOfSale = PointOfSale::factory()->for($branch)->create([
            'warehouse_id' => $warehouse->id,
            'code' => $branch->id.'-'.$warehouse->id.'-000001',
        ]);
        $pointOfSale->users()->sync([$user->id]);

        $this
            ->actingAs($user)
            ->post(route('pos.open'), [
                'point_of_sale_id' => $pointOfSale->id,
                'opening_amount' => 150.25,
            ])
            ->assertRedirect(route('pos.index'));

        $this->assertDatabaseHas('cash_registers', [
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $branch->id,
            'user_id' => $user->id,
            'opening_amount' => '150.25',
            'status' => 'open',
        ]);
    }

    public function test_user_cannot_open_cash_register_for_unassigned_point_of_sale(): void
    {
        $user = $this->userWithPosAccess();
        $pointOfSale = PointOfSale::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('pos.open'), [
                'point_of_sale_id' => $pointOfSale->id,
                'opening_amount' => 10,
            ])
            ->assertSessionHasErrors('point_of_sale_id');
    }

    public function test_user_cannot_open_two_cash_registers(): void
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

        $otherPointOfSale = PointOfSale::factory()->create();
        $otherPointOfSale->users()->sync([$user->id]);

        $this
            ->actingAs($user)
            ->post(route('pos.open'), [
                'point_of_sale_id' => $otherPointOfSale->id,
                'opening_amount' => 10,
            ])
            ->assertSessionHasErrors('point_of_sale_id');
    }

    public function test_point_of_sale_cannot_have_two_open_cash_registers(): void
    {
        $firstUser = $this->userWithPosAccess();
        $secondUser = $this->userWithPosAccess();
        $pointOfSale = PointOfSale::factory()->create();
        $pointOfSale->users()->sync([$firstUser->id, $secondUser->id]);
        CashRegister::factory()->create([
            'point_of_sale_id' => $pointOfSale->id,
            'branch_id' => $pointOfSale->branch_id,
            'user_id' => $firstUser->id,
            'status' => 'open',
        ]);

        $this
            ->actingAs($secondUser)
            ->post(route('pos.open'), [
                'point_of_sale_id' => $pointOfSale->id,
                'opening_amount' => 10,
            ])
            ->assertSessionHasErrors('point_of_sale_id');
    }

    public function test_pos_screen_lists_only_assigned_points_for_regular_user(): void
    {
        $user = $this->userWithPosAccess();
        $assigned = PointOfSale::factory()->create(['name' => 'POS asignado']);
        $unassigned = PointOfSale::factory()->create(['name' => 'POS no asignado']);
        $assigned->users()->sync([$user->id]);

        $this
            ->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('POS asignado')
            ->assertDontSee('POS no asignado');
    }

    public function test_admin_also_only_sees_assigned_points_of_sale(): void
    {
        $user = $this->userWithPosAccess();
        $user->assignRole('admin');
        $assigned = PointOfSale::factory()->create(['name' => 'POS admin asignado']);
        $unassigned = PointOfSale::factory()->create(['name' => 'POS admin no asignado']);
        $assigned->users()->sync([$user->id]);

        $this
            ->actingAs($user)
            ->get(route('pos.index'))
            ->assertOk()
            ->assertSee('POS admin asignado')
            ->assertDontSee('POS admin no asignado');
    }

    public function test_admin_cannot_open_cash_register_for_unassigned_point_of_sale(): void
    {
        $user = $this->userWithPosAccess();
        $user->assignRole('admin');
        $pointOfSale = PointOfSale::factory()->create();

        $this
            ->actingAs($user)
            ->post(route('pos.open'), [
                'point_of_sale_id' => $pointOfSale->id,
                'opening_amount' => 10,
            ])
            ->assertSessionHasErrors('point_of_sale_id');
    }

    private function userWithPosAccess(): User
    {
        Permission::findOrCreate('pos.access');
        Role::findOrCreate('admin');

        $user = User::factory()->create();
        $user->givePermissionTo('pos.access');

        return $user;
    }
}
