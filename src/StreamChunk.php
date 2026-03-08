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
 * Immutable value object representing a single text chunk during streaming.
 *
 * Yielded by stream() methods to deliver incremental text as it is generated.
 * The isComplete flag indicates the final chunk in the stream.
 */
final class StreamChunk
{
    /**
     * @param string $text The text fragment in this chunk
     * @param bool $isComplete Whether this is the final chunk in the stream
     */
    public function __construct(
        public readonly string $text,
        public readonly bool $isComplete = false,
    ) {
    }
}
