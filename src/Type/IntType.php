<?php

declare(strict_types=1);

namespace Enabel\Typesense\Type;

/** @extends AbstractType<int, int> */
final class IntType extends AbstractType
{
    public function __construct(
        public readonly bool $int32 = false,
        bool $array = false,
    ) {
        parent::__construct($int32 ? 'int32' : 'int64', $array);
    }

    protected function normalizeValue(mixed $value): int
    {
        return (int) $value;
    }

    protected function denormalizeValue(string|int|float|bool $value): int
    {
        return (int) $value;
    }
}
