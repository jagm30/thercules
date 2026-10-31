<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $vehiculo_id
 * @property string|null $ip
 * @property string|null $user_agent
 * @property Carbon $created_at
 */
class QrConsulta extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'qr_consultas';

    protected $fillable = [
        'vehiculo_id',
        'ip',
        'user_agent',
    ];

    /** @return BelongsTo<Vehiculo, $this> */
    public function vehiculo(): BelongsTo
    {
        return $this->belongsTo(Vehiculo::class);
    }
}
