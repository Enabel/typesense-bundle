<?php

declare(strict_types=1);

namespace Enabel\Typesense\Type;

/**
 * @template TPhp
 * @template TTypesense of string|int|float|bool
 */
abstract class AbstractType implements TypeInterface
{
    public string $name {
        get => $this->baseName . ($this->array ? '[]' : '');
    }

    public function __construct(
        private readonly string $baseName,
        private readonly bool $array = false,
    ) {}

    /**
     * @return TTypesense
     */
    abstract protected function normalizeValue(mixed $value): string|int|float|bool;

    /**
     * @return TPhp
     */
    abstract protected function denormalizeValue(string|int|float|bool $value): mixed;

    public function normalize(mixed $value): string|int|float|bool|array
    {
        if ($this->array) {
            assert(is_array($value));

            return array_map($this->normalizeValue(...), $value);
        }

        return $this->normalizeValue($value);
    }

    public function denormalize(string|int|float|bool|array $value): mixed
    {
        if ($this->array) {
            assert(is_array($value));

            return array_map($this->denormalizeValue(...), $value);
        }
        
        assert(!is_array($value));

        return $this->denormalizeValue($value);
    }
}
