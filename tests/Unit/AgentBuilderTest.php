<?php

declare(strict_types=1);

use PapiAI\Core\Agent;
use PapiAI\Core\Contracts\MiddlewareInterface;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Contracts\ToolInterface;
use PapiAI\Core\Response;

describe('AgentBuilder', function () {
    beforeEach(function () {
        $this->provider = Mockery::mock(ProviderInterface::class);
        $this->provider->allows('supportsTool')->andReturn(false);
        $this->provider->allows('supportsVision')->andReturn(false);
        $this->provider->allows('supportsStructuredOutput')->andReturn(false);
    });

    it('creates an agent via Agent::build()', function () {
        $this->provider->expects('chat')->andReturn(new Response(text: 'Hello!'));

        $agent = Agent::build()
            ->provider($this->provider)
            ->model('test-model')
            ->instructions('Be helpful')
            ->maxTokens(2048)
            ->temperature(0.5)
            ->maxTurns(5)
            ->create();

        $response = $agent->run('Hi');

        expect($response->text)->toBe('Hello!');
    });

    it('throws without provider', function () {
        Agent::build()->model('test')->create();
    })->throws(InvalidArgumentException::class, 'Provider is required');

    it('throws without model', function () {
        Agent::build()->provider($this->provider)->create();
    })->throws(InvalidArgumentException::class, 'Model is required');

    it('accepts tools', function () {
        $tool = Mockery::mock(ToolInterface::class);
        $tool->allows('getName')->andReturn('test_tool');
        $tool->allows('toAnthropic')->andReturn([]);

        $this->provider->expects('chat')->andReturn(new Response(text: 'OK'));

        $agent = Agent::build()
            ->provider($this->provider)
            ->model('test')
            ->tool($tool)
            ->create();

        expect($agent->run('Hi')->text)->toBe('OK');
    });

    it('accepts tools as array', function () {
        $tool1 = Mockery::mock(ToolInterface::class);
        $tool1->allows('getName')->andReturn('tool1');
        $tool2 = Mockery::mock(ToolInterface::class);
        $tool2->allows('getName')->andReturn('tool2');

        $agent = Agent::build()
            ->provider($this->provider)
            ->model('test')
            ->tools([$tool1, $tool2])
            ->create();

        expect($agent)->toBeInstanceOf(Agent::class);
    });

    it('accepts middleware', function () {
        $middleware = Mockery::mock(MiddlewareInterface::class);
        $middleware->expects('process')->andReturnUsing(fn ($req, $next) => $next($req));

        $this->provider->expects('chat')->andReturn(new Response(text: 'OK'));

        $agent = Agent::build()
            ->provider($this->provider)
            ->model('test')
            ->addMiddleware($middleware)
            ->create();

        expect($agent->run('Hi')->text)->toBe('OK');
    });

    it('accepts middleware as array', function () {
        $m1 = Mockery::mock(MiddlewareInterface::class);
        $m1->expects('process')->andReturnUsing(fn ($req, $next) => $next($req));

        $this->provider->expects('chat')->andReturn(new Response(text: 'OK'));

        $agent = Agent::build()
            ->provider($this->provider)
            ->model('test')
            ->middleware([$m1])
            ->create();

        expect($agent->run('Hi')->text)->toBe('OK');
    });

    it('accepts hooks', function () {
        $agent = Agent::build()
            ->provider($this->provider)
            ->model('test')
            ->hook('beforeToolCall', function () {
            })
            ->create();

        expect($agent)->toBeInstanceOf(Agent::class);
    });
});
