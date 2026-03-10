<?php

declare(strict_types=1);

namespace Enabel\Typesense;

use Enabel\Typesense\Exception\ImportException;
use Enabel\Typesense\Search\Query;
use Enabel\Typesense\Search\Response\GroupedResponse;
use Enabel\Typesense\Search\Response\Response;

interface CollectionInterface
{
    public function search(Query $query): Response;

    public function searchGrouped(Query $query, string $groupBy, int $groupLimit = 3): GroupedResponse;

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function searchRaw(array $params): array;

    public function find(string $id): ?object;

    public function delete(string $id): void;

    public function upsert(object $document): void;

    /**
     * @param object[] $documents
     * @throws ImportException
     */
    public function import(array $documents): void;
}
