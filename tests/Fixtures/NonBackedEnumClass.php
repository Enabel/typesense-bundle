<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Fixtures;

use Enabel\Typesense\Mapping\Document;
use Enabel\Typesense\Mapping\Field;
use Enabel\Typesense\Mapping\Id;

#[Document(collection: 'non_backed_enum')]
class NonBackedEnumClass
{
    #[Id]
    public int $id;

    #[Field]
    public NonBackedEnumStatus $status;
}
