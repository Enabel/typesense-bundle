<?php

declare(strict_types=1);

namespace Enabel\Typesense\Type;

/** @extends AbstractType<\BackedEnum, string|int> */
final class BackedEnumType extends AbstractType
{
    /** @param class-string<\BackedEnum> $enumClass */
    public function __construct(
        public readonly string $enumClass,
        bool $array = false,
    ) {
        $reflection = new \ReflectionEnum($this->enumClass);

        parent::__construct($reflection->getBackingType()?->getName() === 'int' ? 'int64' : 'string', $array);
    }

    protected function normalizeValue(mixed $value): string|int
    {
        return $value->value;
    }

    protected function denormalizeValue(string|int|float|bool $value): \BackedEnum
    {
        return ($this->enumClass)::from(is_string($value) || is_int($value) ? $value : (string) $value);
    }

    public static function cast(mixed $value): string|int
    {
        return $value->value;
    }
}
