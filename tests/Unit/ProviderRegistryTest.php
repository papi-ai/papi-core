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

use PapiAI\Core\Exception\ConfigurationException;
use PapiAI\Core\Factory\ProviderRegistry;
use PapiAI\Core\Tests\Fixtures\FakeProvider;
use PapiAI\Core\Tests\Fixtures\FakeProviderFactory;

describe('The provider registry', function () {
    it('lists the factories it was given', function () {
        $fake = new FakeProviderFactory();
        $other = new FakeProviderFactory('other', 'acme/other');

        expect((new ProviderRegistry([$fake, $other]))->installed())->toBe([$fake, $other]);
    });

    it('creates a provider through the factory of that name', function () {
        $registry = new ProviderRegistry([new FakeProviderFactory('other', 'acme/other'), new FakeProviderFactory()]);

        expect($registry->create('fake', ['api_key' => 'k']))->toBeInstanceOf(FakeProvider::class);
    });

    it('takes any iterable, a discovery included', function () {
        $registry = new ProviderRegistry((function () {
            yield new FakeProviderFactory();
        })());

        expect($registry->installed())->toHaveCount(1);
    });

    it('names what is installed when asked for something that is not', function () {
        $registry = new ProviderRegistry([new FakeProviderFactory(), new FakeProviderFactory('other', 'acme/other')]);

        expect(fn () => $registry->create('google', []))
            ->toThrow(ConfigurationException::class, 'Provider "google" is not installed. Installed: "fake", "other".');
    });

    it('says when no provider is installed at all', function () {
        expect(fn () => (new ProviderRegistry([]))->create('google', []))
            ->toThrow(ConfigurationException::class, 'Provider "google" is not installed, and no papi provider package is.');
    });

    it('refuses two packages claiming the same name', function () {
        expect(fn () => new ProviderRegistry([new FakeProviderFactory(), new FakeProviderFactory('fake', 'acme/fake')]))
            ->toThrow(ConfigurationException::class, 'Both "papi-ai/fake" and "acme/fake" provide "fake".');
    });
});
