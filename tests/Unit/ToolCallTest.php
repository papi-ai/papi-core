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

use PapiAI\Core\ToolCall;

describe('ToolCall wire form', function () {
    it('sends no arguments as an empty object, never as an empty list', function () {
        $call = new ToolCall('c1', 'run_specs', []);

        expect($call->argumentsAsJson())->toBe('{}');
        expect(json_encode(['input' => $call->argumentsAsObject()]))->toBe('{"input":{}}');
    });

    it('sends arguments as the object the model produced', function () {
        $call = new ToolCall('c1', 'get_weather', ['city' => 'Lisbon', 'days' => [1, 2]]);

        expect($call->argumentsAsJson())->toBe('{"city":"Lisbon","days":[1,2]}');
        expect(json_encode($call->argumentsAsObject()))->toBe('{"city":"Lisbon","days":[1,2]}');
    });

    it('refuses arguments that cannot be encoded rather than sending false', function () {
        expect(fn () => (new ToolCall('c1', 'f', ['bad' => "\xB1\x31"]))->argumentsAsJson())
            ->toThrow(JsonException::class);
    });
});
