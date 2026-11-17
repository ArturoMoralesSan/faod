<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Registra en el historial quién crea, modifica o elimina el modelo y qué campos cambian.
 *
 * Por defecto ignora marcas de tiempo, contraseñas y campos ocultos. Un modelo puede
 * definir $activityIgnore (campos extra a ignorar) y activityLabel() (texto del registro).
 */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => $model->logActivity('created', $model->activityValues($model->getAttributes(), true)));

        static::updated(function (Model $model) {
            $changes = [];

            foreach ($model->activityValues($model->getChanges()) as $field => $new) {
                $changes[$field] = [$model->getOriginal($field), $new];
            }

            if ($changes) {
                $model->logActivity('updated', $changes);
            }
        });

        static::deleted(fn (Model $model) => $model->logActivity('deleted', null));

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn (Model $model) => $model->logActivity('restored', null));
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function activityValues(array $attributes, bool $asNew = false): array
    {
        $ignore = [
            'id', 'created_at', 'updated_at', 'deleted_at', 'password', 'remember_token',
            'two_factor_secret', 'two_factor_recovery_codes', 'qr_token', 'last_login_at',
            ...$this->getHidden(),
            ...($this->activityIgnore ?? []),
        ];

        $values = array_diff_key($attributes, array_flip($ignore));

        // En la creación se guarda como [null, valor] para que la vista sea uniforme.
        return $asNew
            ? array_map(fn ($value) => [null, $value], array_filter($values, fn ($v) => $v !== null && $v !== ''))
            : $values;
    }

    protected function logActivity(string $event, ?array $changes): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'subject_type' => static::class,
            'subject_id' => $this->getKey(),
            'subject_label' => mb_substr($this->activityLabel(), 0, 255),
            'changes' => $changes ?: null,
            'ip_address' => request()?->ip(),
        ]);
    }

    public function activityLabel(): string
    {
        foreach (['full_name', 'record_number', 'folio', 'title', 'name', 'description', 'code'] as $attribute) {
            $value = $this->getAttribute($attribute);

            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return '#'.$this->getKey();
    }
}
