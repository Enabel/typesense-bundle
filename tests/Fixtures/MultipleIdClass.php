<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Fixtures;

use Enabel\Typesense\Mapping\Document;
use Enabel\Typesense\Mapping\Id;

#[Document(collection: 'multi_id')]
class MultipleIdClass
{
    #[Id]
    public int $id;

    #[Id]
    public int $otherId;
}
