<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Fixtures;

use Enabel\Typesense\Mapping\Document;
use Enabel\Typesense\Mapping\Field;
use Enabel\Typesense\Mapping\Id;

#[Document(collection: 'no_type_decl')]
class NoTypeDeclarationClass
{
    #[Id]
    public int $id;

    /** @var string */
    #[Field]
    public $title;
}
