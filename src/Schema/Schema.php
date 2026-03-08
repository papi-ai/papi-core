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

use InvalidArgumentException;

/**
 * Zod-like schema builder for PHP.
 *
 * Usage:
 *   $schema = Schema::object([
 *       'name' => Schema::string()->description('The name'),
 *       'age' => Schema::integer()->min(0)->max(150),
 *       'tags' => Schema::array(Schema::string()),
 *   ]);
 */
class Schema
{
    protected SchemaType $type;
    protected ?string $description = null;
    protected bool $nullable = false;
    protected bool $optional = false;
    protected mixed $default = null;
    protected bool $hasDefault = false;

    // String constraints
    protected ?int $minLength = null;
    protected ?int $maxLength = null;
    protected ?string $pattern = null;

    // Number constraints
    protected int|float|null $min = null;
    protected int|float|null $max = null;

    // Array constraints
    protected ?Schema $items = null;
    protected ?int $minItems = null;
    protected ?int $maxItems = null;

    // Object constraints
    protected ?array $properties = null;
    protected array $required = [];

    // Enum values
    protected ?array $enumValues = null;

    protected function __construct(SchemaType $type)
    {
        $this->type = $type;
    }

    /**
     * Create a string schema.
     *
     * @return self A new string-typed schema instance
     */
    public static function string(): self
    {
        return new self(SchemaType::String);
    }

    /**
     * Create a number schema (float).
     *
     * @return self A new number-typed schema instance
     */
    public static function number(): self
    {
        return new self(SchemaType::Number);
    }

    /**
     * Create an integer schema.
     *
     * @return self A new integer-typed schema instance
     */
    public static function integer(): self
    {
        return new self(SchemaType::Integer);
    }

    /**
     * Create a boolean schema.
     *
     * @return self A new boolean-typed schema instance
     */
    public static function boolean(): self
    {
        return new self(SchemaType::Boolean);
    }

    /**
     * Create an array schema.
     *
     * @param Schema $items Schema for array items
     *
     * @return self A new array-typed schema with item validation
     */
    public static function array(Schema $items): self
    {
        $schema = new self(SchemaType::Array);
        $schema->items = $items;

        return $schema;
    }

    /**
     * Create an object schema.
     *
     * @param array<string, Schema> $properties Object properties (non-optional ones are required by default)
     *
     * @return self A new object-typed schema with property validation
     */
    public static function object(array $properties): self
    {
        $schema = new self(SchemaType::Object);
        $schema->properties = $properties;

        // By default, all properties are required unless marked optional
        foreach ($properties as $name => $prop) {
            if (!$prop->optional) {
                $schema->required[] = $name;
            }
        }

        return $schema;
    }

    /**
     * Create an enum schema.
     *
     * @param array<string|int> $values Allowed values
     *
     * @return self A new enum-typed schema constraining values to the given set
     */
    public static function enum(array $values): self
    {
        $schema = new self(SchemaType::Enum);
        $schema->enumValues = $values;

        return $schema;
    }

    /**
     * Create a null schema.
     *
     * @return self A new null-typed schema
     */
    public static function null(): self
    {
        return new self(SchemaType::Null);
    }

    /**
     * Add a description to help LLMs understand the field's purpose.
     *
     * @param string $description Human-readable description
     *
     * @return self For method chaining
     */
    public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Allow null as a valid value for this schema.
     *
     * @return self For method chaining
     */
    public function nullable(): self
    {
        $this->nullable = true;

        return $this;
    }

    /**
     * Mark this property as optional (excluded from the required list in object schemas).
     *
     * @return self For method chaining
     */
    public function optional(): self
    {
        $this->optional = true;

        return $this;
    }

    /**
     * Set a default value used when the property is absent.
     *
     * @param mixed $value The default value
     *
     * @return self For method chaining
     */
    public function default(mixed $value): self
    {
        $this->default = $value;
        $this->hasDefault = true;

        return $this;
    }

    /**
     * Set minimum length (strings) or value (numbers).
     *
     * @param int|float $value Minimum length for strings, minimum value for numbers
     *
     * @return self For method chaining
     */
    public function min(int|float $value): self
    {
        if ($this->type === SchemaType::String) {
            $this->minLength = (int) $value;
        } else {
            $this->min = $value;
        }

        return $this;
    }

