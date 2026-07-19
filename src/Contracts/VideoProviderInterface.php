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

use PapiAI\Core\JobStatus;
use PapiAI\Core\VideoResponse;

/**
 * Contract for providers that support AI video generation.
 *
 * Video generation is long-running: providers such as Google Veo and OpenAI Sora
 * accept a prompt and produce a clip after seconds-to-minutes of processing, usually
 * via a long-running-operation (submit, poll, fetch) lifecycle.
 *
 * Implementations expose two ergonomics over the same capability:
 *   - a blocking generateVideo() that submits and polls internally, and
 *   - an asynchronous startVideo()/videoStatus()/fetchVideo() trio that reuses the
 *     core JobStatus value object so callers can poll on their own schedule (or hand
 *     the job to a queue).
 */
interface VideoProviderInterface
{
    /**
     * Generate a video from a text prompt, blocking until it is ready.
     *
     * Submits the request and polls internally until the provider finishes,
     * then returns the finished clip. Use the async trio below to avoid blocking.
     *
     * @param string $prompt The video generation prompt
     * @param array{
     *     model?: string,
     *     aspectRatio?: string,
     *     durationSeconds?: int,
     *     resolution?: string,
     *     fps?: int,
     *     image?: string,
     *     negativePrompt?: string,
     * } $options Provider-specific options (image = base64/URL seed for image-to-video)
     *
     * @return VideoResponse The generated video
     */
    public function generateVideo(string $prompt, array $options = []): VideoResponse;

    /**
     * Submit a video generation request and return immediately.
     *
     * @param string $prompt The video generation prompt
     * @param array{
     *     model?: string,
     *     aspectRatio?: string,
     *     durationSeconds?: int,
     *     resolution?: string,
     *     fps?: int,
     *     image?: string,
     *     negativePrompt?: string,
     * } $options Provider-specific options
     *
     * @return string The job identifier, to be passed to videoStatus() and fetchVideo()
     */
    public function startVideo(string $prompt, array $options = []): string;

    /**
     * Poll the status of a submitted video generation job.
     *
     * @param string $jobId The identifier returned by startVideo()
     *
     * @return JobStatus The current status (pending/running/completed/failed)
     */
    public function videoStatus(string $jobId): JobStatus;

    /**
     * Retrieve the finished video for a completed job.
     *
     * @param string $jobId The identifier returned by startVideo()
     *
     * @return VideoResponse The generated video
     */
    public function fetchVideo(string $jobId): VideoResponse;

    /**
     * Check if the provider supports video generation.
     *
     * @return bool True if generateVideo() is available
     */
    public function supportsVideoGeneration(): bool;
}
