<?php

namespace App\Models;

use App\Enums\Combustible;
use App\Enums\EstadoOrden;
use App\Enums\Transmision;
use App\Models\Concerns\RegistraActividad;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $cliente_id
 * @property int $marca_id
 * @property int|null $modelo_vehiculo_id
 * @property int|null $anio
 * @property string|null $version
 * @property string|null $color
 * @property string|null $placas
 * @property string|null $placas_normalizadas
 * @property string|null $vin
 * @property string|null $numero_motor
 * @property Combustible|null $combustible
 * @property Transmision|null $transmision
 * @property int|null $km_actual
 * @property string $qr_token
 * @property bool $qr_activo
 * @property string|null $notas
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
class Vehiculo extends Model
{
    use RegistraActividad, SoftDeletes;

    protected $table = 'vehiculos';

    protected $fillable = [
        'cliente_id',
        'marca_id',
        'modelo_vehiculo_id',
        'anio',
        'version',
        'color',
        'placas',
        'vin',
        'numero_motor',
        'combustible',
        'transmision',
        'km_actual',
        'qr_activo',
        'notas',
    ];

    protected function casts(): array
    {
        return [
            'anio' => 'integer',
            'km_actual' => 'integer',
            'qr_activo' => 'boolean',
            'combustible' => Combustible::class,
            'transmision' => Transmision::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Vehiculo $v) {
            $v->qr_token ??= (string) Str::ulid();
        });
    }

    // ── Relaciones ───────────────────────────────────────────────

    /** @return BelongsTo<Cliente, $this> */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /** @return BelongsTo<Marca, $this> */
    public function marca(): BelongsTo
    {
        return $this->belongsTo(Marca::class);
    }

    /** @return BelongsTo<ModeloVehiculo, $this> */
    public function modelo(): BelongsTo
    {
        return $this->belongsTo(ModeloVehiculo::class, 'modelo_vehiculo_id');
    }

    /** @return HasMany<OrdenServicio, $this> */
    public function ordenes(): HasMany
    {
        return $this->hasMany(OrdenServicio::class)->latest('fecha_ingreso');
    }

    /** La orden con la que el vehículo está dentro del taller, si la hay.
     * @return HasOne<OrdenServicio, $this>
     */
    public function ordenAbierta(): HasOne
    {
        return $this->hasOne(OrdenServicio::class)
            ->whereIn('estado', EstadoOrden::abiertos())
            ->latestOfMany('fecha_ingreso');
    }

    /** @return HasMany<RecordatorioServicio, $this> */
    public function recordatorios(): HasMany
    {
        return $this->hasMany(RecordatorioServicio::class);
    }

    /** @return HasMany<VehiculoPropietario, $this> */
    public function propietarios(): HasMany
    {
        return $this->hasMany(VehiculoPropietario::class)->latest('desde');
    }

    /** @return HasMany<QrConsulta, $this> */
    public function qrConsultas(): HasMany
    {
        return $this->hasMany(QrConsulta::class);
    }

    // ── Atributos ────────────────────────────────────────────────

    /** @return Attribute<string|null, string|null> */
    protected function placas(): Attribute
    {
        return Attribute::set(fn (?string $valor) => [
            'placas' => $valor ? strtoupper(trim($valor)) : null,
            'placas_normalizadas' => self::normalizarPlacas($valor),
        ]);
    }

    /** @return Attribute<string|null, string|null> */
    protected function vin(): Attribute
    {
        return Attribute::set(fn (?string $valor) => $valor ? strtoupper(preg_replace('/\s+/', '', $valor)) : null);
    }

    public static function normalizarPlacas(?string $placas): ?string
    {
        if ($placas === null || trim($placas) === '') {
            return null;
        }

        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $placas));
    }

    // ── Scopes ───────────────────────────────────────────────────

    /**
     * Busca por placas sin importar guiones, espacios o mayúsculas.
     *
     * @param  Builder<Vehiculo>  $query
     * @return Builder<Vehiculo>
     */
    public function scopePorPlacas(Builder $query, string $placas): Builder
    {
        return $query->where('placas_normalizadas', self::normalizarPlacas($placas));
    }

    /**
     * @param  Builder<Vehiculo>  $query
     * @return Builder<Vehiculo>
     */
    public function scopePorVin(Builder $query, string $vin): Builder
    {
        return $query->where('vin', strtoupper(trim($vin)));
    }

    // ── Operaciones ──────────────────────────────────────────────

    /** El QR anterior deja de funcionar. */
    public function regenerarQr(): void
    {
        $this->forceFill(['qr_token' => (string) Str::ulid(), 'qr_activo' => true])->save();
    }

    /** Cierra el registro del dueño actual y abre uno nuevo, en una transacción. */
    public function cambiarPropietario(Cliente $nuevo, ?\DateTimeInterface $desde = null): void
    {
        $desde ??= now();

        DB::transaction(function () use ($nuevo, $desde) {
            $this->propietarios()->whereNull('hasta')->update(['hasta' => $desde->format('Y-m-d')]);
            $this->propietarios()->create([
                'cliente_id' => $nuevo->id,
                'desde' => $desde->format('Y-m-d'),
            ]);
            $this->update(['cliente_id' => $nuevo->id]);
        });
    }
}
