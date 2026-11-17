<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityParticipant extends Model
{
    public const STATUSES = ['inscrito', 'acreditado', 'no_acreditado', 'baja'];

    public const PAYMENT_STATUSES = ['no_aplica', 'pendiente', 'pagado', 'becado'];

    protected $fillable = [
        'academic_activity_id',
        'people_id',
        'status',
        'attendance',
        'grade',
        'payment_status',
        'certificate_folio',
        'certificate_issued_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance' => 'integer',
            'grade' => 'float',
            'certificate_issued_at' => 'datetime',
        ];
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(AcademicActivity::class, 'academic_activity_id');
    }

    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }
}
