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

namespace PapiAI\Core;

/**
 * Why a model stopped, in one vocabulary for every provider.
 *
 * The provider's own string stays on Response::$stopReason; this is what to branch on.
 */
enum StopReason: string
{
    /** The model finished its turn. */
    case Stop = 'stop';

    /** The model wants tools run before it continues. */
    case ToolCalls = 'tool_calls';

    /** The output ceiling cut the answer short. */
    case MaxTokens = 'max_tokens';

    /** The model declined, or a content filter stopped it. */
    case Refused = 'refused';

    /** Anything else, or the provider did not say. */
    case Other = 'other';
}
