<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgramObjective extends Model
{
    public const TYPES = ['objetivo', 'competencia', 'perfil_ingreso', 'perfil_egreso'];

    protected $fillable = [
        'academic_program_id',
        'type',
        'description',
        'sort',
    ];

    public function academicProgram(): BelongsTo
    {
        return $this->belongsTo(AcademicProgram::class);
    }
}
