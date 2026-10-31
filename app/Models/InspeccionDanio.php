<?php

namespace App\Models;

use App\Enums\SeveridadDanio;
use App\Enums\TipoDanio;
use App\Enums\VistaVehiculo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $inspeccion_id
 * @property int|null $zona_vehiculo_id
 * @property VistaVehiculo $vista
 * @property string $pos_x
 * @property string $pos_y
 * @property TipoDanio $tipo
 * @property SeveridadDanio $severidad
 * @property int|null $medida_cm
 * @property string|null $descripcion
 * @property int|null $danio_origen_id
 * @property bool $es_nuevo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class InspeccionDanio extends Model
{
    protected $table = 'inspeccion_danios';

    protected $fillable = [
        'inspeccion_id',
        'zona_vehiculo_id',
        'vista',
        'pos_x',
        'pos_y',
        'tipo',
        'severidad',
        'medida_cm',
        'descripcion',
        'danio_origen_id',
        'es_nuevo',
    ];

    protected function casts(): array
    {
        return [
            'vista' => VistaVehiculo::class,
            'tipo' => TipoDanio::class,
            'severidad' => SeveridadDanio::class,
            'pos_x' => 'decimal:2',
            'pos_y' => 'decimal:2',
            'medida_cm' => 'integer',
            'es_nuevo' => 'boolean',
        ];
    }

    /** @return BelongsTo<Inspeccion, $this> */
    public function inspeccion(): BelongsTo
    {
        return $this->belongsTo(Inspeccion::class);
    }

    /** @return BelongsTo<ZonaVehiculo, $this> */
    public function zona(): BelongsTo
    {
        return $this->belongsTo(ZonaVehiculo::class, 'zona_vehiculo_id');
    }

    /** En la entrega: el mismo daño que se registró al recibir. */
    /** @return BelongsTo<InspeccionDanio, $this> */
    public function danioOrigen(): BelongsTo
    {
        return $this->belongsTo(self::class, 'danio_origen_id');
    }

    /** @return HasMany<InspeccionDanio, $this> */
    public function seguimientos(): HasMany
    {
        return $this->hasMany(self::class, 'danio_origen_id');
    }

    /** @return MorphMany<Adjunto, $this> */
    public function fotos(): MorphMany
    {
        return $this->morphMany(Adjunto::class, 'adjuntable');
    }
}