    /**
     * Set maximum length (strings) or value (numbers).
     *
     * @param int|float $value Maximum length for strings, maximum value for numbers
     *
     * @return self For method chaining
     */
    public function max(int|float $value): self
    {
        if ($this->type === SchemaType::String) {
            $this->maxLength = (int) $value;
        } else {
            $this->max = $value;
        }

        return $this;
    }

    /**
     * Set a regex pattern constraint for string values.
     *
     * @param string $pattern A valid regular expression pattern
     *
     * @return self For method chaining
     */
    public function pattern(string $pattern): self
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Set the minimum number of items for array schemas.
     *
     * @param int $count Minimum item count
     *
     * @return self For method chaining
     */
    public function minItems(int $count): self
    {
        $this->minItems = $count;

        return $this;
    }

    /**
     * Set the maximum number of items for array schemas.
     *
     * @param int $count Maximum item count
     *
     * @return self For method chaining
     */
    public function maxItems(int $count): self
    {
        $this->maxItems = $count;

        return $this;
    }

    /**
     * Get the schema type.
     *
     * @return SchemaType The primitive type of this schema
     */
    public function getType(): SchemaType
    {
        return $this->type;
    }

    /**
     * Check if this schema is optional.
     *
     * @return bool True if this property is not required in its parent object
     */
    public function isOptional(): bool
    {
        return $this->optional;
    }

    /**
     * Convert to a standard JSON Schema array for use with LLM APIs.
     *
     * @return array<string, mixed> A JSON Schema-compliant representation
     */
    public function toJsonSchema(): array
    {
        $schema = [];

        // Handle enum specially
        if ($this->type === SchemaType::Enum) {
            $schema['type'] = 'string';
            $schema['enum'] = $this->enumValues;
        } else {
            $schema['type'] = $this->type->value;
        }

        if ($this->description !== null) {
            $schema['description'] = $this->description;
        }

        if ($this->nullable && $this->type !== SchemaType::Null) {
            $schema['type'] = [$this->type->value, 'null'];
        }

        if ($this->hasDefault) {
            $schema['default'] = $this->default;
        }

        // String constraints
        if ($this->minLength !== null) {
            $schema['minLength'] = $this->minLength;
        }
        if ($this->maxLength !== null) {
            $schema['maxLength'] = $this->maxLength;
        }
        if ($this->pattern !== null) {
            $schema['pattern'] = $this->pattern;
        }

        // Number constraints
        if ($this->min !== null) {
            $schema['minimum'] = $this->min;
        }
        if ($this->max !== null) {
            $schema['maximum'] = $this->max;
        }

        // Array constraints
        if ($this->type === SchemaType::Array) {
            $schema['items'] = $this->items->toJsonSchema();
            if ($this->minItems !== null) {
                $schema['minItems'] = $this->minItems;
            }
            if ($this->maxItems !== null) {
                $schema['maxItems'] = $this->maxItems;
            }
        }

        // Object constraints
        if ($this->type === SchemaType::Object) {
            $schema['properties'] = [];
            foreach ($this->properties as $name => $prop) {
                $schema['properties'][$name] = $prop->toJsonSchema();
            }
            if (!empty($this->required)) {
                $schema['required'] = $this->required;
            }
            $schema['additionalProperties'] = false;
        }

        return $schema;
    }

