<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Consulta: motivo, signos vitales y exploración.
 */
class PatientConsultation extends Model
{
    /**
     * Síntomas bucales que refiere el paciente, agrupados como en la historia clínica.
     * Las etiquetas están en resources/js/lib/labels.json.
     */
    public const SYMPTOMS = [
        'dolor' => ['dolor_espontaneo', 'dolor_provocado', 'dolor_al_masticar', 'dolor_nocturno', 'dolor_irradiado'],
        'sensibilidad' => ['sensibilidad_frio', 'sensibilidad_calor', 'sensibilidad_dulce'],
        'periodontal' => ['sangrado_encias', 'inflamacion', 'movilidad_dental', 'supuracion', 'halitosis'],
        'otros' => ['diente_fracturado', 'lesion_mucosa', 'dolor_atm', 'limitacion_apertura', 'bruxismo', 'boca_seca'],
    ];

    /** @return array<int, string> */
    public static function symptomKeys(): array
    {
        return array_merge(...array_values(self::SYMPTOMS));
    }

    protected $fillable = [
        'patient_id',
        'clinic_id',
        'clinical_session_id',
        'student_profile_id',
        'teacher_profile_id',
        'attended_at',
        'reason',
        'current_illness',
        'symptoms',
        'pain_intensity',
        'blood_pressure',
        'heart_rate',
        'respiratory_rate',
        'temperature',
        'weight',
        'height',
        'findings',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'attended_at' => 'datetime',
            'symptoms' => 'array',
            'pain_intensity' => 'integer',
            'heart_rate' => 'integer',
            'respiratory_rate' => 'integer',
            'temperature' => 'float',
            'weight' => 'float',
            'height' => 'float',
        ];
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function clinicalSession(): BelongsTo
    {
        return $this->belongsTo(ClinicalSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class, 'teacher_profile_id');
    }

    public function progressNotes(): HasMany
    {
        return $this->hasMany(PatientProgressNote::class);
    }
}
