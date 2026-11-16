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

namespace PapiAI\Core\Factory;

use Composer\InstalledVersions;
use Generator;
use IteratorAggregate;
use PapiAI\Core\Contracts\ProviderFactoryInterface;
use PapiAI\Core\Exception\ConfigurationException;

/**
 * The factories of every installed provider package.
 *
 * A provider package declares the Composer type "papi-provider" and names its factory class
 * under extra.papi.factory in its composer.json.
 *
 * @implements IteratorAggregate<int, ProviderFactoryInterface>
 */
final class ComposerDiscovery implements IteratorAggregate
{
    public const PACKAGE_TYPE = 'papi-provider';

    /**
     * @return Generator<int, ProviderFactoryInterface>
     *
     * @throws ConfigurationException When a provider package names no usable factory
     */
    public function getIterator(): Generator
    {
        foreach (array_unique(InstalledVersions::getInstalledPackagesByType(self::PACKAGE_TYPE)) as $package) {
            yield $this->factoryOf($package);
        }
    }

    private function factoryOf(string $package): ProviderFactoryInterface
    {
        $class = $this->factoryClassNamedBy($package);

        if (!class_exists($class)) {
            throw new ConfigurationException(sprintf('Package "%s" names factory "%s", which does not exist.', $package, $class));
        }

        $factory = new $class();

        if (!$factory instanceof ProviderFactoryInterface) {
            throw new ConfigurationException(sprintf(
                'Package "%s" names factory "%s", which does not implement %s.',
                $package,
                $class,
                ProviderFactoryInterface::class,
            ));
        }

        return $factory;
    }

    private function factoryClassNamedBy(string $package): string
    {
        $manifest = InstalledVersions::getInstallPath($package) . '/composer.json';
        $contents = is_file($manifest) ? file_get_contents($manifest) : false;
        $decoded = is_string($contents) ? json_decode($contents, true) : null;
        $class = is_array($decoded) ? ($decoded['extra']['papi']['factory'] ?? null) : null;

        if (!is_string($class) || $class === '') {
            throw new ConfigurationException(sprintf('Package "%s" is a papi-provider but names no factory in extra.papi.factory.', $package));
        }

        return $class;
    }
}
