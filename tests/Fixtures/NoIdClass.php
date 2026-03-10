<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Fixtures;

use Enabel\Typesense\Mapping\Document;
use Enabel\Typesense\Mapping\Field;

#[Document(collection: 'no_id')]
class NoIdClass
{
    #[Field]
    public string $title;
}
