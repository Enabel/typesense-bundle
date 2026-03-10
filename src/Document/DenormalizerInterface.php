<?php

declare(strict_types=1);

namespace Enabel\Typesense\Document;

interface DenormalizerInterface
{
    /**
     * @param array<array<string, mixed>> $documents
     * @param class-string $className
     * @return array<?object>
     */
    public function denormalize(array $documents, string $className): array;
}
