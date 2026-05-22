<?php

namespace App\Services;

use App\Models\PointOfSale;
use App\Models\User;
use App\Models\Warehouse;
use App\Repositories\PointOfSaleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PointOfSaleService
{
    public function __construct(
        private readonly PointOfSaleRepository $pointOfSales
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->pointOfSales->paginate($perPage);
    }

    public function create(array $data): PointOfSale
    {
        $pointOfSale = DB::transaction(function () use ($data): PointOfSale {
            $users = $data['users'] ?? [];
            $data = $this->normalize($data, true);
            $warehouse = Warehouse::query()->lockForUpdate()->findOrFail((int) $data['warehouse_id']);
            $sequence = $this->nextSequence($warehouse->id);
            $data['company_id'] = $warehouse->company_id;
            $data['branch_id'] = $warehouse->branch_id;
            $data['sequence_number'] = $sequence;
            $data['code'] = $this->referenceFor($warehouse, $sequence);

            $pointOfSale = $this->pointOfSales->create($data);
            $this->ensureUsersBelongToCompany($users, $pointOfSale->company_id);
            $pointOfSale->users()->sync($users);

            return $pointOfSale->refresh()->load(['branch', 'warehouse', 'users']);
        });

        Log::info('Point of sale created', ['point_of_sale_id' => $pointOfSale->id]);

        return $pointOfSale;
    }

    public function update(PointOfSale $pointOfSale, array $data): PointOfSale
    {
        $pointOfSale = DB::transaction(function () use ($pointOfSale, $data): PointOfSale {
            $users = $data['users'] ?? [];
            $data = $this->normalize($data);
            $warehouse = Warehouse::query()->lockForUpdate()->findOrFail((int) $data['warehouse_id']);
            $data['company_id'] = $warehouse->company_id;
            $data['branch_id'] = $warehouse->branch_id;

            if ((int) $pointOfSale->warehouse_id !== $warehouse->id) {
                $sequence = $this->nextSequence($warehouse->id);
                $data['sequence_number'] = $sequence;
                $data['code'] = $this->referenceFor($warehouse, $sequence);
            } else {
                unset($data['code'], $data['sequence_number']);
            }

            $pointOfSale = $this->pointOfSales->update($pointOfSale, $data);
            $this->ensureUsersBelongToCompany($users, $pointOfSale->company_id);
            $pointOfSale->users()->sync($users);

            return $pointOfSale->refresh()->load(['branch', 'warehouse', 'users']);
        });

        Log::info('Point of sale updated', ['point_of_sale_id' => $pointOfSale->id]);

        return $pointOfSale;
    }

    public function delete(PointOfSale $pointOfSale): bool
    {
        $deleted = $this->pointOfSales->delete($pointOfSale);

        Log::warning('Point of sale deleted', ['point_of_sale_id' => $pointOfSale->id]);

        return $deleted;
    }

    private function normalize(array $data, ?bool $defaultActive = null): array
    {
        unset($data['code'], $data['sequence_number'], $data['users']);

        if (array_key_exists('is_active', $data)) {
            $data['is_active'] = (bool) $data['is_active'];
        } elseif ($defaultActive !== null) {
            $data['is_active'] = $defaultActive;
        }

        return $data;
    }

    private function ensureUsersBelongToCompany(array $userIds, ?int $companyId): void
    {
        if ($companyId === null || $userIds === []) {
            return;
        }

        $invalidUsers = User::query()
            ->whereIn('id', $userIds)
            ->where(fn ($query) => $query
                ->where('company_id', '<>', $companyId)
                ->orWhereNull('company_id'))
            ->exists();

        if ($invalidUsers) {
            throw ValidationException::withMessages([
                'users' => 'Todos los usuarios asignados deben pertenecer a la misma empresa del punto de venta.',
            ]);
        }
    }

    private function nextSequence(int $warehouseId): int
    {
        return ((int) PointOfSale::query()
            ->withTrashed()
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->max('sequence_number')) + 1;
    }

    private function referenceFor(Warehouse $warehouse, int $sequence): string
    {
        return $warehouse->branch_id.'-'.$warehouse->id.'-'.str_pad((string) $sequence, 6, '0', STR_PAD_LEFT);
    }
}
