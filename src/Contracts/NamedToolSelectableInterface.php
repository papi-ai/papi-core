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

/**
 * Marks a provider that can be told to call one specific tool.
 *
 * Extends {@see ToolSelectableInterface} rather than sitting beside it, because forcing a named
 * tool is strictly more than forcing "some tool": anything that can do the former can do the
 * latter. That ordering is what lets a caller ask one question for the common case.
 *
 * On top of `"required"` and `"none"`, an implementer honours
 * `['name' => '<tool>']`, calling exactly that tool.
 *
 * Most providers qualify. Cohere is the instructive exception: its API takes only REQUIRED or
 * NONE, so it implements the parent interface and not this one. Silently turning "call
 * get_weather" into "call something" would satisfy the request shape while breaking the guarantee
 * the caller asked for, so it throws instead.
 */
interface NamedToolSelectableInterface extends ToolSelectableInterface
{
}
