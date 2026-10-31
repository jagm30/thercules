<?php

namespace App\Models;

use App\Enums\TipoPreferencia;
use App\Models\Concerns\RegistraActividad;
use App\Support\Preferencias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cómo opera el taller (modo con o sin precios, reglas de recepción, etc.).
 * Solo el administrador las modifica; cada cambio queda en activity_log.
 */
class PreferenciaOperacion extends Model
{
    use RegistraActividad;

    protected $table = 'preferencias_operacion';

    protected $fillable = [
        'sucursal_id',
        'grupo',
        'clave',
        'valor',
        'tipo',
        'descripcion',
        'actualizado_por_id',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoPreferencia::class,
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Preferencias::olvidar());
        static::deleted(fn () => Preferencias::olvidar());
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actualizado_por_id');
    }

    /** Convierte el texto guardado al tipo declarado. */
    public function valorTipado(): mixed
    {
        if ($this->valor === null) {
            return null;
        }

        return match ($this->tipo) {
            TipoPreferencia::Bool => filter_var($this->valor, FILTER_VALIDATE_BOOLEAN),
            TipoPreferencia::Int => (int) $this->valor,
            TipoPreferencia::Decimal => (float) $this->valor,
            TipoPreferencia::Json => json_decode($this->valor, true),
            TipoPreferencia::String, TipoPreferencia::Text => $this->valor,
        };
    }
}
