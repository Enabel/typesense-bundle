<?php

declare(strict_types=1);

namespace Enabel\Typesense\Mapping;

enum Infix: string
{
    case Off = 'off';
    case Always = 'always';
    case Fallback = 'fallback';
}
