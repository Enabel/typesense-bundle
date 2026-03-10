<?php

declare(strict_types=1);

namespace Enabel\Typesense\Type;

/** @extends AbstractType<bool, bool> */
final class BoolType extends AbstractType
{
    public function __construct(bool $array = false)
    {
        parent::__construct('bool', $array);
    }

    protected function normalizeValue(mixed $value): bool
    {
        return (bool) $value;
    }

    protected function denormalizeValue(string|int|float|bool $value): bool
    {
        return (bool) $value;
    }
}
