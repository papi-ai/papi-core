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
 * Immutable value object containing a generated video from a video provider.
 *
 * A provider may return the clip inline (raw bytes) or as a remote URI that the
 * caller downloads separately; both forms are supported. Holds format and model
 * metadata and provides convenience methods to save the bytes to disk.
 */
final class VideoResponse
{
    /**
     * @param string      $data            Raw video bytes (empty when only a URI is available)
     * @param string|null $uri             Remote URI of the video (null when the bytes are inline)
     * @param string      $mimeType        MIME type (e.g. video/mp4)
     * @param string      $model           The model used
     * @param float|null  $durationSeconds Clip duration in seconds, when reported by the provider
     * @param array       $usage           Raw usage/metadata payload from the provider
     */
    public function __construct(
        public readonly string $data = '',
        public readonly ?string $uri = null,
        public readonly string $mimeType = 'video/mp4',
        public readonly string $model = '',
        public readonly ?float $durationSeconds = null,
        public readonly array $usage = [],
    ) {
    }

    /**
     * Create a response from inline video bytes.
     *
     * @param string     $data            Raw video bytes
     * @param string     $model           The model used
     * @param string     $mimeType        MIME type (e.g. video/mp4)
     * @param float|null $durationSeconds Clip duration in seconds, if known
     * @param array      $usage           Raw usage/metadata payload from the provider
     */
    public static function fromBytes(
        string $data,
        string $model,
        string $mimeType = 'video/mp4',
        ?float $durationSeconds = null,
        array $usage = [],
    ): self {
        return new self($data, null, $mimeType, $model, $durationSeconds, $usage);
    }

    /**
     * Create a response from a remote video URI.
     *
     * @param string     $uri             Remote URI of the video
     * @param string     $model           The model used
     * @param string     $mimeType        MIME type (e.g. video/mp4)
     * @param float|null $durationSeconds Clip duration in seconds, if known
     * @param array      $usage           Raw usage/metadata payload from the provider
     */
    public static function fromUri(
        string $uri,
        string $model,
        string $mimeType = 'video/mp4',
        ?float $durationSeconds = null,
        array $usage = [],
    ): self {
        return new self('', $uri, $mimeType, $model, $durationSeconds, $usage);
    }

    /**
     * Whether the raw video bytes are available inline.
     *
     * @return bool True when data is present
     */
    public function hasData(): bool
    {
        return $this->data !== '';
    }

    /**
     * Save the inline video bytes to a file on disk.
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
     * Get the size of the inline video bytes.
     *
     * @return int Size of the raw video data in bytes
     */
    public function size(): int
    {
        return strlen($this->data);
    }
}
