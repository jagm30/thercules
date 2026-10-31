<?php

namespace App\Models;

use App\Enums\EstadoInspeccion;
use App\Enums\TipoAdjunto;
use App\Enums\TipoCarroceria;
use App\Enums\TipoInspeccion;
use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Acta de cómo llegó (recepción) o salió (entrega) el vehículo.
 * Se completa con App\Actions\CompletarInspeccion; completada, es de solo lectura.
 */
class Inspeccion extends Model
{
    use RegistraActividad;

    protected $table = 'inspecciones';

    protected $fillable = [
        'orden_servicio_id',
        'tipo',
        'estado',
        'realizada_por_id',
        'iniciada_at',
        'completada_at',
        'tipo_carroceria',
        'km',
        'km_justificacion',
        'nivel_combustible',
        'placas',
        'sin_placas',
        'persona_nombre',
        'persona_telefono',
        'persona_es_cliente',
        'sin_danios',
        'objetos_personales',
        'cliente_retiro_valores',
        'observaciones',
        'acepta_terminos',
        'terminos_version',
        'firmante_nombre',
        'firmado_at',
        'dispositivo',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoInspeccion::class,
            'estado' => EstadoInspeccion::class,
            'tipo_carroceria' => TipoCarroceria::class,
            'iniciada_at' => 'datetime',
            'completada_at' => 'datetime',
            'firmado_at' => 'datetime',
            'km' => 'integer',
            'nivel_combustible' => 'integer',
            'terminos_version' => 'integer',
            'sin_placas' => 'boolean',
            'persona_es_cliente' => 'boolean',
            'sin_danios' => 'boolean',
            'cliente_retiro_valores' => 'boolean',
            'acepta_terminos' => 'boolean',
        ];
    }

    // ── Relaciones ───────────────────────────────────────────────

    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    public function realizadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realizada_por_id');
    }

    public function respuestas(): HasMany
    {
        return $this->hasMany(InspeccionRespuesta::class);
    }

    public function danios(): HasMany
    {
        return $this->hasMany(InspeccionDanio::class);
    }

    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'adjuntable');
    }

    public function fotos(): MorphMany
    {
        return $this->adjuntos()->where('tipo', TipoAdjunto::Foto->value);
    }

    public function firma(): MorphOne
    {
        return $this->morphOne(Adjunto::class, 'adjuntable')
            ->where('tipo', TipoAdjunto::Firma->value)
            ->latestOfMany();
    }

    // ── Estado ───────────────────────────────────────────────────

    public function estaCompletada(): bool
    {
        return $this->estado === EstadoInspeccion::Completada;
    }

    public function esRecepcion(): bool
    {
        return $this->tipo === TipoInspeccion::Recepcion;
    }

    /** Nivel de combustible como fracción legible: 0/8 … 8/8. */
    public function combustibleTexto(): ?string
    {
        return $this->nivel_combustible === null ? null : "{$this->nivel_combustible}/8";
    }
}
