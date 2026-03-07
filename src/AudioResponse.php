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
     * Save audio to a file.
     */
    public function save(string $path): int|false
    {
        return file_put_contents($path, $this->data);
    }

    /**
     * Get the size of the audio data in bytes.
     */
    public function size(): int
    {
        return strlen($this->data);
    }
}