    /**
     * Validate a value against this schema.
     *
     * @param mixed $value The value to validate
     * @param string $path The path for error messages
     * @return array{valid: bool, errors: array<string>}
     */
    public function validate(mixed $value, string $path = '$'): array
    {
        $errors = [];

        // Handle null
        if ($value === null) {
            if ($this->nullable || $this->optional) {
                return ['valid' => true, 'errors' => []];
            }
            $errors[] = "{$path}: Value cannot be null";

            return ['valid' => false, 'errors' => $errors];
        }

        // Type validation
        switch ($this->type) {
            case SchemaType::String:
                if (!is_string($value)) {
                    $errors[] = "{$path}: Expected string, got " . gettype($value);
                } else {
                    if ($this->minLength !== null && strlen($value) < $this->minLength) {
                        $errors[] = "{$path}: String length must be at least {$this->minLength}";
                    }
                    if ($this->maxLength !== null && strlen($value) > $this->maxLength) {
                        $errors[] = "{$path}: String length must be at most {$this->maxLength}";
                    }
                    if ($this->pattern !== null && !preg_match($this->pattern, $value)) {
                        $errors[] = "{$path}: String must match pattern {$this->pattern}";
                    }
                }
                break;

            case SchemaType::Number:
                if (!is_int($value) && !is_float($value)) {
                    $errors[] = "{$path}: Expected number, got " . gettype($value);
                } else {
                    if ($this->min !== null && $value < $this->min) {
                        $errors[] = "{$path}: Value must be at least {$this->min}";
                    }
                    if ($this->max !== null && $value > $this->max) {
                        $errors[] = "{$path}: Value must be at most {$this->max}";
                    }
                }
                break;

            case SchemaType::Integer:
                if (!is_int($value)) {
                    $errors[] = "{$path}: Expected integer, got " . gettype($value);
                } else {
                    if ($this->min !== null && $value < $this->min) {
                        $errors[] = "{$path}: Value must be at least {$this->min}";
                    }
                    if ($this->max !== null && $value > $this->max) {
                        $errors[] = "{$path}: Value must be at most {$this->max}";
                    }
                }
                break;

            case SchemaType::Boolean:
                if (!is_bool($value)) {
                    $errors[] = "{$path}: Expected boolean, got " . gettype($value);
                }
                break;

            case SchemaType::Array:
                if (!is_array($value) || !array_is_list($value)) {
                    $errors[] = "{$path}: Expected array, got " . gettype($value);
                } else {
                    if ($this->minItems !== null && count($value) < $this->minItems) {
                        $errors[] = "{$path}: Array must have at least {$this->minItems} items";
                    }
                    if ($this->maxItems !== null && count($value) > $this->maxItems) {
                        $errors[] = "{$path}: Array must have at most {$this->maxItems} items";
                    }
                    foreach ($value as $i => $item) {
                        $result = $this->items->validate($item, "{$path}[{$i}]");
                        $errors = array_merge($errors, $result['errors']);
                    }
                }
                break;

            case SchemaType::Object:
                if (!is_array($value) || array_is_list($value)) {
                    $errors[] = "{$path}: Expected object, got " . gettype($value);
                } else {
                    // Check required properties
                    foreach ($this->required as $prop) {
                        if (!array_key_exists($prop, $value)) {
                            $errors[] = "{$path}.{$prop}: Required property is missing";
                        }
                    }
                    // Validate each property
                    foreach ($this->properties as $name => $schema) {
                        if (array_key_exists($name, $value)) {
                            $result = $schema->validate($value[$name], "{$path}.{$name}");
                            $errors = array_merge($errors, $result['errors']);
                        }
                    }
                }
                break;

            case SchemaType::Enum:
                if (!in_array($value, $this->enumValues, true)) {
                    $allowed = implode(', ', array_map(fn ($v) => json_encode($v), $this->enumValues));
                    $errors[] = "{$path}: Value must be one of: {$allowed}";
                }
                break;

            case SchemaType::Null:
                $errors[] = "{$path}: Expected null, got " . gettype($value);
                break;
        }

        return ['valid' => empty($errors), 'errors' => $errors];
    }

    /**
     * Parse and validate a value, throwing on error.
     *
     * @param mixed $value The value to validate and return
     *
     * @return mixed The validated value (unchanged)
     *
     * @throws InvalidArgumentException If validation fails
     */
    public function parse(mixed $value): mixed
    {
        $result = $this->validate($value);

        if (!$result['valid']) {
            throw new InvalidArgumentException(
                "Schema validation failed:\n" . implode("\n", $result['errors'])
            );
        }

        return $value;
    }

    /**
     * Try to parse a value without throwing, returning null on validation failure.
     *
     * @param mixed $value The value to validate
     *
     * @return array{data: mixed}|null Array with validated data, or null if invalid
     */
    public function safeParse(mixed $value): ?array
    {
        $result = $this->validate($value);

        if (!$result['valid']) {
            return null;
        }

        return ['data' => $value];
    }
}
