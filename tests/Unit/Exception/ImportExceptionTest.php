<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Unit\Exception;

use Enabel\Typesense\Exception\ImportException;
use PHPUnit\Framework\TestCase;

final class ImportExceptionTest extends TestCase
{
    public function testItReportsTheNumberOfFailedDocuments(): void
    {
        $failures = [
            ['success' => false, 'error' => 'Bad value', 'document' => '{"id":"1"}'],
            ['success' => false, 'error' => 'Missing field', 'document' => '{"id":"2"}'],
        ];

        $exception = new ImportException($failures);

        self::assertSame('2 document(s) failed to import', $exception->getMessage());
        self::assertSame($failures, $exception->failures);
        self::assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testItHandlesASingleFailure(): void
    {
        $failures = [
            ['success' => false, 'error' => 'Bad value', 'document' => '{"id":"1"}'],
        ];

        $exception = new ImportException($failures);

        self::assertSame('1 document(s) failed to import', $exception->getMessage());
    }
}
