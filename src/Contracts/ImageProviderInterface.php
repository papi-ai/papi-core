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
 * Contract for providers that support AI image generation and editing.
 *
 * Implementations translate between PapiAI's image API and provider-specific formats
 * (e.g., OpenAI DALL-E, Stability AI).
 */
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
     *
     * @return bool True if generateImage() is available
     */
    public function supportsImageGeneration(): bool;

    /**
     * Check if the provider supports image editing.
     *
     * @return bool True if editImage() is available
     */
    public function supportsImageEditing(): bool;
}
