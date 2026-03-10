<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Fixtures;

enum IntStatus: int
{
    case Active = 1;
    case Inactive = 0;
}
