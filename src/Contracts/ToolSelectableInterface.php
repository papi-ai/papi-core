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
 * Marks a provider that can be told whether to call a tool.
 *
 * Implementing this means the provider honours `toolChoice` values of `"required"` (it must call
 * one of the declared tools) and `"none"` (it must not call any). It says nothing about forcing a
 * *specific* tool: see {@see NamedToolSelectableInterface} for that.
 *
 * Capability is expressed as a type rather than a `supports*()` probe so callers get a static
 * answer, matching how {@see EmbeddingProviderInterface}, {@see ImageProviderInterface} and the
 * rest already work. Do not confuse it with `ProviderInterface::supportsTool()`, which answers the
 * different question of whether tools work at all: a provider can support tools and still be
 * unable to force their use.
 *
 * A provider that does not implement this throws when asked to force a choice, rather than
 * quietly downgrading to "the model decides". Check the type first and there is nothing to catch:
 *
 *     if ($provider instanceof NamedToolSelectableInterface) {
 *         $agent->run($prompt, ['toolChoice' => ['name' => 'get_weather']]);
 *     } elseif ($provider instanceof ToolSelectableInterface) {
 *         $agent->run($prompt, ['toolChoice' => 'required']);
 *     } else {
 *         $agent->run($prompt);
 *     }
 *
 * `"auto"` needs no capability: every provider accepts it, and it is what omitting the option means.
 */
interface ToolSelectableInterface extends ProviderInterface
{
}
