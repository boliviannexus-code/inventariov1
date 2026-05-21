<?php

namespace App\Services;

use App\Models\Branch;
use App\Repositories\BranchRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BranchService
{
    public function __construct(
        private readonly BranchRepository $branches
    ) {}

    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return $this->branches->paginate($perPage);
    }

    public function active(): Collection
    {
        return $this->branches->active();
    }

    public function create(array $data): Branch
    {
        $branch = $this->branches->create($this->normalize($data, true));

        Log::info('Branch created', ['branch_id' => $branch->id]);

        return $branch;
    }

    public function update(Branch $branch, array $data): Branch
    {
        $branch = $this->branches->update($branch, $this->normalize($data));

        Log::info('Branch updated', ['branch_id' => $branch->id]);

        return $branch;
    }

    public function delete(Branch $branch): bool
    {
        $deleted = DB::transaction(function () use ($branch): bool {
            $branch->pointOfSales()->delete();
            $branch->warehouses()->delete();

            return $this->branches->delete($branch);
        });

        Log::warning('Branch deleted', ['branch_id' => $branch->id]);

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
