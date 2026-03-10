<?php

declare(strict_types=1);

namespace Enabel\Typesense;

use Enabel\Typesense\Document\DenormalizerInterface;
use Enabel\Typesense\Document\DocumentNormalizerInterface;
use Enabel\Typesense\Exception\ImportException;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Search\Query;
use Enabel\Typesense\Search\Response\FacetCount;
use Enabel\Typesense\Search\Response\FacetStats;
use Enabel\Typesense\Search\Response\FacetValue;
use Enabel\Typesense\Search\Response\GroupedHit;
use Enabel\Typesense\Search\Response\GroupedResponse;
use Enabel\Typesense\Search\Response\Hit;
use Enabel\Typesense\Search\Response\Response;
use Typesense\Exceptions\ObjectNotFound;

final readonly class Collection implements CollectionInterface
{
    public function __construct(
        private DocumentMetadata $metadata,
        private \Typesense\Collection $typesenseCollection,
        private DenormalizerInterface $denormalizer,
        private DocumentNormalizerInterface $normalizer,
    ) {}

    public function search(Query $query): Response
    {
        $raw = $this->typesenseCollection->getDocuments()->search($query->toParams());

        $rawHits = $raw['hits'] ?? [];
        $documents = $this->denormalizer->denormalize(
            array_map(fn(array $hit) => $hit['document'], $rawHits),
            $this->metadata->className,
        );

        $hits = [];
        foreach ($rawHits as $i => $rawHit) {
            if ($documents[$i] !== null) {
                $hits[] = new Hit($documents[$i], $rawHit['text_match'] ?? 0, $rawHit);
            }
        }

        return new Response(
            found: $raw['found'],
            outOf: $raw['out_of'],
            page: $raw['page'],
            searchTimeMs: $raw['search_time_ms'],
            searchCutoff: $raw['search_cutoff'] ?? false,
            hits: $hits,
            facetCounts: $this->mapFacetCounts($raw['facet_counts'] ?? []),
            raw: $raw,
        );
    }

    public function searchGrouped(Query $query, string $groupBy, int $groupLimit = 3): GroupedResponse
    {
        $params = $query->toParams();
        $params['group_by'] = $groupBy;
        $params['group_limit'] = $groupLimit;

        $raw = $this->typesenseCollection->getDocuments()->search($params);

        $groupedHits = [];
        foreach ($raw['grouped_hits'] ?? [] as $rawGroup) {
            $rawHits = $rawGroup['hits'] ?? [];
            $documents = $this->denormalizer->denormalize(
                array_map(fn(array $hit) => $hit['document'], $rawHits),
                $this->metadata->className,
            );

            $hits = [];
            foreach ($rawHits as $i => $rawHit) {
                if ($documents[$i] !== null) {
                    $hits[] = new Hit($documents[$i], $rawHit['text_match'] ?? 0, $rawHit);
                }
            }

            $groupedHits[] = new GroupedHit(
                $rawGroup['group_key'],
                $hits,
                $rawGroup['found'],
                $rawGroup,
            );
        }

        return new GroupedResponse(
            found: $raw['found'],
            foundDocs: $raw['found_docs'] ?? 0,
            outOf: $raw['out_of'],
            page: $raw['page'],
            searchTimeMs: $raw['search_time_ms'],
            searchCutoff: $raw['search_cutoff'] ?? false,
            groupedHits: $groupedHits,
            facetCounts: $this->mapFacetCounts($raw['facet_counts'] ?? []),
            raw: $raw,
        );
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function searchRaw(array $params): array
    {
        return $this->typesenseCollection->getDocuments()->search($params);
    }

    public function find(string $id): ?object
    {
        try {
            $document = $this->typesenseCollection->getDocuments()[$id]->retrieve();
        } catch (ObjectNotFound) {
            return null;
        }

        $results = $this->denormalizer->denormalize([$document], $this->metadata->className);

        return $results[0] ?? null;
    }

    public function delete(string $id): void
    {
        $this->typesenseCollection->getDocuments()[$id]->delete();
    }

    public function upsert(object $document): void
    {
        $docs = $this->normalizer->normalize([$document]);
        $this->typesenseCollection->getDocuments()->upsert($docs[0]);
    }

    /**
     * @param object[] $documents
     * @throws ImportException
     */
    public function import(array $documents): void
    {
        $docs = $this->normalizer->normalize($documents);
        $results = $this->typesenseCollection->getDocuments()->import($docs, ['action' => 'upsert']);

        assert(is_array($results));

        $failures = array_filter($results, fn(array $r) => !$r['success']);
        if ($failures !== []) {
            throw new ImportException(array_values($failures));
        }
    }

    /**
     * @param array<array<string, mixed>> $rawFacetCounts
     * @return array<string, FacetCount>
     */
    private function mapFacetCounts(array $rawFacetCounts): array
    {
        $facetCounts = [];

        foreach ($rawFacetCounts as $rawFacet) {
            $counts = array_map(
                fn(array $c) => new FacetValue($c['value'], $c['count'], $c['highlighted'], $c),
                $rawFacet['counts'] ?? [],
            );

            $stats = null;
            if (isset($rawFacet['stats'])) {
                $s = $rawFacet['stats'];
                $stats = new FacetStats(
                    $s['total_values'],
                    $s['min'] ?? null,
                    $s['max'] ?? null,
                    $s['sum'] ?? null,
                    $s['avg'] ?? null,
                    $s,
                );
            }

            $fieldName = $rawFacet['field_name'];
            $facetCounts[$fieldName] = new FacetCount(
                $fieldName,
                $counts,
                $stats,
                $rawFacet['sampled'] ?? false,
                $rawFacet,
            );
        }

        return $facetCounts;
    }
}
