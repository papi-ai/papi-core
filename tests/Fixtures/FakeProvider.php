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

namespace PapiAI\Core\Tests\Fixtures;

use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Effort;
use PapiAI\Core\Response;

/**
 * A provider that records how its factory built it.
 */
final class FakeProvider implements ProviderInterface
{
    public function __construct(
        public readonly string $apiKey,
        public readonly string $model = 'fake-1',
        public readonly int $maxTokens = 4096,
        public readonly ?Effort $effort = null,
        public readonly ?string $baseUrl = null,
    ) {
    }

    public function chat(array $messages, array $options = []): Response
    {
        return new Response('');
    }

    public function stream(array $messages, array $options = []): iterable
    {
        return [];
    }

    public function supportsTool(): bool
    {
        return false;
    }

    public function supportsVision(): bool
    {
        return false;
    }

    public function supportsStructuredOutput(): bool
    {
        return false;
    }

    public function getName(): string
    {
        return 'fake';
    }
}
