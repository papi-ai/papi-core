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

use PapiAI\Core\Effort;
use PapiAI\Core\Exception\ConfigurationException;
use PapiAI\Core\Factory\Setting;
use PapiAI\Core\Factory\SettingValues;
use PapiAI\Core\Tests\Fixtures\FakeProvider;
use PapiAI\Core\Tests\Fixtures\FakeProviderFactory;

describe('A provider factory', function () {
    beforeEach(function () {
        $this->factory = new FakeProviderFactory();
    });

    it('describes itself for a setup prompt', function () {
        expect($this->factory->name())->toBe('fake');
        expect($this->factory->package())->toBe('papi-ai/fake');
        expect($this->factory->settings()[0])->toEqual(new Setting('api_key', true, 'Fake API key'));
    });

    it('builds the provider from the settings it was given', function () {
        $provider = $this->factory->create(['api_key' => 'k', 'model' => 'fake-2', 'max_tokens' => 512, 'effort' => 'high', 'base_url' => 'http://h']);

        expect($provider)->toEqual(new FakeProvider('k', 'fake-2', 512, Effort::High, 'http://h'));
    });

    it('leaves the optional settings it was not given to the provider\'s own defaults', function () {
        expect($this->factory->create(['api_key' => 'k']))->toEqual(new FakeProvider('k', 'fake-1', 4096, null, null));
    });

    it('treats an empty string as not given', function () {
        expect($this->factory->create(['api_key' => 'k', 'model' => '', 'base_url' => '']))->toEqual(new FakeProvider('k', 'fake-1', 4096, null, null));
    });

    it('accepts a number written as a string, as an environment variable would be', function () {
        expect($this->factory->create(['api_key' => 'k', 'max_tokens' => '2048'])->maxTokens)->toBe(2048);
    });

    it('refuses a setting it does not know, so a typo fails loudly', function () {
        expect(fn () => $this->factory->create(['apikey' => 'k']))
            ->toThrow(ConfigurationException::class, 'Provider "fake" has no setting "apikey". It takes: api_key, model, max_tokens, effort, base_url.');
    });

    it('refuses to build without a required setting, saying what it is', function () {
        foreach ([[], ['api_key' => ''], ['api_key' => null]] as $settings) {
            expect(fn () => $this->factory->create($settings))
                ->toThrow(ConfigurationException::class, 'Provider "fake" needs the "api_key" setting: Fake API key.');
        }
    });

    it('refuses a value of the wrong type', function () {
        expect(fn () => $this->factory->create(['api_key' => 'k', 'max_tokens' => 'lots']))
            ->toThrow(ConfigurationException::class, 'Setting "max_tokens" of provider "fake" must be an integer, string given.');
        expect(fn () => $this->factory->create(['api_key' => ['k']]))
            ->toThrow(ConfigurationException::class, 'Setting "api_key" of provider "fake" must be a string, array given.');
    });

    it('refuses an effort it does not recognise, listing the ones it does', function () {
        expect(fn () => $this->factory->create(['api_key' => 'k', 'effort' => 'enormous']))
            ->toThrow(ConfigurationException::class, 'Setting "effort" of provider "fake" must be one of "none", "minimal", "low", "medium", "high", "extra-high", "maximum"; "enormous" given.');
    });

    it('accepts an Effort as well as its name', function () {
        expect($this->factory->create(['api_key' => 'k', 'effort' => Effort::Low])->effort)->toBe(Effort::Low);
    });

    it('refuses to read a string that was never given and has no default', function () {
        expect(fn () => (new SettingValues('fake', []))->string('endpoint'))
            ->toThrow(ConfigurationException::class, 'Provider "fake" needs the "endpoint" setting.');
    });

    it('throws an InvalidArgumentException, which callers already catch around construction', function () {
        expect(new ConfigurationException('x'))->toBeInstanceOf(InvalidArgumentException::class);
    });
});
