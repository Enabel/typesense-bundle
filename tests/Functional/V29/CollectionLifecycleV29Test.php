<?php

declare(strict_types=1);

namespace Enabel\Typesense\Tests\Functional\V29;

use Enabel\Typesense\Tests\Functional\CollectionLifecycleTest;

final class CollectionLifecycleV29Test extends CollectionLifecycleTest
{
    protected function getTypesenseUrl(): string
    {
        return $_ENV['TYPESENSE_V29_URL'] ?? 'http://typesense-v29:8108';
    }
}
