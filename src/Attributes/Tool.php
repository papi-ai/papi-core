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
 * Marks a method as an LLM-callable tool.
 *
 * Apply this attribute to public methods on a class to automatically register
 * them as tools via Tool::fromClass(). The method's parameters become the
 * tool's input schema, and non-scalar parameters are treated as context.
 */
#[Attribute(Attribute::TARGET_METHOD)]
class Tool
{
    /**
     * @param string $description Description shown to the LLM so it can decide when to call this tool
     * @param string|null $name Override the tool name (defaults to snake_case of method name)
     */
    public function __construct(
        public readonly string $description,
        public readonly ?string $name = null,
    ) {
    }
}
