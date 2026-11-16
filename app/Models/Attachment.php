<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    /**
     * Modelos que pueden recibir adjuntos (alias usado desde el frontend).
     */
    public const ATTACHABLE_TYPES = [
        'people' => People::class,
        'student_profile' => StudentProfile::class,
        'teacher_profile' => TeacherProfile::class,
        'student_enrollment' => StudentEnrollment::class,
        'university_registration' => UniversityRegistration::class,
        'academic_program' => AcademicProgram::class,
        'study_plan' => StudyPlan::class,
    ];

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'document_type_id',
        'title',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'uploaded_by',
    ];

    protected $hidden = [
        'disk',
        'path',
    ];

    protected static function booted(): void
    {
        static::deleted(function (Attachment $attachment) {
            Storage::disk($attachment->disk)->delete($attachment->path);
        });
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'document_type_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
