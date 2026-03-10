<?php

declare(strict_types=1);

namespace Enabel\Typesense\Document;

interface DataProviderInterface
{
    /**
     * @param class-string $className
     * @return iterable<object>
     */
    public function provide(string $className): iterable;
}
