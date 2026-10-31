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
use Illuminate\Support\Carbon;

/**
 * Acta de cómo llegó (recepción) o salió (entrega) el vehículo.
 * Se completa con App\Actions\CompletarInspeccion; completada, es de solo lectura.
 *
 * @property int $id
 * @property int $orden_servicio_id
 * @property TipoInspeccion $tipo
 * @property EstadoInspeccion $estado
 * @property int $realizada_por_id
 * @property Carbon $iniciada_at
 * @property Carbon|null $completada_at
 * @property TipoCarroceria $tipo_carroceria
 * @property int|null $km
 * @property string|null $km_justificacion
 * @property int|null $nivel_combustible
 * @property string|null $placas
 * @property bool $sin_placas
 * @property string|null $persona_nombre
 * @property string|null $persona_telefono
 * @property bool $persona_es_cliente
 * @property bool|null $sin_danios
 * @property string|null $objetos_personales
 * @property bool|null $cliente_retiro_valores
 * @property string|null $observaciones
 * @property bool|null $acepta_terminos
 * @property int|null $terminos_version
 * @property string|null $firmante_nombre
 * @property Carbon|null $firmado_at
 * @property string|null $dispositivo
 * @property Carbon $created_at
 * @property Carbon $updated_at
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

    /** @return BelongsTo<OrdenServicio, $this> */
    public function orden(): BelongsTo
    {
        return $this->belongsTo(OrdenServicio::class, 'orden_servicio_id');
    }

    /** @return BelongsTo<User, $this> */
    public function realizadaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'realizada_por_id');
    }

    /** @return HasMany<InspeccionRespuesta, $this> */
    public function respuestas(): HasMany
    {
        return $this->hasMany(InspeccionRespuesta::class);
    }

    /** @return HasMany<InspeccionDanio, $this> */
    public function danios(): HasMany
    {
        return $this->hasMany(InspeccionDanio::class);
    }

    /** @return MorphMany<Adjunto, $this> */
    public function adjuntos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'adjuntable');
    }

    /** @return MorphMany<Adjunto, $this> */
    public function fotos(): MorphMany
    {
        return $this->adjuntos()->where('tipo', TipoAdjunto::Foto->value);
    }

    /** @return MorphOne<Adjunto, $this> */
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
