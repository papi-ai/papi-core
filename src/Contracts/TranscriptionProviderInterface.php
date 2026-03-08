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

use PapiAI\Core\TranscriptionResponse;

/**
 * Contract for providers that transcribe audio to text (speech-to-text).
 *
 * Implementations process audio files and return transcribed text with
 * optional language detection and timed segments.
 */
interface TranscriptionProviderInterface
{
    /**
     * Transcribe audio to text.
     *
     * @param string $audioPath Path to the audio file
     * @param array{
     *     model?: string,
     *     language?: string,
     *     prompt?: string,
     *     timestamps?: bool,
     * } $options Provider-specific options
     *
     * @return TranscriptionResponse The transcribed text with optional segments and language info
     */
    public function transcribe(string $audioPath, array $options = []): TranscriptionResponse;
}
