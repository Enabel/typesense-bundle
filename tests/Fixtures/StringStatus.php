<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Fixtures;

enum StringStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
