<?php

namespace App\Models;

use App\Enums\VistaVehiculo;
use App\Models\Concerns\FiltraPorCarroceria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $clave
 * @property string $nombre
 * @property VistaVehiculo $vista
 * @property array<int, string>|null $tipos_carroceria
 * @property int $orden
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class ZonaVehiculo extends Model
{
    use FiltraPorCarroceria;

    protected $table = 'zonas_vehiculo';

    protected $fillable = [
        'clave',
        'nombre',
        'vista',
        'tipos_carroceria',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'vista' => VistaVehiculo::class,
            'tipos_carroceria' => 'array',
            'orden' => 'integer',
        ];
    }

    /** @return HasMany<InspeccionDanio, $this> */
    public function danios(): HasMany
    {
        return $this->hasMany(InspeccionDanio::class);
    }
}
