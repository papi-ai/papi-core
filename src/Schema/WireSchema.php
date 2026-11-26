<?php

/*
 * This file is part of PapiAI,
 * A simple but powerful PHP library for building AI agents.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace PapiAI\Core\Schema;

use stdClass;

/**
 * A JSON Schema held as a PHP array, in the form that encodes to the JSON it means.
 *
 * An empty PHP array encodes as a list, so `"properties": {}` would leave as `"properties": []`
 * and a provider would refuse the request. Here every empty map is an object, and every object
 * schema states its properties, even when it has none.
 */
final class WireSchema
{
    private const SCHEMAS_BY_NAME = ['properties', '$defs', 'definitions', 'patternProperties', 'dependentSchemas'];

    private const SCHEMA_LISTS = ['anyOf', 'allOf', 'oneOf', 'prefixItems'];

    private const SCHEMAS = ['items', 'additionalProperties', 'contains', 'propertyNames', 'not', 'if', 'then', 'else'];

    /**
     * @param array<string, mixed> $schema
     */
    public function __construct(
        private readonly array $schema,
    ) {
    }

    /**
     * @return array<string, mixed> The schema, ready for `json_encode`
     */
    public function toArray(): array
    {
        $schema = $this->schema;

        foreach (self::SCHEMAS_BY_NAME as $keyword) {
            if (is_array($schema[$keyword] ?? null)) {
                $schema[$keyword] = $this->orEmptyObject(array_map($this->nested(...), $schema[$keyword]));
            }
        }

        foreach (self::SCHEMA_LISTS as $keyword) {
            if (is_array($schema[$keyword] ?? null)) {
                $schema[$keyword] = array_map($this->nested(...), $schema[$keyword]);
            }
        }

        foreach (self::SCHEMAS as $keyword) {
            if (is_array($schema[$keyword] ?? null)) {
                $schema[$keyword] = $this->nested($schema[$keyword]);
            }
        }

        if ($this->describesAnObject($schema) && !isset($schema['properties'])) {
            $schema['properties'] = new stdClass();
        }

        return $schema;
    }

    private function nested(mixed $schema): mixed
    {
        return is_array($schema) ? $this->orEmptyObject((new self($schema))->toArray()) : $schema;
    }

    /**
     * @param array<array-key, mixed> $map
     *
     * @return array<array-key, mixed>|stdClass
     */
    private function orEmptyObject(array $map): array|stdClass
    {
        return $map === [] ? new stdClass() : $map;
    }

    /**
     * @param array<string, mixed> $schema
     */
    private function describesAnObject(array $schema): bool
    {
        $type = $schema['type'] ?? null;

        return $type === 'object' || (is_array($type) && in_array('object', $type, true));
    }
}
