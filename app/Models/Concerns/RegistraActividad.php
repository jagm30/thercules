<?php

namespace App\Models\Concerns;

/*
 * Único punto de contacto con spatie/laravel-activitylog.
 *
 * - v5 (PHP 8.4+, recomendado):  Spatie\Activitylog\Models\Concerns\LogsActivity
 *                                Spatie\Activitylog\Support\LogOptions
 * - v4 (si el servidor tiene PHP 8.3): cambia los dos imports por
 *                                Spatie\Activitylog\Traits\LogsActivity
 *                                Spatie\Activitylog\LogOptions
 */
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

trait RegistraActividad
{
    use LogsActivity;

    /** Registra los campos fillable que cambiaron, con su valor anterior y nuevo. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty();
    }
}
