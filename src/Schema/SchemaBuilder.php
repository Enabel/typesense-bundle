<?php

declare(strict_types=1);

namespace Enabel\Typesense\Schema;

use Enabel\Typesense\Mapping\Infix;
use Enabel\Typesense\Metadata\DocumentMetadata;
use Enabel\Typesense\Metadata\FieldMetadata;

final readonly class SchemaBuilder implements SchemaBuilderInterface
{
    /**
     * @return array{name: string, fields: list<array<string, mixed>>, default_sorting_field?: string}
     */
    public function build(DocumentMetadata $metadata): array
    {
        $schema = [
            'name' => $metadata->collection,
            'fields' => array_values(array_map($this->buildField(...), $metadata->fields)),
        ];

        if ($metadata->defaultSortingField !== null) {
            $schema['default_sorting_field'] = $metadata->defaultSortingField;
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildField(FieldMetadata $field): array
    {
        $schema = [
            'name' => $field->propertyName,
            'type' => $field->type->name,
        ];

        if ($field->facet) {
            $schema['facet'] = true;
        }

        if ($field->sort) {
            $schema['sort'] = true;
        }

        if (!$field->index) {
            $schema['index'] = false;
        }

        if (!$field->store) {
            $schema['store'] = false;
        }

        if ($field->optional) {
            $schema['optional'] = true;
        }

        if ($field->infix !== Infix::Off) {
            $schema['infix'] = true;
        }

        return $schema;
    }
}
