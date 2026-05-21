<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\PointOfSale;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CashRegisterService
{
    public function openForUser(array $data, User $user): CashRegister
    {
        return DB::transaction(function () use ($data, $user): CashRegister {
            $this->ensureUserHasNoOpenRegister($user);

            $pointOfSale = PointOfSale::query()
                ->with(['warehouse', 'users'])
                ->where('is_active', true)
                ->lockForUpdate()
                ->findOrFail((int) $data['point_of_sale_id']);

            $this->ensureUserCanUsePointOfSale($pointOfSale, $user);
            $this->ensurePointOfSaleHasNoOpenRegister($pointOfSale);

            return CashRegister::query()
                ->create([
                    'point_of_sale_id' => $pointOfSale->id,
                    'branch_id' => $pointOfSale->branch_id,
                    'user_id' => $user->id,
                    'opening_amount' => $data['opening_amount'],
                    'opened_at' => now(),
                    'status' => 'open',
                ])
                ->load(['pointOfSale.warehouse', 'branch', 'user']);
        });
    }

    public function openRegisterFor(User $user): ?CashRegister
    {
        return CashRegister::query()
            ->with(['pointOfSale.warehouse', 'branch'])
            ->where('user_id', $user->id)
            ->where('status', 'open')
            ->latest('opened_at')
            ->first();
    }

    private function ensureUserCanUsePointOfSale(PointOfSale $pointOfSale, User $user): void
    {
        if (! $pointOfSale->users->contains($user)) {
            throw ValidationException::withMessages([
                'point_of_sale_id' => 'No tienes asignado este punto de venta.',
            ]);
        }
    }

    private function ensureUserHasNoOpenRegister(User $user): void
    {
        if (CashRegister::query()->where('user_id', $user->id)->where('status', 'open')->exists()) {
            throw ValidationException::withMessages([
                'point_of_sale_id' => 'Ya tienes una caja abierta.',
            ]);
        }
    }

    private function ensurePointOfSaleHasNoOpenRegister(PointOfSale $pointOfSale): void
    {
        if (CashRegister::query()->where('point_of_sale_id', $pointOfSale->id)->where('status', 'open')->exists()) {
            throw ValidationException::withMessages([
                'point_of_sale_id' => 'Este punto de venta ya tiene una caja abierta.',
            ]);
        }
    }
}
