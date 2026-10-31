<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $nombre
 * @property string $prefijo_folio
 * @property int $ultimo_folio
 * @property string|null $direccion
 * @property string|null $telefono
 * @property bool $activo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Sucursal extends Model
{
    protected $table = 'sucursales';

    protected $fillable = [
        'nombre',
        'prefijo_folio',
        'ultimo_folio',
        'direccion',
        'telefono',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'ultimo_folio' => 'integer',
            'activo' => 'boolean',
        ];
    }

    /** @return HasMany<User, $this> */
    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** @return HasMany<OrdenServicio, $this> */
    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }

    /** @return HasMany<PreferenciaOperacion, $this> */
    public function preferencias(): HasMany
    {
        return $this->hasMany(PreferenciaOperacion::class);
    }
}
