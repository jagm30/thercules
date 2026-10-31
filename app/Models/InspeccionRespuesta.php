<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $inspeccion_id
 * @property int|null $checklist_item_id
 * @property string $item_nombre
 * @property string $valor
 * @property string|null $comentario
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class InspeccionRespuesta extends Model
{
    protected $table = 'inspeccion_respuestas';

    protected $fillable = [
        'inspeccion_id',
        'checklist_item_id',
        'item_nombre',
        'valor',
        'comentario',
    ];

    protected static function booted(): void
    {
        // Guarda el nombre del punto al momento, por si después se renombra en el catálogo.
        static::creating(function (InspeccionRespuesta $r) {
            if (blank($r->item_nombre) && $r->checklist_item_id) {
                $r->item_nombre = ChecklistItem::withTrashed()->find($r->checklist_item_id)?->nombre;
            }
        });
    }

    /** @return BelongsTo<Inspeccion, $this> */
    public function inspeccion(): BelongsTo
    {
        return $this->belongsTo(Inspeccion::class);
    }

    /** @return BelongsTo<ChecklistItem, $this> */
    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class)->withTrashed();
    }
}
