<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogType extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(CatalogItem::class)->orderBy('sort');
    }

    /**
     * Opciones activas de un catálogo por su código, p. ej. CatalogType::options('document_types').
     */
    public static function options(string $code)
    {
        return CatalogItem::query()
            ->whereHas('catalogType', fn ($q) => $q->where('code', $code))
            ->where('is_active', true)
            ->orderBy('sort')
            ->get(['id', 'code', 'name']);
    }
}
