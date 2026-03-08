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

namespace PapiAI\Core;

use Closure;
use PapiAI\Core\Attributes\Description;
use PapiAI\Core\Attributes\Tool as ToolAttribute;
use PapiAI\Core\Contracts\ToolInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

/**
 * Default implementation of ToolInterface for defining LLM-callable tools.
 *
 * Tools can be created via the static factory methods: make() for closure-based tools,
 * or fromClass() to automatically discover methods annotated with #[Tool] attributes.
 */
final class Tool implements ToolInterface
{
    private Closure $handler;

    /**
     * @param string $name Tool name
     * @param string $description Tool description
     * @param array $parameters JSON schema for parameters
     * @param Closure $handler The function to execute
     */
    private function __construct(
        private readonly string $name,
        private readonly string $description,
        private readonly array $parameters,
        Closure $handler,
    ) {
        $this->handler = $handler;
    }

    /**
     * Create a tool from a closure with explicit parameter definitions.
     *
     * @param string $name Tool name (snake_case)
     * @param string $description Description for the LLM
     * @param array<string, array{type?: string, description?: string, enum?: array, default?: mixed}> $parameters Parameter definitions
     * @param Closure $handler The handler function
     *
     * @return self A configured tool instance
     */
    public static function make(
        string $name,
        string $description,
        array $parameters,
        Closure $handler,
    ): self {
        // Convert simple parameter format to JSON schema
        $properties = [];
        $required = [];

        foreach ($parameters as $paramName => $config) {
            $prop = ['type' => $config['type'] ?? 'string'];

            if (isset($config['description'])) {
                $prop['description'] = $config['description'];
            }

            if (isset($config['enum'])) {
                $prop['enum'] = $config['enum'];
            }

            if (isset($config['default'])) {
                $prop['default'] = $config['default'];
            } else {
                $required[] = $paramName;
            }

            $properties[$paramName] = $prop;
        }

        $schema = [
            'type' => 'object',
            'properties' => $properties,
        ];

        if (!empty($required)) {
            $schema['required'] = $required;
        }

        return new self($name, $description, $schema, $handler);
    }

    /**
     * Create tools from a class with #[Tool] attributes.
     *
     * @param class-string|object $class The class or instance
     * @return array<Tool>
     */
    public static function fromClass(string|object $class): array
    {
        $instance = is_object($class) ? $class : null;
        $className = is_object($class) ? get_class($class) : $class;
        $reflection = new ReflectionClass($className);
        $tools = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(ToolAttribute::class);

            if (empty($attributes)) {
                continue;
            }

            $attr = $attributes[0]->newInstance();
            $toolName = $attr->name ?? self::toSnakeCase($method->getName());

            // Build parameter schema from method signature
            $properties = [];
            $required = [];
            $contextParam = null;

            foreach ($method->getParameters() as $param) {
                $paramName = $param->getName();
                $paramType = $param->getType();

                // Check if this is a context parameter (non-scalar type)
                if ($paramType instanceof ReflectionNamedType) {
                    $typeName = $paramType->getName();
                    if (!in_array($typeName, ['string', 'int', 'float', 'bool', 'array'], true)) {
                        $contextParam = $paramName;
                        continue;
                    }
                }

                $prop = self::parameterToSchema($param);
                $properties[$paramName] = $prop;

                if (!$param->isOptional()) {
                    $required[] = $paramName;
                }
            }

            $schema = [
                'type' => 'object',
                'properties' => $properties,
            ];

            if (!empty($required)) {
                $schema['required'] = $required;
            }

            // Create handler that properly passes context
            $handler = function (array $arguments, mixed $context = null) use ($method, $instance, $className, $contextParam) {
                $obj = $instance ?? new $className();
                $args = [];

                foreach ($method->getParameters() as $param) {
                    $paramName = $param->getName();

                    if ($paramName === $contextParam && $context !== null) {
                        $args[] = $context;
                    } elseif (isset($arguments[$paramName])) {
                        $args[] = $arguments[$paramName];
                    } elseif ($param->isOptional()) {
                        $args[] = $param->getDefaultValue();
                    }
                }

                return $method->invokeArgs($obj, $args);
            };

            $tools[] = new self($toolName, $attr->description, $schema, Closure::fromCallable($handler));
        }

        return $tools;
    }

    /** {@inheritDoc} */
    public function getName(): string
    {
        return $this->name;
    }

    /** {@inheritDoc} */
    public function getDescription(): string
    {
        return $this->description;
    }

    /** {@inheritDoc} */
    public function getParameterSchema(): array
    {
        return $this->parameters;
    }

    /** {@inheritDoc} */
    public function execute(array $arguments, mixed $context = null): mixed
    {
        return ($this->handler)($arguments, $context);
    }

    /**
     * Convert to format expected by Anthropic API.
     */
    public function toAnthropic(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'input_schema' => $this->parameters,
        ];
    }

    /**
     * Convert to format expected by OpenAI API.
     */
    public function toOpenAI(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->name,
                'description' => $this->description,
                'parameters' => $this->parameters,
            ],
        ];
    }

    /**
     * Convert method parameter to JSON schema.
     */
    private static function parameterToSchema(ReflectionParameter $param): array
    {
        $type = $param->getType();
        $schema = [];

        // Get description from attribute
        $descAttrs = $param->getAttributes(Description::class);
        if (!empty($descAttrs)) {
            $schema['description'] = $descAttrs[0]->newInstance()->text;
        }

        if ($type instanceof ReflectionNamedType) {
            $typeName = $type->getName();

            $schema['type'] = match ($typeName) {
                'string' => 'string',
                'int' => 'integer',
                'float' => 'number',
                'bool' => 'boolean',
                'array' => 'array',
                default => 'string',
            };

            if ($type->allowsNull()) {
                $schema['type'] = [$schema['type'], 'null'];
            }
        } else {
            $schema['type'] = 'string';
        }

        if ($param->isOptional() && $param->isDefaultValueAvailable()) {
            $schema['default'] = $param->getDefaultValue();
        }

        return $schema;
    }

    /**
     * Convert camelCase to snake_case.
     */
    private static function toSnakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
