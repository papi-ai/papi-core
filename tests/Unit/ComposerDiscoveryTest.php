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

use Composer\InstalledVersions;
use PapiAI\Core\Exception\ConfigurationException;
use PapiAI\Core\Factory\ComposerDiscovery;
use PapiAI\Core\Tests\Fixtures\FakeProviderFactory;

/**
 * Pretends Composer installed the given fixture packages, each under tests/Fixtures/Discovery.
 *
 * @param array<string, string> $packages package name => type
 */
function installFixtures(array $packages): void
{
    $versions = [];

    foreach ($packages as $name => $type) {
        $versions[$name] = [
            'pretty_version' => '1.0.0',
            'version' => '1.0.0.0',
            'reference' => null,
            'type' => $type,
            'install_path' => __DIR__ . '/../Fixtures/Discovery/' . substr($name, strlen('acme/')),
            'aliases' => [],
            'dev_requirement' => false,
        ];
    }

    InstalledVersions::reload([
        'root' => ['name' => 'acme/app', 'pretty_version' => '1.0.0', 'version' => '1.0.0.0', 'reference' => null, 'type' => 'project', 'install_path' => __DIR__, 'aliases' => [], 'dev' => true],
        'versions' => $versions,
    ]);
}

describe('Composer discovery', function () {
    afterEach(function () {
        InstalledVersions::reload(null);
    });

    it('finds the factory each papi-provider package names, and only those', function () {
        installFixtures(['acme/provider' => 'papi-provider', 'acme/no-factory' => 'library']);

        $factories = iterator_to_array(new ComposerDiscovery(), false);

        expect($factories)->toHaveCount(1);
        expect($factories[0])->toBeInstanceOf(FakeProviderFactory::class);
    });

    it('finds nothing when no provider package is installed', function () {
        expect(iterator_to_array(new ComposerDiscovery(), false))->toBe([]);
    });

    it('refuses a provider package that names no factory', function () {
        installFixtures(['acme/no-factory' => 'papi-provider']);

        expect(fn () => iterator_to_array(new ComposerDiscovery()))
            ->toThrow(ConfigurationException::class, 'Package "acme/no-factory" is a papi-provider but names no factory in extra.papi.factory.');
    });

    it('refuses a factory class that does not exist', function () {
        installFixtures(['acme/missing-class' => 'papi-provider']);

        expect(fn () => iterator_to_array(new ComposerDiscovery()))
            ->toThrow(ConfigurationException::class, 'Package "acme/missing-class" names factory "Acme\\Missing\\Factory", which does not exist.');
    });

    it('refuses a class that is not a provider factory', function () {
        installFixtures(['acme/not-a-factory' => 'papi-provider']);

        expect(fn () => iterator_to_array(new ComposerDiscovery()))
            ->toThrow(ConfigurationException::class, 'which does not implement PapiAI\\Core\\Contracts\\ProviderFactoryInterface.');
    });
});
