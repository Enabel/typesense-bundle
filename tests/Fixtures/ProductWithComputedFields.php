<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Fixtures;

use Enabel\Typesense\Mapping\Document;
use Enabel\Typesense\Mapping\Field;
use Enabel\Typesense\Mapping\Id;
use Enabel\Typesense\Type\StringType;

#[Document(collection: 'products_computed')]
class ProductWithComputedFields
{
    #[Id]
    public int $id;

    #[Field]
    public string $title;

    #[Field]
    public string $subtitle;

    #[Field(name: 'product_category')]
    public string $category;

    #[Field(denormalize: false)]
    public string $internalCode;

    #[Field]
    public string $fullTitle {
        get => $this->title . ' - ' . $this->subtitle;
    }

    #[Field(type: new StringType(array: true))]
    public function searchKeywords(): array
    {
        return [$this->title, $this->category];
    }
}
