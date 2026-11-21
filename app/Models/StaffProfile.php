<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffProfile extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'people_id',
        'employee_number',
        'position',
        'department_id',
        'status',
        'hired_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'hired_at' => 'date',
        ];
    }

    public function people(): BelongsTo
    {
        return $this->belongsTo(People::class, 'people_id');
    }

    public function departmentRecord(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_id');
    }
}
