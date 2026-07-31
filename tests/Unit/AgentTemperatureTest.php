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

use PapiAI\Core\Agent;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Response;

/**
 * Records the options every call was made with.
 */
class TemperatureRecordingProvider implements ProviderInterface
{
    /** @var list<array<string, mixed>> */
    public array $calls = [];

    public function chat(array $messages, array $options = []): Response
    {
        $this->calls[] = $options;

        return new Response('done');
    }

    public function stream(array $messages, array $options = []): iterable
    {
        $this->calls[] = $options;

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
        return false;
    }

    public function getName(): string
    {
        return 'recorder';
    }
}

/**
 * Anthropic returns a 400 for a non-default temperature on Claude 4.7 and later, and Google has
 * deprecated it too. An agent that invents a temperature nobody asked for therefore breaks those
 * models outright, so it is only sent when the caller actually chose one.
 */
describe('Agent temperature', function () {
    beforeEach(function () {
        $this->provider = new TemperatureRecordingProvider();
    });

    it('sends no temperature when the caller never set one', function () {
        (new Agent(provider: $this->provider, model: 'm'))->run('hi');

        expect($this->provider->calls[0])->not->toHaveKey('temperature');
    });

    it('sends the temperature the caller chose', function () {
        (new Agent(provider: $this->provider, model: 'm', temperature: 0.2))->run('hi');

        expect($this->provider->calls[0]['temperature'])->toBe(0.2);
    });

    it('sends an explicit zero, which is a real choice', function () {
        (new Agent(provider: $this->provider, model: 'm', temperature: 0.0))->run('hi');

        expect($this->provider->calls[0]['temperature'])->toBe(0.0);
    });

    it('leaves it out when streaming too', function () {
        iterator_to_array((new Agent(provider: $this->provider, model: 'm'))->stream('hi'));

        expect($this->provider->calls[0])->not->toHaveKey('temperature');
    });
});
