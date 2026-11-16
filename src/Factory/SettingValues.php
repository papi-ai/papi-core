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

use PapiAI\Core\Effort;
use PapiAI\Core\Exception\ConfigurationException;

/**
 * The settings handed to a factory, read back as the types a provider constructor takes.
 *
 * An empty string counts as not given, since that is what an unset environment variable or a
 * blank configuration entry produces.
 */
final class SettingValues
{
    /**
     * @param array<string, mixed> $values
     */
    public function __construct(
        private readonly string $provider,
        private readonly array $values,
    ) {
    }

    /**
     * @throws ConfigurationException When the value is missing with no default, or not a string
     */
    public function string(string $name, ?string $default = null): string
    {
        $value = $this->stringOrNull($name) ?? $default;

        if ($value === null) {
            throw new ConfigurationException(sprintf('Provider "%s" needs the "%s" setting.', $this->provider, $name));
        }

        return $value;
    }

    /**
     * @throws ConfigurationException When the value is given but is not a string
     */
    public function stringOrNull(string $name): ?string
    {
        $value = $this->given($name);

        if ($value === null || is_string($value)) {
            return $value;
        }

        throw $this->wrongType($name, 'a string', $value);
    }

    /**
     * @throws ConfigurationException When the value is given but is not a whole number
     */
    public function intOrNull(string $name): ?int
    {
        $value = $this->given($name);

        if ($value === null) {
            return null;
        }

        if (is_int($value)) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value)) {
            return (int) $value;
        }

        throw $this->wrongType($name, 'an integer', $value);
    }

    /**
     * @throws ConfigurationException When the value is given but names no Effort
     */
    public function effort(string $name = 'effort'): ?Effort
    {
        $value = $this->given($name);

        if ($value === null || $value instanceof Effort) {
            return $value;
        }

        $effort = is_string($value) ? Effort::tryFrom($value) : null;

        if ($effort !== null) {
            return $effort;
        }

        throw new ConfigurationException(sprintf(
            'Setting "%s" of provider "%s" must be one of %s; %s given.',
            $name,
            $this->provider,
            implode(', ', array_map(static fn (Effort $effort): string => '"' . $effort->value . '"', Effort::cases())),
            is_string($value) ? '"' . $value . '"' : get_debug_type($value),
        ));
    }

    private function given(string $name): mixed
    {
        $value = $this->values[$name] ?? null;

        return $value === '' ? null : $value;
    }

    private function wrongType(string $name, string $expected, mixed $value): ConfigurationException
    {
        return new ConfigurationException(sprintf(
            'Setting "%s" of provider "%s" must be %s, %s given.',
            $name,
            $this->provider,
            $expected,
            get_debug_type($value),
        ));
    }
}
