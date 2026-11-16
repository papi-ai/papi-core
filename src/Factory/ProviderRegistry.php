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

use PapiAI\Core\Contracts\ProviderFactoryInterface;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Exception\ConfigurationException;

/**
 * The providers an application can build, by name.
 *
 * Built from any list of factories: ComposerDiscovery for what is installed, or a plain array
 * where the application wants to choose.
 */
final class ProviderRegistry
{
    /** @var array<string, ProviderFactoryInterface> */
    private array $factories = [];

    /**
     * @param iterable<ProviderFactoryInterface> $factories
     *
     * @throws ConfigurationException When two factories claim the same name
     */
    public function __construct(iterable $factories)
    {
        foreach ($factories as $factory) {
            $claimed = $this->factories[$factory->name()] ?? null;

            if ($claimed !== null) {
                throw new ConfigurationException(sprintf(
                    'Both "%s" and "%s" provide "%s".',
                    $claimed->package(),
                    $factory->package(),
                    $factory->name(),
                ));
            }

            $this->factories[$factory->name()] = $factory;
        }
    }

    /**
     * @return list<ProviderFactoryInterface>
     */
    public function installed(): array
    {
        return array_values($this->factories);
    }

    /**
     * @param array<string, mixed> $settings
     *
     * @throws ConfigurationException When no factory has that name, or the settings do not fit it
     */
    public function create(string $provider, array $settings): ProviderInterface
    {
        $factory = $this->factories[$provider] ?? null;

        if ($factory !== null) {
            return $factory->create($settings);
        }

        if ($this->factories === []) {
            throw new ConfigurationException(sprintf('Provider "%s" is not installed, and no papi provider package is.', $provider));
        }

        throw new ConfigurationException(sprintf(
            'Provider "%s" is not installed. Installed: %s.',
            $provider,
            implode(', ', array_map(static fn (string $name): string => '"' . $name . '"', array_keys($this->factories))),
        ));
    }
}
