<?php

declare(strict_types=1);

namespace Enabel\Typesense\Document;

interface DocumentNormalizerInterface
{
    /**
     * @param object[] $objects
     * @return array<array<string, mixed>>
     */
    public function normalize(array $objects): array;
}
