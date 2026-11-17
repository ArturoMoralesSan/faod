<?php

namespace App\Models\Concerns;

use App\Models\Review;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Registro clínico que debe autorizar un docente.
 * El modelo necesita la columna review_status.
 */
trait Reviewable
{
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable')->latest('id');
    }

    public function latestReview(): MorphOne
    {
        return $this->morphOne(Review::class, 'reviewable')->latestOfMany();
    }

    /** Título corto para la lista de revisiones pendientes. */
    abstract public function reviewTitle(): string;

    /** URL donde se consulta el registro. */
    abstract public function reviewUrl(): string;
}
