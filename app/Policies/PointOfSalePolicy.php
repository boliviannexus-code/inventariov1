<?php

namespace App\Policies;

use App\Models\PointOfSale;
use App\Models\User;

class PointOfSalePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('point-of-sales.view');
    }

    public function view(User $user, PointOfSale $pointOfSale): bool
    {
        return $user->can('point-of-sales.view');
    }

    public function create(User $user): bool
    {
        return $user->can('point-of-sales.create');
    }

    public function update(User $user, PointOfSale $pointOfSale): bool
    {
        return $user->can('point-of-sales.update');
    }

    public function delete(User $user, PointOfSale $pointOfSale): bool
    {
        return $user->can('point-of-sales.delete');
    }
}
