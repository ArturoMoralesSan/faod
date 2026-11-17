<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Antecedente del paciente.
 */
class PatientCondition extends Model
{
    public const CATEGORIES = [
        'heredofamiliar',
        'patologico',
        'no_patologico',
        'odontologico',
        'ginecoobstetrico',
    ];

    protected $fillable = ['patient_id', 'category', 'name', 'notes'];

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }
}
