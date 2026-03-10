<?php

declare(strict_types=1);

namespace Enabel\Typesense\Type;

/** @extends AbstractType<string, string> */
final class StringType extends AbstractType
{
    public function __construct(bool $array = false)
    {
        parent::__construct('string', $array);
    }

    protected function normalizeValue(mixed $value): string
    {
        return (string) $value;
    }

    protected function denormalizeValue(string|int|float|bool $value): string
    {
        return (string) $value;
    }
}
