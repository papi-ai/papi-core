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

interface ImageProviderInterface
{
    /**
     * Generate images from a text prompt.
     *
     * @param string $prompt The image generation prompt
     * @param array{
     *     model?: string,
     *     aspectRatio?: string,
     *     imageSize?: int,
     *     numberOfImages?: int,
     * } $options Provider-specific options
     * @return array{images: array<array{mimeType: string, data: string}>}
     */
    public function generateImage(string $prompt, array $options = []): array;

    /**
     * Edit/enhance an existing image with AI.
     *
     * @param string $imageUrl URL of the source image
     * @param string $prompt Instructions for editing
     * @param array{
     *     model?: string,
     *     aspectRatio?: string,
     *     imageSize?: int,
     * } $options Provider-specific options
     * @return array{images: array<array{mimeType: string, data: string}>, text: string}
     */
    public function editImage(string $imageUrl, string $prompt, array $options = []): array;

    /**
     * Check if the provider supports image generation.
     */
    public function supportsImageGeneration(): bool;

    /**
     * Check if the provider supports image editing.
     */
    public function supportsImageEditing(): bool;
}
