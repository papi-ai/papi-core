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

use PapiAI\Core\Contracts\EmbeddingProviderInterface;
use PapiAI\Core\Contracts\ProviderInterface;
use RuntimeException;
use Throwable;

final class FailoverProvider implements ProviderInterface, EmbeddingProviderInterface
{
    /** @var array<ProviderInterface> */
    private array $providers;

    private ?ProviderInterface $lastUsedProvider = null;

    /**
     * @param array<ProviderInterface> $providers Ordered list of providers to try
     * @param array<class-string<Throwable>> $retryOn Exception types that trigger failover (empty = all)
     */
    public function __construct(
        array $providers,
        private readonly array $retryOn = [],
    ) {
        if (count($providers) < 2) {
            throw new \InvalidArgumentException('FailoverProvider requires at least 2 providers');
        }

        $this->providers = array_values($providers);
    }

    public function chat(array $messages, array $options = []): Response
    {
        return $this->tryProviders(
            fn (ProviderInterface $provider) => $provider->chat($messages, $options)
        );
    }

    public function stream(array $messages, array $options = []): iterable
    {
        return $this->tryProviders(
            fn (ProviderInterface $provider) => $provider->stream($messages, $options)
        );
    }

    public function embed(string|array $input, array $options = []): EmbeddingResponse
    {
        return $this->tryProviders(function (ProviderInterface $provider) use ($input, $options) {
            if (!$provider instanceof EmbeddingProviderInterface) {
                throw new RuntimeException("Provider {$provider->getName()} does not support embeddings");
            }

            return $provider->embed($input, $options);
        });
    }

    public function supportsTool(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsTool()) {
                return true;
            }
        }

        return false;
    }

    public function supportsVision(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsVision()) {
                return true;
            }
        }

        return false;
    }

    public function supportsStructuredOutput(): bool
    {
        foreach ($this->providers as $provider) {
            if ($provider->supportsStructuredOutput()) {
                return true;
            }
        }

        return false;
    }

    public function getName(): string
    {
        return 'failover';
    }

    /**
     * Get the provider that was last used successfully.
     */
    public function getLastUsedProvider(): ?ProviderInterface
    {
        return $this->lastUsedProvider;
    }

    /**
     * Try each provider in order, failing over on exception.
     *
     * @template T
     * @param callable(ProviderInterface): T $operation
     * @return T
     */
    private function tryProviders(callable $operation): mixed
    {
        $lastException = null;

        foreach ($this->providers as $provider) {
            try {
                $result = $operation($provider);
                $this->lastUsedProvider = $provider;

                return $result;
            } catch (Throwable $e) {
                if (!$this->shouldRetry($e)) {
                    throw $e;
                }
                $lastException = $e;
            }
        }

        throw new RuntimeException(
            'All providers failed. Last error: ' . ($lastException?->getMessage() ?? 'unknown'),
            0,
            $lastException,
        );
    }

    /**
     * Check if an exception should trigger failover.
     */
    private function shouldRetry(Throwable $e): bool
    {
        if (empty($this->retryOn)) {
            return true;
        }

        foreach ($this->retryOn as $exceptionClass) {
            if ($e instanceof $exceptionClass) {
                return true;
            }
        }

        return false;
    }
}
