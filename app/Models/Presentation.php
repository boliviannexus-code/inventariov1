<?php

namespace App\Models;

use Database\Factories\PresentationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presentation extends Model
{
    /** @use HasFactory<PresentationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'units_per_package',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'units_per_package' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function inventoryMovements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }
}
