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

use PapiAI\Core\Tool;
use PapiAI\Core\Attributes\Tool as ToolAttribute;
use PapiAI\Core\Attributes\Description;

describe('Tool', function () {
    describe('make', function () {
        it('creates a tool with basic parameters', function () {
            $tool = Tool::make(
                name: 'greet',
                description: 'Greet a person',
                parameters: [
                    'name' => ['type' => 'string', 'description' => 'Name to greet'],
                ],
                handler: fn(array $args) => "Hello, {$args['name']}!",
            );

            expect($tool->getName())->toBe('greet');
            expect($tool->getDescription())->toBe('Greet a person');
        });

        it('executes the handler', function () {
            $tool = Tool::make(
                name: 'add',
                description: 'Add two numbers',
                parameters: [
                    'a' => ['type' => 'number'],
                    'b' => ['type' => 'number'],
                ],
                handler: fn(array $args) => $args['a'] + $args['b'],
            );

            $result = $tool->execute(['a' => 5, 'b' => 3]);
            expect($result)->toBe(8);
        });

        it('generates correct parameter schema', function () {
            $tool = Tool::make(
                name: 'test',
                description: 'Test tool',
                parameters: [
                    'required_param' => ['type' => 'string'],
                    'optional_param' => ['type' => 'string', 'default' => 'default'],
                ],
                handler: fn() => null,
            );

            $schema = $tool->getParameterSchema();

            expect($schema['type'])->toBe('object');
            expect($schema['properties'])->toHaveKey('required_param');
            expect($schema['required'])->toContain('required_param');
            expect($schema['required'])->not->toContain('optional_param');
        });
    });

    describe('toAnthropic', function () {
        it('formats tool for Anthropic API', function () {
            $tool = Tool::make(
                name: 'get_weather',
                description: 'Get weather for a city',
                parameters: [
                    'city' => ['type' => 'string'],
                ],
                handler: fn() => null,
            );

            $formatted = $tool->toAnthropic();

            expect($formatted['name'])->toBe('get_weather');
            expect($formatted['description'])->toBe('Get weather for a city');
            expect($formatted['input_schema']['type'])->toBe('object');
        });
    });

    describe('toOpenAI', function () {
        it('formats tool for OpenAI API', function () {
            $tool = Tool::make(
                name: 'get_weather',
                description: 'Get weather for a city',
                parameters: [
                    'city' => ['type' => 'string'],
                ],
                handler: fn() => null,
            );

            $formatted = $tool->toOpenAI();

            expect($formatted['type'])->toBe('function');
            expect($formatted['function']['name'])->toBe('get_weather');
            expect($formatted['function']['description'])->toBe('Get weather for a city');
        });
    });

    describe('fromClass', function () {
        it('creates tools from class methods with attributes', function () {
            $tools = Tool::fromClass(TestToolClass::class);

            expect($tools)->toHaveCount(2);

            $greet = array_values(array_filter($tools, fn($t) => $t->getName() === 'greet'))[0] ?? null;
            expect($greet)->not->toBeNull();
            expect($greet->getDescription())->toBe('Greet someone');
        });

        it('generates parameter schema from type hints', function () {
            $tools = Tool::fromClass(TestToolClass::class);
            $greet = array_values(array_filter($tools, fn($t) => $t->getName() === 'greet'))[0];

            $schema = $greet->getParameterSchema();

            expect($schema['properties']['name']['type'])->toBe('string');
            expect($schema['required'])->toContain('name');
        });

        it('handles optional parameters', function () {
            $tools = Tool::fromClass(TestToolClass::class);
            $greet = array_values(array_filter($tools, fn($t) => $t->getName() === 'greet'))[0];

            $schema = $greet->getParameterSchema();

            expect($schema['properties']['greeting']['default'])->toBe('Hello');
            expect($schema['required'])->not->toContain('greeting');
        });

        it('includes parameter descriptions from attributes', function () {
            $tools = Tool::fromClass(TestToolClass::class);
            $greet = array_values(array_filter($tools, fn($t) => $t->getName() === 'greet'))[0];

            $schema = $greet->getParameterSchema();

            expect($schema['properties']['name']['description'])->toBe('The name to greet');
        });

        it('executes class methods', function () {
            $tools = Tool::fromClass(new TestToolClass());
            $add = array_values(array_filter($tools, fn($t) => $t->getName() === 'add_numbers'))[0];

            $result = $add->execute(['a' => 10, 'b' => 5]);
            expect($result)->toBe(15);
        });
    });
});

// Test fixture class
class TestToolClass
{
    #[ToolAttribute('Greet someone')]
    public function greet(
        #[Description('The name to greet')] string $name,
        string $greeting = 'Hello',
    ): string {
        return "{$greeting}, {$name}!";
    }

    #[ToolAttribute('Add two numbers')]
    public function addNumbers(int $a, int $b): int
    {
        return $a + $b;
    }

    // This method should NOT be included (no attribute)
    public function helperMethod(): void
    {
    }
}
