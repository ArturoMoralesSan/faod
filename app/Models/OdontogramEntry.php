<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Estado de un diente (o de una de sus caras) en una fecha.
 */
class OdontogramEntry extends Model
{
    /** Caras: oclusal/incisal, mesial, distal, vestibular, lingual/palatina. */
    public const SURFACES = ['O', 'M', 'D', 'V', 'L'];

    /** Condiciones de una cara. */
    public const SURFACE_CONDITIONS = ['sano', 'caries', 'obturado', 'sellador', 'fractura'];

    /** Condiciones del diente completo. */
    public const TOOTH_CONDITIONS = ['sano', 'ausente', 'extraccion_indicada', 'corona', 'endodoncia', 'implante', 'protesis', 'fractura'];

    public const PERMANENT_TEETH = [
        18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28,
        48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38,
    ];

    public const PRIMARY_TEETH = [
        55, 54, 53, 52, 51, 61, 62, 63, 64, 65,
        85, 84, 83, 82, 81, 71, 72, 73, 74, 75,
    ];

    protected $fillable = [
        'patient_id',
        'patient_diagnosis_id',
        'tooth',
        'surface',
        'condition',
        'notes',
        'recorded_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'recorded_at' => 'datetime',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function diagnosis(): BelongsTo
    {
        return $this->belongsTo(PatientDiagnosis::class, 'patient_diagnosis_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return list<string> */
    public static function teeth(): array
    {
        return array_map('strval', [...self::PERMANENT_TEETH, ...self::PRIMARY_TEETH]);
    }
}
