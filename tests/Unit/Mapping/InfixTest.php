<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Mapping;

use Enabel\Typesense\Mapping\Infix;
use PHPUnit\Framework\TestCase;

final class InfixTest extends TestCase
{
    public function testItMapsToExpectedStringValues(): void
    {
        self::assertSame('off', Infix::Off->value);
        self::assertSame('always', Infix::Always->value);
        self::assertSame('fallback', Infix::Fallback->value);
    }
}
