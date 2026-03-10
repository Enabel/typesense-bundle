<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Fixtures;

use Enabel\Typesense\Mapping\Document;
use Enabel\Typesense\Mapping\Field;
use Enabel\Typesense\Mapping\Id;
use Enabel\Typesense\Mapping\Infix;
use Enabel\Typesense\Type\StringType;

#[Document(collection: 'functional_products', defaultSortingField: 'popularity')]
class FunctionalProduct
{
    #[Id]
    public int $id;

    #[Field(facet: true)]
    public string $title;

    #[Field(sort: true)]
    public float $price;

    #[Field]
    public bool $inStock;

    #[Field(type: new StringType(array: true), facet: true)]
    public array $tags;

    #[Field(sort: true)]
    public int $popularity;

    #[Field(infix: Infix::Always)]
    public string $description;

    #[Field]
    public ?string $subtitle;

    #[Field]
    public \DateTimeImmutable $createdAt;

    #[Field(facet: true)]
    public StringStatus $status;
}
