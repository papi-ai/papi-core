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
use PapiAI\Core\Contracts\NamedToolSelectableInterface;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Contracts\ToolSelectableInterface;
use PapiAI\Core\Response;
use PapiAI\Core\Tool;
use PapiAI\Core\ToolCall;

/**
 * Records the options of every call so the forced-choice lifetime can be asserted.
 */
class RecordingToolChoiceProvider implements NamedToolSelectableInterface
{
    /** @var list<array<string, mixed>> */
    public array $calls = [];

    /** @var list<Response> */
    private array $responses;

    public function __construct(Response ...$responses)
    {
        $this->responses = $responses;
    }

    public function chat(array $messages, array $options = []): Response
    {
        $this->calls[] = $options;

        return array_shift($this->responses) ?? new Response('done');
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

describe('Agent tool choice', function () {
    beforeEach(function () {
        $this->tool = Tool::make(
            name: 'get_weather',
            description: 'Weather',
            parameters: [],
            handler: fn () => 'sunny',
        );
    });

    it('forwards the choice to the provider', function () {
        $provider = new RecordingToolChoiceProvider(new Response('done'));
        $agent = new Agent(provider: $provider, model: 'm', tools: [$this->tool]);

        $agent->run('hi', ['toolChoice' => ['name' => 'get_weather']]);

        expect($provider->calls[0]['toolChoice'])->toBe(['name' => 'get_weather']);
    });

    it('forces only the opening call, so the loop can still finish', function () {
        // The trap this exists to avoid: forcing every turn means the model must always call a
        // tool, can never answer in plain text, and the loop can only end by exhausting maxTurns.
        $provider = new RecordingToolChoiceProvider(
            new Response('', toolCalls: [new ToolCall('1', 'get_weather', [])]),
            new Response('sunny today'),
        );
        $agent = new Agent(provider: $provider, model: 'm', tools: [$this->tool]);

        $response = $agent->run('hi', ['toolChoice' => 'required']);

        expect($response->text)->toBe('sunny today');
        expect($provider->calls)->toHaveCount(2);
        expect($provider->calls[0]['toolChoice'])->toBe('required');
        expect($provider->calls[1])->not->toHaveKey('toolChoice');
    });

    it('sends nothing when the caller does not ask (backward compatible)', function () {
        $provider = new RecordingToolChoiceProvider(new Response('done'));
        $agent = new Agent(provider: $provider, model: 'm', tools: [$this->tool]);

        $agent->run('hi');

        expect($provider->calls[0])->not->toHaveKey('toolChoice');
    });

    it('forwards the choice when streaming, where there is no loop to trap', function () {
        $provider = new RecordingToolChoiceProvider();
        $agent = new Agent(provider: $provider, model: 'm', tools: [$this->tool]);

        iterator_to_array($agent->stream('hi', ['toolChoice' => 'required']));

        expect($provider->calls[0]['toolChoice'])->toBe('required');
    });
});

describe('Agent effort', function () {
    beforeEach(function () {
        $this->tool = Tool::make('get_weather', 'Weather', [], fn () => 'sunny');
    });

    it('forwards the level to the provider', function () {
        $provider = new RecordingToolChoiceProvider(new Response('done'));

        (new Agent(provider: $provider, model: 'm'))->run('hi', ['effort' => 'high']);

        expect($provider->calls[0]['effort'])->toBe('high');
    });

    it('applies to every turn, unlike a forced tool', function () {
        // Thinking hard on each turn is what the caller asked for. A forced tool is different:
        // repeat it and the model can never stop calling tools.
        $provider = new RecordingToolChoiceProvider(
            new Response('', toolCalls: [new ToolCall('1', 'get_weather', [])]),
            new Response('sunny'),
        );
        $agent = new Agent(provider: $provider, model: 'm', tools: [$this->tool]);

        $agent->run('hi', ['effort' => 'high', 'toolChoice' => 'required']);

        expect($provider->calls)->toHaveCount(2);
        expect($provider->calls[0]['effort'])->toBe('high');
        expect($provider->calls[1]['effort'])->toBe('high');
        expect($provider->calls[1])->not->toHaveKey('toolChoice');
    });

    it('sends nothing when the caller does not ask', function () {
        $provider = new RecordingToolChoiceProvider(new Response('done'));

        (new Agent(provider: $provider, model: 'm'))->run('hi');

        expect($provider->calls[0])->not->toHaveKey('effort');
    });

    it('forwards it when streaming too', function () {
        $provider = new RecordingToolChoiceProvider();

        iterator_to_array((new Agent(provider: $provider, model: 'm'))->stream('hi', ['effort' => 'low']));

        expect($provider->calls[0]['effort'])->toBe('low');
    });
});

describe('tool-selection capability interfaces', function () {
    it('orders naming a tool as more than forcing one', function () {
        // Anything that can force a *named* tool can force *a* tool, so the common case is one check.
        expect(is_subclass_of(NamedToolSelectableInterface::class, ToolSelectableInterface::class))->toBeTrue();
        expect(is_subclass_of(ToolSelectableInterface::class, ProviderInterface::class))->toBeTrue();
    });

    it('lets a caller ask instead of catching', function () {
        $provider = new RecordingToolChoiceProvider(new Response('done'));

        expect($provider)->toBeInstanceOf(NamedToolSelectableInterface::class);
        expect($provider)->toBeInstanceOf(ToolSelectableInterface::class);
        expect($provider)->toBeInstanceOf(ProviderInterface::class);
    });

    it('leaves a plain provider outside both, which is the signal not to force', function () {
        $plain = Mockery::mock(ProviderInterface::class);

        expect($plain)->not->toBeInstanceOf(ToolSelectableInterface::class);
        expect($plain)->not->toBeInstanceOf(NamedToolSelectableInterface::class);

        Mockery::close();
    });
});
