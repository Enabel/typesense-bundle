<?php

declare(strict_types=1);

namespace Enabel\Typesense\Mapping;

use Enabel\Typesense\Type\TypeInterface;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
final readonly class Id
{
    public function __construct(
        public ?TypeInterface $type = null,
    ) {}
}
