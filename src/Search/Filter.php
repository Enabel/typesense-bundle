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
        return new self(\sprintf('%s:=%s', self::field($field), self::formatValue($value)));
    }

    public static function notEquals(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:!=%s', self::field($field), self::formatValue($value)));
    }

    public static function matches(string $field, string $value): self
    {
        return new self(\sprintf('%s:%s', self::field($field), $value));
    }

    public static function greaterThan(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:>%s', self::field($field), self::formatValue($value)));
    }

    public static function greaterThanOrEqual(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:>=%s', self::field($field), self::formatValue($value)));
    }

    public static function lessThan(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:<%s', self::field($field), self::formatValue($value)));
    }

    public static function lessThanOrEqual(string $field, string|int|float|bool $value): self
    {
        return new self(\sprintf('%s:<=%s', self::field($field), self::formatValue($value)));
    }

    /**
     * @param array<string|int|float|bool> $values
     */
    public static function in(string $field, array $values): self
    {
        $formatted = implode(', ', array_map(self::formatValue(...), $values));

        return new self(\sprintf('%s:[%s]', self::field($field), $formatted));
    }

    /**
     * @param array<string|int|float|bool> $values
     */
    public static function notIn(string $field, array $values): self
    {
        $formatted = implode(', ', array_map(self::formatValue(...), $values));

        return new self(\sprintf('%s:!=[%s]', self::field($field), $formatted));
    }

    public static function between(string $field, int|float $min, int|float $max): self
    {
        return new self(\sprintf('%s:[%s..%s]', self::field($field), $min, $max));
    }

    public static function all(self ...$filters): self
    {
        return self::combine(' && ', $filters);
    }

    public static function any(self ...$filters): self
    {
        return self::combine(' || ', $filters);
    }

    public function __toString(): string
    {
        return $this->expression;
    }

    /**
     * Empty groups are dropped: spreading an empty list into all() or any()
     * would otherwise render "()", which Typesense rejects.
     *
     * @param array<self> $filters
     */
    private static function combine(string $operator, array $filters): self
    {
        $operands = array_values(array_filter($filters, static fn (self $filter): bool => '' !== $filter->expression));

        if (1 === \count($operands)) {
            return $operands[0];
        }

        return new self(implode($operator, array_map(self::operand(...), $operands)), [] !== $operands);
    }

    /**
     * Typesense gives && and || the same precedence and evaluates them left to
     * right, so a nested group must be parenthesised to keep its meaning.
     */
    private static function operand(self $filter): string
    {
        return $filter->isGroup ? \sprintf('(%s)', $filter->expression) : $filter->expression;
    }

    /**
     * Field names are not quoted in Typesense filters, so one carrying filter
     * syntax ("x:=1 || country") would inject its own clauses.
     */
    private static function field(string $field): string
    {
        if (1 !== preg_match('/^\w+(\.\w+)*$/', $field)) {
            throw new \InvalidArgumentException('Filter field names may only contain letters, digits, underscores and dots.');
        }

        return $field;
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
                throw new \InvalidArgumentException('Filter values cannot contain a backtick.');
            }

            return \sprintf('`%s`', $value);
        }

        return (string) $value;
    }
}
