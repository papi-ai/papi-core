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
 * Immutable value object containing synthesized audio data from a TTS provider.
 *
 * Holds the raw audio bytes along with format and model metadata,
 * and provides a convenience method to save the audio to disk.
 */
final class AudioResponse
{
    /**
     * @param string $data Raw audio data
     * @param string $format Audio format (mp3, opus, aac, flac, wav, pcm)
     * @param string $model The model used
     */
    public function __construct(
        public readonly string $data,
        public readonly string $format,
        public readonly string $model,
    ) {
    }

    /**
     * Save audio data to a file on disk.
     *
     * @param string $path The file path to write to
     *
     * @return int|false Number of bytes written, or false on failure
     */
    public function save(string $path): int|false
    {
        return file_put_contents($path, $this->data);
    }

    /**
     * Get the size of the audio data in bytes.
     *
     * @return int Size of the raw audio data
     */
    public function size(): int
    {
        return strlen($this->data);
    }
}
