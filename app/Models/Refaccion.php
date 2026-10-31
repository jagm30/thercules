<?php

namespace App\Models;

use App\Enums\UnidadRefaccion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string|null $numero_parte
 * @property string $descripcion
 * @property string|null $marca
 * @property string|null $categoria
 * @property UnidadRefaccion $unidad
 * @property string|null $costo
 * @property string|null $precio_venta
 * @property bool $activo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class Refaccion extends Model
{
    use SoftDeletes;

    protected $table = 'refacciones';

    protected $fillable = [
        'numero_parte',
        'descripcion',
        'marca',
        'categoria',
        'unidad',
        'costo',
        'precio_venta',
        'activo',
    ];

    /** El costo de compra solo se expone con el permiso costos.ver. */
    protected $hidden = ['costo'];

    protected function casts(): array
    {
        return [
            'unidad' => UnidadRefaccion::class,
            'costo' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    /** @return BelongsToMany<Servicio, $this> */
    public function servicios(): BelongsToMany
    {
        return $this->belongsToMany(Servicio::class, 'servicio_refacciones')
            ->withPivot('cantidad')
            ->withTimestamps();
    }

    /** @return HasMany<OrdenRefaccion, $this> */
    public function usos(): HasMany
    {
        return $this->hasMany(OrdenRefaccion::class);
    }

    /**
     * @param  Builder<Refaccion>  $query
     * @return Builder<Refaccion>
     */
    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('descripcion');
    }

    /**
     * @param  Builder<Refaccion>  $query
     * @return Builder<Refaccion>
     */
    public function scopeBuscar(Builder $query, string $texto): Builder
    {
        return $query->where(function ($q) use ($texto) {
            $q->where('descripcion', 'like', "%{$texto}%")
                ->orWhere('numero_parte', 'like', "{$texto}%");
        });
    }
}
