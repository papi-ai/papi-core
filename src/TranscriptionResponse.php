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
 * Immutable value object containing the result of an audio transcription.
 *
 * Holds the transcribed text, detected language, duration, and optional
 * timed segments for subtitle or alignment use cases.
 */
final class TranscriptionResponse
{
    /**
     * @param string $text The transcribed text
     * @param string $model The model used
     * @param string|null $language Detected language code
     * @param float|null $duration Audio duration in seconds
     * @param array<array{start: float, end: float, text: string}> $segments Timed segments
     */
    public function __construct(
        public readonly string $text,
        public readonly string $model,
        public readonly ?string $language = null,
        public readonly ?float $duration = null,
        public readonly array $segments = [],
    ) {
    }

    /**
     * Check if timed segments are available.
     *
     * @return bool True if the transcription includes timestamped segments
     */
    public function hasSegments(): bool
    {
        return !empty($this->segments);
    }

    /**
     * Get the number of timed segments.
     *
     * @return int Segment count
     */
    public function segmentCount(): int
    {
        return count($this->segments);
    }
}
