<?php

declare(strict_types=1);

use PapiAI\Core\Schema\Schema;
use PapiAI\Core\Schema\SchemaType;

describe('Schema', function () {
    describe('string schema', function () {
        it('validates strings', function () {
            $schema = Schema::string();

            $result = $schema->validate('hello');
            expect($result['valid'])->toBeTrue();
            expect($result['errors'])->toBeEmpty();
        });

        it('rejects non-strings', function () {
            $schema = Schema::string();

            $result = $schema->validate(123);
            expect($result['valid'])->toBeFalse();
            expect($result['errors'])->toHaveCount(1);
        });

        it('validates min length', function () {
            $schema = Schema::string()->min(5);

            expect($schema->validate('hello')['valid'])->toBeTrue();
            expect($schema->validate('hi')['valid'])->toBeFalse();
        });

        it('validates max length', function () {
            $schema = Schema::string()->max(5);

            expect($schema->validate('hello')['valid'])->toBeTrue();
            expect($schema->validate('hello world')['valid'])->toBeFalse();
        });

        it('validates pattern', function () {
            $schema = Schema::string()->pattern('/^[a-z]+$/');

            expect($schema->validate('hello')['valid'])->toBeTrue();
            expect($schema->validate('Hello123')['valid'])->toBeFalse();
        });

        it('supports description', function () {
            $schema = Schema::string()->description('A test string');
            $jsonSchema = $schema->toJsonSchema();

            expect($jsonSchema['description'])->toBe('A test string');
        });
    });

    describe('number schema', function () {
        it('validates numbers', function () {
            $schema = Schema::number();

            expect($schema->validate(42)['valid'])->toBeTrue();
            expect($schema->validate(3.14)['valid'])->toBeTrue();
            expect($schema->validate('42')['valid'])->toBeFalse();
        });

        it('validates min value', function () {
            $schema = Schema::number()->min(0);

            expect($schema->validate(5)['valid'])->toBeTrue();
            expect($schema->validate(-1)['valid'])->toBeFalse();
        });

        it('validates max value', function () {
            $schema = Schema::number()->max(100);

            expect($schema->validate(50)['valid'])->toBeTrue();
            expect($schema->validate(150)['valid'])->toBeFalse();
        });
    });

    describe('integer schema', function () {
        it('validates integers', function () {
            $schema = Schema::integer();

            expect($schema->validate(42)['valid'])->toBeTrue();
            expect($schema->validate(3.14)['valid'])->toBeFalse();
        });
    });

    describe('boolean schema', function () {
        it('validates booleans', function () {
            $schema = Schema::boolean();

            expect($schema->validate(true)['valid'])->toBeTrue();
            expect($schema->validate(false)['valid'])->toBeTrue();
            expect($schema->validate(1)['valid'])->toBeFalse();
            expect($schema->validate('true')['valid'])->toBeFalse();
        });
    });

    describe('array schema', function () {
        it('validates arrays', function () {
            $schema = Schema::array(Schema::string());

            expect($schema->validate(['a', 'b', 'c'])['valid'])->toBeTrue();
            expect($schema->validate([1, 2, 3])['valid'])->toBeFalse();
        });

        it('validates nested item types', function () {
            $schema = Schema::array(Schema::integer());

            $result = $schema->validate([1, 'two', 3]);
            expect($result['valid'])->toBeFalse();
            expect($result['errors'][0])->toContain('[1]');
        });

        it('validates minItems', function () {
            $schema = Schema::array(Schema::string())->minItems(2);

            expect($schema->validate(['a', 'b'])['valid'])->toBeTrue();
            expect($schema->validate(['a'])['valid'])->toBeFalse();
        });

        it('validates maxItems', function () {
            $schema = Schema::array(Schema::string())->maxItems(2);

            expect($schema->validate(['a', 'b'])['valid'])->toBeTrue();
            expect($schema->validate(['a', 'b', 'c'])['valid'])->toBeFalse();
        });
    });

    describe('object schema', function () {
        it('validates objects', function () {
            $schema = Schema::object([
                'name' => Schema::string(),
                'age' => Schema::integer(),
            ]);

            $result = $schema->validate(['name' => 'John', 'age' => 30]);
            expect($result['valid'])->toBeTrue();
        });

        it('requires properties by default', function () {
            $schema = Schema::object([
                'name' => Schema::string(),
                'age' => Schema::integer(),
            ]);

            $result = $schema->validate(['name' => 'John']);
            expect($result['valid'])->toBeFalse();
            expect($result['errors'][0])->toContain('age');
        });

        it('allows optional properties', function () {
            $schema = Schema::object([
                'name' => Schema::string(),
                'nickname' => Schema::string()->optional(),
            ]);

            $result = $schema->validate(['name' => 'John']);
            expect($result['valid'])->toBeTrue();
        });

        it('validates nested objects', function () {
            $schema = Schema::object([
                'user' => Schema::object([
                    'name' => Schema::string(),
                ]),
            ]);

            expect($schema->validate(['user' => ['name' => 'John']])['valid'])->toBeTrue();
            expect($schema->validate(['user' => ['name' => 123]])['valid'])->toBeFalse();
        });
    });

    describe('enum schema', function () {
        it('validates enum values', function () {
            $schema = Schema::enum(['red', 'green', 'blue']);

            expect($schema->validate('red')['valid'])->toBeTrue();
            expect($schema->validate('yellow')['valid'])->toBeFalse();
        });
    });

    describe('nullable', function () {
        it('allows null when nullable', function () {
            $schema = Schema::string()->nullable();

            expect($schema->validate(null)['valid'])->toBeTrue();
            expect($schema->validate('hello')['valid'])->toBeTrue();
        });

        it('rejects null when not nullable', function () {
            $schema = Schema::string();

            expect($schema->validate(null)['valid'])->toBeFalse();
        });
    });

    describe('toJsonSchema', function () {
        it('generates valid JSON schema for objects', function () {
            $schema = Schema::object([
                'name' => Schema::string()->description('The name'),
                'age' => Schema::integer()->min(0)->max(150),
                'tags' => Schema::array(Schema::string()),
            ]);

            $jsonSchema = $schema->toJsonSchema();

            expect($jsonSchema['type'])->toBe('object');
            expect($jsonSchema['properties']['name']['type'])->toBe('string');
            expect($jsonSchema['properties']['age']['minimum'])->toBe(0);
            expect($jsonSchema['properties']['tags']['items']['type'])->toBe('string');
            expect($jsonSchema['required'])->toContain('name');
            expect($jsonSchema['additionalProperties'])->toBeFalse();
        });
    });

    describe('parse', function () {
        it('returns value on success', function () {
            $schema = Schema::string();

            expect($schema->parse('hello'))->toBe('hello');
        });

        it('throws on validation error', function () {
            $schema = Schema::string();

            expect(fn() => $schema->parse(123))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('safeParse', function () {
        it('returns data on success', function () {
            $schema = Schema::string();

            $result = $schema->safeParse('hello');
            expect($result)->toBe(['data' => 'hello']);
        });

        it('returns null on error', function () {
            $schema = Schema::string();

            expect($schema->safeParse(123))->toBeNull();
        });
    });
});
