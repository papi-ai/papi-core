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

use PapiAI\Core\AudioResponse;

/**
 * Contract for providers that synthesize speech from text (TTS).
 *
 * Implementations convert text input to audio data in various formats and voices.
 */
interface TextToSpeechProviderInterface
{
    /**
     * Synthesize speech from text.
     *
     * @param string $text The text to convert to speech
     * @param array{
     *     model?: string,
     *     voice?: string,
     *     format?: string,
     *     speed?: float,
     *     instructions?: string,
     * } $options Provider-specific options
     *
     * @return AudioResponse The generated audio data with format metadata
     */
    public function synthesize(string $text, array $options = []): AudioResponse;
}
