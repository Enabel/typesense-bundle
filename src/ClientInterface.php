<?php

declare(strict_types=1);

namespace Enabel\Typesense;

interface ClientInterface
{
    /**
     * @param class-string $className
     */
    public function collection(string $className): CollectionInterface;

    /**
     * @param class-string $className
     */
    public function create(string $className): void;

    /**
     * @param class-string $className
     */
    public function drop(string $className): void;
}
