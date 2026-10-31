<?php

namespace App\Models;

use App\Enums\TipoCarroceria;
use App\Models\Concerns\FiltraPorCarroceria;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $clave
 * @property string $nombre
 * @property string|null $descripcion
 * @property bool $obligatorio
 * @property array<int, string>|null $tipos_carroceria
 * @property int $orden
 * @property bool $activo
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class AnguloFoto extends Model
{
    use FiltraPorCarroceria;

    protected $table = 'angulos_foto';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'obligatorio',
        'tipos_carroceria',
        'orden',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'tipos_carroceria' => 'array',
            'obligatorio' => 'boolean',
            'activo' => 'boolean',
            'orden' => 'integer',
        ];
    }

    /** @return HasMany<Adjunto, $this> */
    public function adjuntos(): HasMany
    {
        return $this->hasMany(Adjunto::class);
    }

    /**
     * @param  Builder<AnguloFoto>  $query
     * @return Builder<AnguloFoto>
     */
    public function scopeActivos(Builder $query): Builder
    {
        return $query->where('activo', true)->orderBy('orden');
    }

    /** @return Collection<int, static> */
    public static function obligatoriosPara(TipoCarroceria|string $carroceria): Collection
    {
        return new Collection(
            static::filtrarPorCarroceria(static::query()->activos()->where('obligatorio', true)->get(), $carroceria)->all()
        );
    }
}
