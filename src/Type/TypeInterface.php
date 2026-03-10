<?php

declare(strict_types=1);

namespace Enabel\Typesense\Type;

interface TypeInterface
{
    public string $name { get; }

    /** @return string|int|float|bool|array<string|int|float|bool> */
    public function normalize(mixed $value): string|int|float|bool|array;

    /** @param string|int|float|bool|array<string|int|float|bool> $value */
    public function denormalize(string|int|float|bool|array $value): mixed;
}
