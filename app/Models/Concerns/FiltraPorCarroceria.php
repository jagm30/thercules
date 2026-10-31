<?php

namespace App\Models\Concerns;

use App\Enums\TipoCarroceria;
use Illuminate\Support\Collection;

/**
 * Para catálogos con columna JSON `tipos_carroceria` (NULL = aplica a todas).
 * Se filtra en PHP porque los catálogos son pequeños y así funciona igual en MySQL y SQLite.
 */
trait FiltraPorCarroceria
{
    public function aplicaA(TipoCarroceria|string $carroceria): bool
    {
        $valor = $carroceria instanceof TipoCarroceria ? $carroceria->value : $carroceria;
        $tipos = $this->tipos_carroceria;

        return empty($tipos) || in_array($valor, $tipos, true);
    }

    /**
     * @param  Collection<int, static>  $items
     * @return Collection<int, static>
     */
    public static function filtrarPorCarroceria(Collection $items, TipoCarroceria|string $carroceria): Collection
    {
        return $items->filter(fn ($item) => $item->aplicaA($carroceria))->values();
    }
}
