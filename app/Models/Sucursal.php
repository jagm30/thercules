<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function usuarios(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class);
    }

    public function preferencias(): HasMany
    {
        return $this->hasMany(PreferenciaOperacion::class);
    }
}
