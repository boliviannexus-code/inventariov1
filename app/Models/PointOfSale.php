<?php

namespace App\Models;

use Database\Factories\PointOfSaleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PointOfSale extends Model
{
    /** @use HasFactory<PointOfSaleFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'branch_id',
        'warehouse_id',
        'name',
        'code',
        'sequence_number',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sequence_number' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function cashRegisters(): HasMany
    {
        return $this->hasMany(CashRegister::class);
    }
}
