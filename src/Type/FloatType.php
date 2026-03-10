<?php

declare(strict_types=1);

namespace Enabel\Typesense\Type;

/** @extends AbstractType<float, float> */
final class FloatType extends AbstractType
{
    public function __construct(bool $array = false)
    {
        parent::__construct('float', $array);
    }

    protected function normalizeValue(mixed $value): float
    {
        return (float) $value;
    }

    protected function denormalizeValue(string|int|float|bool $value): float
    {
        return (float) $value;
    }
}
