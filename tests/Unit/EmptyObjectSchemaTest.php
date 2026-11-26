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

use PapiAI\Core\Agent;
use PapiAI\Core\Attributes\Tool as ToolAttribute;
use PapiAI\Core\Contracts\ToolInterface;
use PapiAI\Core\Conversation;
use PapiAI\Core\Handoffs;
use PapiAI\Core\Response;
use PapiAI\Core\Schema\Schema;
use PapiAI\Core\Schema\WireSchema;
use PapiAI\Core\Tests\Fixtures\ScriptedProvider;
use PapiAI\Core\Tool;

class ClockTools
{
    #[ToolAttribute('The current time')]
    public function now(): string
    {
        return '12:00';
    }
}

/**
 * A tool as papi-ai/mcp hands one over: the server's input schema, decoded and passed through.
 */
final class DecodedSchemaTool implements ToolInterface
{
    public function __construct(private readonly array $schema)
    {
    }

    public function getName(): string
    {
        return 'remote';
    }

    public function getDescription(): string
    {
        return 'A remote tool';
    }

    public function getParameterSchema(): array
    {
        return $this->schema;
    }

    public function execute(array $arguments, mixed $context = null): mixed
    {
        return 'ok';
    }
}

function toolsAsSent(ToolInterface ...$tools): string
{
    $provider = new ScriptedProvider(false, new Response('done'));
    (new Agent($provider, 'm', tools: $tools))->run('Go');

    return json_encode($provider->calls[0]['options']['tools'], JSON_THROW_ON_ERROR);
}

describe('A schema with no properties', function () {
    it('is sent as an object for a tool made without parameters', function () {
        $sent = toolsAsSent(Tool::make('ping', 'Ping', [], fn () => 'pong'));

        expect($sent)->toBe('[{"name":"ping","description":"Ping","parameters":{"type":"object","properties":{}}}]');
    });

    it('is sent as an object for an attribute tool without parameters', function () {
        $sent = toolsAsSent(...Tool::fromClass(ClockTools::class));

        expect($sent)->toContain('"parameters":{"type":"object","properties":{}}');
    });

    it('is sent as an object for the transfer tools of a handoff', function () {
        $triage = new ScriptedProvider(false, new Response('Hello'));
        $desk = new Handoffs(['triage' => new Agent($triage, 'm'), 'billing' => new Agent(new ScriptedProvider(), 'm')], start: 'triage');

        $desk->reply((new Conversation())->addUser('Hi'));

        expect(json_encode($triage->calls[0]['options']['tools']))->toContain('"name":"transfer_to_billing"')
            ->toContain('"parameters":{"type":"object","properties":{}}');
    });

    it('is sent as an object for a decoded server schema, however deep', function (array $schema, string $encoded) {
        expect(toolsAsSent(new DecodedSchemaTool($schema)))->toContain('"parameters":' . $encoded);
    })->with([
        'empty properties' => [['type' => 'object', 'properties' => []], '{"type":"object","properties":{}}'],
        'no properties at all' => [['type' => 'object'], '{"type":"object","properties":{}}'],
        'a nested object' => [
            ['type' => 'object', 'properties' => ['filter' => ['type' => 'object', 'properties' => []]], 'required' => []],
            '{"type":"object","properties":{"filter":{"type":"object","properties":{}}},"required":[]}',
        ],
        'objects in a list and in a choice' => [
            ['type' => 'object', 'properties' => [
                'rows' => ['type' => 'array', 'items' => ['type' => 'object']],
                'either' => ['anyOf' => [['type' => ['object', 'null'], 'properties' => []], ['type' => 'string']]],
            ]],
            '{"type":"object","properties":{"rows":{"type":"array","items":{"type":"object","properties":{}}},"either":{"anyOf":[{"type":["object","null"],"properties":{}},{"type":"string"}]}}}',
        ],
        'empty definitions and an open item schema' => [
            ['type' => 'object', 'properties' => ['any' => ['type' => 'array', 'items' => []]], '$defs' => []],
            '{"type":"object","properties":{"any":{"type":"array","items":{}}},"$defs":{}}',
        ],
        'a property named properties' => [
            ['type' => 'object', 'properties' => ['properties' => ['type' => 'array', 'items' => ['type' => 'string']]]],
            '{"type":"object","properties":{"properties":{"type":"array","items":{"type":"string"}}}}',
        ],
    ]);

    it('is sent as an object in an output schema', function () {
        $provider = new ScriptedProvider(true, new Response('{"meta": {}}'));

        (new Agent($provider, 'm'))->run('Go', ['outputSchema' => Schema::object(['meta' => Schema::object([])])]);

        expect(json_encode($provider->calls[0]['options']['outputSchema']))
            ->toBe('{"type":"object","properties":{"meta":{"type":"object","properties":{},"additionalProperties":false}},"required":["meta"],"additionalProperties":false}');
    });

    it('accepts an empty object in a reply, which decodes to an empty array', function () {
        $schema = Schema::object(['meta' => Schema::object([])]);

        expect($schema->validate(['meta' => []])['valid'])->toBeTrue();
        expect($schema->validate(['meta' => ['a', 'b']])['valid'])->toBeFalse();
    });

    it('leaves a schema with properties as it was', function () {
        $schema = Schema::object(['name' => Schema::string(), 'tags' => Schema::array(Schema::string())])->toJsonSchema();

        expect((new WireSchema($schema))->toArray())->toBe($schema);
    });
});
