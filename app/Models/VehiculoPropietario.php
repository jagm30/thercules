<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vehiculo_id
 * @property int $cliente_id
 * @property Carbon $desde
 * @property Carbon|null $hasta
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class VehiculoPropietario extends Model
{
    protected $table = 'vehiculo_propietarios';

    protected $fillable = [
        'vehiculo_id',
        'cliente_id',
        'desde',
        'hasta',
    ];

    protected function casts(): array
    {
        return [
            'desde' => 'date',
            'hasta' => 'date',
        ];
    }

    /** @return BelongsTo<Vehiculo, $this> */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function esActual(): bool
    {
        return $this->hasta === null;
    }
}
