<?php

namespace App\Models;

use App\Models\Concerns\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Expediente clínico de una persona.
 */
class Patient extends Model
{
    use HasAttachments, SoftDeletes;

    public const STATUSES = ['activo', 'alta', 'baja'];

    public const BLOOD_TYPES = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];

    public const MARITAL_STATUSES = ['soltero', 'casado', 'union_libre', 'divorciado', 'viudo'];

    protected $fillable = [
        'people_id',
        'record_number',
        'clinic_id',
        'blood_type',
        'occupation',
        'marital_status',
        'referred_by',
        'status',
        'registered_at',
        'notes',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'date:Y-m-d',
        ];
    }

    protected static function booted(): void
    {
        // Expediente consecutivo: P-000001, P-000002...
        static::creating(function (Patient $patient) {
            if (! $patient->record_number) {
                $last = static::withTrashed()->max('id') ?? 0;

                do {
                    $patient->record_number = sprintf('P-%06d', ++$last);
                } while (static::withTrashed()->where('record_number', $patient->record_number)->exists());
            }
        });
    }

    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function emergencyContacts(): HasMany
    {
        return $this->hasMany(PatientEmergencyContact::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(PatientCondition::class)->orderBy('category');
    }

    public function allergies(): HasMany
    {
        return $this->hasMany(PatientAllergy::class);
    }

    public function medications(): HasMany
    {
        return $this->hasMany(PatientMedication::class)->latest('started_at');
    }

    public function consultations(): HasMany
    {
        return $this->hasMany(PatientConsultation::class)->latest('attended_at');
    }

    public function diagnoses(): HasMany
    {
        return $this->hasMany(PatientDiagnosis::class)->latest('diagnosed_at')->latest('id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class)->orderBy('date')->orderBy('start_time');
    }

    public function treatmentPlans(): HasMany
    {
        return $this->hasMany(TreatmentPlan::class)->latest('started_at')->latest('id');
    }

    public function odontogramEntries(): HasMany
    {
        return $this->hasMany(OdontogramEntry::class)->latest('recorded_at')->latest('id');
    }

    public function progressNotes(): HasMany
    {
        return $this->hasMany(PatientProgressNote::class)->latest('noted_at');
    }
}
