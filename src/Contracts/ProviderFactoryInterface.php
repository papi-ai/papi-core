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

namespace PapiAI\Core\Contracts;

use PapiAI\Core\Exception\ConfigurationException;
use PapiAI\Core\Factory\Setting;

/**
 * Builds one provider from plain settings, so an application can choose a provider by name
 * from its configuration without knowing the provider's class or constructor.
 *
 * Each provider package ships one, named in its composer.json under extra.papi.factory.
 */
interface ProviderFactoryInterface
{
    /**
     * The name a configuration selects this provider by, such as "google".
     */
    public function name(): string;

    /**
     * The Composer package that ships this provider, such as "papi-ai/google".
     */
    public function package(): string;

    /**
     * Every setting create() accepts.
     *
     * @return list<Setting>
     */
    public function settings(): array;

    /**
     * @param array<string, mixed> $settings Setting values keyed by Setting::$name
     *
     * @throws ConfigurationException When a setting is unknown, missing or of the wrong type
     */
    public function create(array $settings): ProviderInterface;
}
