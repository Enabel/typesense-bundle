<?php

declare(strict_types=1);

namespace Enabel\Typesense\Type;

/** @extends AbstractType<\DateTimeInterface, int> */
final class DateTimeType extends AbstractType
{
    public function __construct(bool $array = false)
    {
        parent::__construct('int64', $array);
    }

    protected function normalizeValue(mixed $value): int
    {
        /** @var \DateTimeInterface $value */
        return $value->getTimestamp();
    }

    protected function denormalizeValue(string|int|float|bool $value): \DateTimeImmutable
    {
        $result = \DateTimeImmutable::createFromFormat('U', (string) $value);
        assert($result instanceof \DateTimeImmutable);

        return $result;
    }

    public static function cast(mixed $value): int
    {
        /** @var \DateTimeInterface $value */
        return $value->getTimestamp();
    }
}
