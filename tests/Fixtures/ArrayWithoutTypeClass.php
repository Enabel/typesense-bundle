<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Fixtures;

use Enabel\Typesense\Mapping\Document;
use Enabel\Typesense\Mapping\Field;
use Enabel\Typesense\Mapping\Id;

#[Document(collection: 'array_no_type')]
class ArrayWithoutTypeClass
{
    #[Id]
    public int $id;

    #[Field]
    public array $tags;
}
