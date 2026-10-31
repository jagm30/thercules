<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nombre
 * @property string|null $logo_path
 * @property bool $verificado
 * @property bool $activo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class Marca extends Model
{
    use SoftDeletes;

    protected $table = 'marcas';

    protected $fillable = [
        'nombre',
        'logo_path',
        'verificado',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'verificado' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    /** @return HasMany<ModeloVehiculo, $this> */
    public function modelos(): HasMany
    {
        return $this->hasMany(ModeloVehiculo::class);
    }

    /** @return HasMany<Vehiculo, $this> */
    public function vehiculos(): HasMany
    {
        return $this->hasMany(Vehiculo::class);
    }

    /**
     * @param  Builder<Marca>  $query
     * @return Builder<Marca>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('nombre');
    }

    /**
     * Altas rápidas de recepción pendientes de revisar por el administrador.
     *
     * @param  Builder<Marca>  $query
     * @return Builder<Marca>
     */
    public function scopePendientesDeVerificar(Builder $query): Builder
    {
        return $query->where('verificado', false);
    }
}
