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

namespace PapiAI\Core\Attributes;

use Attribute;

/**
 * Provides a description for a tool parameter or property in the generated JSON schema.
 *
 * Applied to method parameters alongside the #[Tool] attribute so the LLM
 * understands what each parameter represents.
 */
#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
class Description
{
    /**
     * @param string $text Human-readable description of the parameter
     */
    public function __construct(
        public readonly string $text,
    ) {
    }
}
