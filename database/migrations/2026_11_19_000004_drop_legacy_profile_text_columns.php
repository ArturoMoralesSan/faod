<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Quita columnas de texto que ya tienen tabla normalizada, después de pasar sus datos:
 * - teacher_profiles.highest_degree → teacher_degrees (si el docente no tiene grados).
 * - teacher_profiles.specialty      → specialty_teacher_profile (si coincide con el catálogo).
 * - staff_profiles.department       → staff_profiles.department_id (si coincide por nombre).
 */
return new class extends Migration
{
    private const LEVELS = ['licenciatura', 'especialidad', 'maestria', 'doctorado', 'posdoctorado'];

    public function up(): void
    {
        $this->moveTeacherData();
        $this->moveStaffData();

        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn(['highest_degree', 'specialty']);
        });

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->dropColumn('department');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->string('highest_degree')->nullable();
            $table->string('specialty')->nullable();
        });

        Schema::table('staff_profiles', function (Blueprint $table) {
            $table->string('department')->nullable();
        });
    }

    private function moveTeacherData(): void
    {
        $specialties = DB::table('specialties')->get(['id', 'name'])
            ->mapWithKeys(fn ($s) => [$this->normalize($s->name) => $s->id]);

        $teachers = DB::table('teacher_profiles')
            ->where(fn ($q) => $q->whereNotNull('highest_degree')->orWhereNotNull('specialty'))
            ->get(['id', 'highest_degree', 'specialty']);

        foreach ($teachers as $teacher) {
            $degree = trim((string) $teacher->highest_degree);

            if ($degree !== '' && ! DB::table('teacher_degrees')->where('teacher_profile_id', $teacher->id)->exists()) {
                $normalized = $this->normalize($degree);
                $level = collect(self::LEVELS)->first(fn ($level) => str_contains($normalized, $level)) ?? 'licenciatura';

                DB::table('teacher_degrees')->insert([
                    'teacher_profile_id' => $teacher->id,
                    'level' => $level,
                    'title' => $degree,
                    'institution' => 'Sin especificar',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $specialtyId = $specialties[$this->normalize((string) $teacher->specialty)] ?? null;

            if ($specialtyId && ! DB::table('specialty_teacher_profile')->where(['teacher_profile_id' => $teacher->id, 'specialty_id' => $specialtyId])->exists()) {
                DB::table('specialty_teacher_profile')->insert(['teacher_profile_id' => $teacher->id, 'specialty_id' => $specialtyId, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    private function moveStaffData(): void
    {
        $departments = DB::table('departments')->get(['id', 'name'])
            ->mapWithKeys(fn ($d) => [$this->normalize($d->name) => $d->id]);

        DB::table('staff_profiles')
            ->whereNull('department_id')
            ->whereNotNull('department')
            ->get(['id', 'department'])
            ->each(function ($staff) use ($departments) {
                if ($id = $departments[$this->normalize($staff->department)] ?? null) {
                    DB::table('staff_profiles')->where('id', $staff->id)->update(['department_id' => $id]);
                }
            });
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->ascii()->lower()->squish()->toString();
    }
};
