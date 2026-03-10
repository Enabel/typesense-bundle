<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search;

final readonly class Filter implements \Stringable
{
    private function __construct(
        private string $expression,
    ) {}

    public static function equals(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:=%s', $field, self::formatValue($value)));
    }

    public static function notEquals(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:!=%s', $field, self::formatValue($value)));
    }

    public static function matches(string $field, string $value): self
    {
        return new self(\sprintf('%s:%s', $field, $value));
    }

    public static function greaterThan(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:>%s', $field, self::formatValue($value)));
    }

    public static function greaterThanOrEqual(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:>=%s', $field, self::formatValue($value)));
    }

    public static function lessThan(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:<%s', $field, self::formatValue($value)));
    }

    public static function lessThanOrEqual(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:<=%s', $field, self::formatValue($value)));
    }

    /**
     * @param array<string|int|float|bool> $values
     */
    public static function in(string $field, array $values): self
    {
        $formatted = implode(', ', array_map(self::formatValue(...), $values));

        return new self(\sprintf('%s:[%s]', $field, $formatted));
    }

    /**
     * @param array<string|int|float|bool> $values
     */
    public static function notIn(string $field, array $values): self
    {
        $formatted = implode(', ', array_map(self::formatValue(...), $values));

        return new self(\sprintf('%s:!=[%s]', $field, $formatted));
    }

    public static function between(string $field, int|float $min, int|float $max): self
    {
        return new self(\sprintf('%s:[%s..%s]', $field, $min, $max));
    }

    public static function all(self ...$filters): self
    {
        return new self(implode(' && ', array_map(fn(self $f) => $f->expression, $filters)));
    }

    public static function any(self ...$filters): self
    {
        return new self(implode(' || ', array_map(fn(self $f) => $f->expression, $filters)));
    }

    public function __toString(): string
    {
        return $this->expression;
    }

    private static function formatValue(string|int|float|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            return \sprintf('`%s`', $value);
        }

        return (string) $value;
    }
}
