<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Conteo físico de un almacén. Al cerrarlo se generan los ajustes.
 */
class InventoryCount extends Model
{
    use LogsActivity;

    protected $fillable = ['warehouse_id', 'status', 'notes', 'user_id', 'closed_at', 'closed_by'];

    protected function casts(): array
    {
        return [
            'closed_at' => 'datetime',
        ];
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InventoryCountLine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
