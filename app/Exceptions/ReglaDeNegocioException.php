<?php

namespace App\Exceptions;

use DomainException;

/**
 * Se lanza cuando una operación viola una regla del taller
 * (transición de estado no permitida, recepción incompleta, etc.).
 * $errores trae la lista exacta de lo que falta, para mostrarla en pantalla.
 */
class ReglaDeNegocioException extends DomainException
{
    /** @param list<string> $errores */
    public function __construct(string $mensaje, public readonly array $errores = [])
    {
        parent::__construct($mensaje);
    }
}
