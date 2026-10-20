<?php

declare(strict_types=1);

namespace Enabel\Typesense\Search;

final readonly class Filter implements \Stringable
{
    private function __construct(
        private string $expression,
        private bool $isGroup = false,
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
        return new self(implode(' && ', array_map(self::operand(...), $filters)), true);
    }

    public static function any(self ...$filters): self
    {
        return new self(implode(' || ', array_map(self::operand(...), $filters)), true);
    }

    public function __toString(): string
    {
        return $this->expression;
    }

    /**
     * Typesense gives && and || the same precedence and evaluates them left to
     * right, so a nested group must be parenthesised to keep its meaning.
     */
    private static function operand(self $filter): string
    {
        return $filter->isGroup ? \sprintf('(%s)', $filter->expression) : $filter->expression;
    }

    private static function formatValue(string|int|float|bool $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_string($value)) {
            // Typesense has no way to escape a backtick inside a quoted value:
            // one would close the quoting and let the rest of the value be
            // parsed as filter syntax.
            if (str_contains($value, '`')) {
                throw new \InvalidArgumentException(\sprintf('Filter values cannot contain a backtick, got "%s".', $value));
            }

            return \sprintf('`%s`', $value);
        }

        return (string) $value;
    }
}
