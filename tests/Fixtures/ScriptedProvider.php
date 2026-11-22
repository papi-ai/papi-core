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
use PapiAI\Core\Message;
use PapiAI\Core\Response;
use Throwable;

/**
 * Answers each chat call with the next scripted Response, or throws the next scripted Throwable,
 * and remembers what it was sent. Once the script runs out it answers "done".
 */
final class ScriptedProvider implements ProviderInterface
{
    /** @var list<array{messages: list<Message>, options: array<string, mixed>}> */
    public array $calls = [];

    /** @var list<Response|Throwable> */
    private array $script;

    public function __construct(
        private readonly bool $structuredOutput = false,
        Response|Throwable ...$script,
    ) {
        $this->script = array_values($script);
    }

    public function chat(array $messages, array $options = []): Response
    {
        $this->calls[] = ['messages' => array_values($messages), 'options' => $options];
        $next = array_shift($this->script) ?? new Response('done');

        if ($next instanceof Throwable) {
            throw $next;
        }

        return $next;
    }

    public function stream(array $messages, array $options = []): iterable
    {
        $this->calls[] = ['messages' => array_values($messages), 'options' => $options];

        return [];
    }

    public function supportsTool(): bool
    {
        return true;
    }

    public function supportsVision(): bool
    {
        return false;
    }

    public function supportsStructuredOutput(): bool
    {
        return $this->structuredOutput;
    }

    public function getName(): string
    {
        return 'scripted';
    }
}
