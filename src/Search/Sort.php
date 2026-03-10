<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search;

enum Sort: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
