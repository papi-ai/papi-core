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
use PapiAI\Core\Response;
use PapiAI\Core\Schema\Schema;
use PapiAI\Core\Tests\Fixtures\ScriptedProvider;

dataset('patterns', [
    'a slash delimiter' => ['/^[A-Z]{3}$/', '^[A-Z]{3}$', 'EUR', 'eur'],
    'a hash delimiter' => ['#^[A-Z]{3}$#', '^[A-Z]{3}$', 'EUR', 'eur'],
    'a tilde delimiter' => ['~^\d+$~', '^\d+$', '42', 'x'],
    'a bracket pair' => ['{^[a-z]{2}$}', '^[a-z]{2}$', 'ab', 'abc'],
    'a bracket pair escaped inside' => ['(^\(\d+\)$)', '^\(\d+\)$', '(42)', '42'],
    'an escaped slash' => ['/^\d+\/\d+$/', '^\d+/\d+$', '1/2', '1-2'],
    'an escaped hash' => ['#^\#\d+$#', '^#\d+$', '#7', '7'],
    'an escaped backslash before the end' => ['/^a\\\\$/', '^a\\\\$', 'a\\', 'a'],
    'a delimiter that is a regex operator' => ['+^a\+b$+', '^a\+b$', 'a+b', 'aab'],
    'the unicode flag' => ['/^\p{Lu}+$/u', '^\p{Lu}+$', 'ÉA', 'éa'],
]);

describe('Schema pattern', function () {
    it('emits the regular expression without its PHP delimiters', function (string $pattern, string $emitted) {
        expect(Schema::string()->pattern($pattern)->toJsonSchema()['pattern'])->toBe($emitted);
    })->with('patterns');

    it('validates with the PHP regular expression as written', function (string $pattern, string $emitted, string $fits, string $misfits) {
        $schema = Schema::string()->pattern($pattern);

        expect($schema->validate($fits)['valid'])->toBeTrue();
        expect($schema->validate($misfits)['valid'])->toBeFalse();
    })->with('patterns');

    it('refuses to emit a flag JSON Schema cannot express', function (string $pattern, string $flag) {
        expect(fn () => Schema::string()->pattern($pattern)->toJsonSchema())
            ->toThrow(InvalidArgumentException::class, sprintf('The flag "%s" of the pattern "%s" cannot be sent to a provider', $flag, $pattern));
    })->with([
        'case-insensitive' => ['/^[a-z]+$/i', 'i'],
        'multiline beside unicode' => ['/^[a-z]+$/um', 'm'],
        'extended' => ['#^ [a-z]+ $#x', 'x'],
    ]);

    it('still validates locally with a flag it cannot emit', function () {
        $schema = Schema::string()->pattern('/^[a-z]+$/i');

        expect($schema->validate('Hello')['valid'])->toBeTrue();
        expect($schema->validate('Hello1')['valid'])->toBeFalse();
    });

    it('emits a pattern without delimiters as it is', function () {
        expect(Schema::string()->pattern('^[A-Z]{3}$')->toJsonSchema()['pattern'])->toBe('^[A-Z]{3}$');
    });

    it('strips the delimiters wherever the string sits', function () {
        $code = Schema::string()->pattern('/^[A-Z]{3}$/');
        $schema = Schema::object([
            'code' => $code,
            'codes' => Schema::array($code),
            'maybe' => Schema::string()->pattern('/^[A-Z]{3}$/')->nullable(),
        ])->toJsonSchema();

        expect($schema['properties']['code']['pattern'])->toBe('^[A-Z]{3}$');
        expect($schema['properties']['codes']['items']['pattern'])->toBe('^[A-Z]{3}$');
        expect($schema['properties']['maybe'])->toBe(['type' => ['string', 'null'], 'pattern' => '^[A-Z]{3}$']);
    });

    it('hands the provider the pattern without delimiters', function () {
        $provider = new ScriptedProvider(true, new Response('{"code": "EUR"}'));
        $schema = Schema::object(['code' => Schema::string()->pattern('/^[A-Z]{3}$/')]);

        $response = (new Agent($provider, 'm'))->run('Currency code for the euro?', ['outputSchema' => $schema]);

        expect($provider->calls[0]['options']['outputSchema']['properties']['code']['pattern'])->toBe('^[A-Z]{3}$');
        expect($response->data)->toBe(['code' => 'EUR']);
    });
});
