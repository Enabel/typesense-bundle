<?php

declare(strict_types=1);

namespace Enabel\Typesense\Exception;

final class ImportException extends \RuntimeException
{
    /**
     * @param array<array{success: bool, error: string, document: string}> $failures
     */
    public function __construct(
        public readonly array $failures,
    ) {
        parent::__construct(\sprintf('%d document(s) failed to import', \count($failures)));
    }
}
