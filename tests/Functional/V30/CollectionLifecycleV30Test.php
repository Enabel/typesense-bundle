<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Functional\V30;

use Enabel\Typesense\Tests\Functional\CollectionLifecycleTest;

final class CollectionLifecycleV30Test extends CollectionLifecycleTest
{
    protected function getTypesenseUrl(): string
    {
        return $_ENV['TYPESENSE_V30_URL'] ?? 'http://typesense-v30:8108';
    }
}
