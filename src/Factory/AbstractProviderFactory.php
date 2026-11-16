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
 * Checks the settings against what the factory declares, then hands them to build().
 *
 * Unknown keys are refused, so a misspelt setting fails instead of being ignored.
 */
abstract class AbstractProviderFactory implements ProviderFactoryInterface
{
    final public function create(array $settings): ProviderInterface
    {
        $declared = [];

        foreach ($this->settings() as $setting) {
            $declared[$setting->name] = $setting;
        }

        foreach (array_keys($settings) as $key) {
            if (!isset($declared[$key])) {
                throw new ConfigurationException(sprintf(
                    'Provider "%s" has no setting "%s". It takes: %s.',
                    $this->name(),
                    $key,
                    implode(', ', array_keys($declared)),
                ));
            }
        }

        foreach ($declared as $name => $setting) {
            if ($setting->required && in_array($settings[$name] ?? null, [null, ''], true)) {
                throw new ConfigurationException(sprintf(
                    'Provider "%s" needs the "%s" setting: %s.',
                    $this->name(),
                    $name,
                    $setting->description,
                ));
            }
        }

        return $this->build(new SettingValues($this->name(), $settings));
    }

    /**
     * The constructor arguments that were given, so the provider's own defaults apply to the rest.
     *
     * Spread the result into the constructor: `new MyProvider(...$this->argumentsGiven([...]))`.
     *
     * @param array<string, mixed> $arguments Constructor arguments by parameter name, null when not given
     *
     * @return array<string, mixed>
     */
    protected function argumentsGiven(array $arguments): array
    {
        return array_filter($arguments, static fn (mixed $value): bool => $value !== null);
    }

    /**
     * Construct the provider. Every required setting is present by the time this runs.
     *
     * @throws ConfigurationException When a value has the wrong type
     */
    abstract protected function build(SettingValues $values): ProviderInterface;
}
