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

/**
 * One setting a provider factory accepts.
 */
final class Setting
{
    /**
     * @param string $name        The key, in snake_case, such as "api_key"
     * @param bool   $required    Whether the provider cannot be built without it
     * @param string $description What it is, short enough for a setup prompt
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $required,
        public readonly string $description,
    ) {
    }
}
