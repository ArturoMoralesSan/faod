<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItem extends Model
{
    protected $fillable = [
        'catalog_type_id',
        'code',
        'name',
        'meta',
        'sort',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
            'sort' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function catalogType(): BelongsTo
    {
        return $this->belongsTo(CatalogType::class);
    }
}
