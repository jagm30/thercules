<?php

namespace App\Models;

use App\Enums\TipoCarroceria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property int $marca_id
 * @property string $nombre
 * @property TipoCarroceria $tipo_carroceria
 * @property int|null $anio_inicio
 * @property int|null $anio_fin
 * @property bool $verificado
 * @property bool $activo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class ModeloVehiculo extends Model
{
    use SoftDeletes;

    protected $table = 'modelos_vehiculo';

    protected $fillable = [
        'marca_id',
        'nombre',
        'tipo_carroceria',
        'anio_inicio',
        'anio_fin',
        'verificado',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'tipo_carroceria' => TipoCarroceria::class,
            'anio_inicio' => 'integer',
            'anio_fin' => 'integer',
            'verificado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /** @return BelongsTo<Marca, $this> */
    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    /** @return HasMany<Vehiculo, $this> */
    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }

    /**
     * @param  Builder<ModeloVehiculo>  $query
     * @return Builder<ModeloVehiculo>
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('nombre');
    }

    /**
     * @param  Builder<ModeloVehiculo>  $query
     * @return Builder<ModeloVehiculo>
     */
    public function scopePendientesDeVerificar(Builder $query): Builder
    {
        return $query->where('verificado', false);
    }

    /** ¿El año capturado es coherente con los años del modelo? (advertencia, no bloqueo) */
    public function anioEsCoherente(?int $anio): bool
    {
        if ($anio === null) {
            return true;
        }

        return ($this->anio_inicio === null || $anio >= $this->anio_inicio)
            && ($this->anio_fin === null || $anio <= $this->anio_fin);
    }

    /**
     * Fusiona este modelo (duplicado o mal escrito) dentro de $destino:
     * reasigna los vehículos y elimina este registro.
     */
    public function fusionarEn(self $destino): void
    {
        DB::transaction(function () use ($destino) {
            Vehiculo::withTrashed()->where('modelo_vehiculo_id', $this->id)->update([
                'modelo_vehiculo_id' => $destino->id,
                'marca_id' => $destino->marca_id,
            ]);
            $this->forceDelete();
        });
    }
}
