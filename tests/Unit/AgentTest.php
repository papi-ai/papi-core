<?php

declare(strict_types=1);

use PapiAI\Core\Agent;
use PapiAI\Core\Contracts\ProviderInterface;
use PapiAI\Core\Message;
use PapiAI\Core\Response;
use PapiAI\Core\Tool;
use PapiAI\Core\ToolCall;

describe('Agent', function () {
    beforeEach(function () {
        $this->mockProvider = Mockery::mock(ProviderInterface::class);
        $this->mockProvider->allows('getName')->andReturn('mock');
        $this->mockProvider->allows('supportsTool')->andReturn(true);
        $this->mockProvider->allows('supportsVision')->andReturn(true);
        $this->mockProvider->allows('supportsStructuredOutput')->andReturn(false);
    });

    afterEach(function () {
        Mockery::close();
    });

    describe('construction', function () {
        it('creates an agent with provider and model', function () {
            $agent = new Agent(
                provider: $this->mockProvider,
                model: 'test-model',
            );

            expect($agent->getProvider())->toBe($this->mockProvider);
        });

        it('accepts tools', function () {
            $tool = Tool::make(
                name: 'test',
                description: 'Test tool',
                parameters: [],
                handler: fn() => 'result',
            );

            $agent = new Agent(
                provider: $this->mockProvider,
                model: 'test-model',
                tools: [$tool],
            );

            expect($agent)->toBeInstanceOf(Agent::class);
        });
    });

    describe('run', function () {
        it('sends message to provider and returns response', function () {
            $expectedResponse = new Response(
                text: 'Hello, human!',
                usage: ['input_tokens' => 10, 'output_tokens' => 5],
            );

            $this->mockProvider
                ->expects('chat')
                ->once()
                ->andReturn($expectedResponse);

            $agent = new Agent(
                provider: $this->mockProvider,
                model: 'test-model',
                instructions: 'Be helpful',
            );

            $response = $agent->run('Hello');

            expect($response->text)->toBe('Hello, human!');
        });

        it('includes system instructions', function () {
            $this->mockProvider
                ->expects('chat')
                ->withArgs(function ($messages, $options) {
                    $systemMessages = array_filter($messages, fn($m) => $m->isSystem());
                    $systemMessage = reset($systemMessages) ?: null;
                    return $systemMessage !== null && $systemMessage->getText() === 'Be helpful';
                })
                ->andReturn(new Response(text: 'OK'));

            $agent = new Agent(
                provider: $this->mockProvider,
                model: 'test-model',
                instructions: 'Be helpful',
            );

            $agent->run('Hello');
        });

        it('executes tools when provider requests them', function () {
            $toolExecuted = false;

            $tool = Tool::make(
                name: 'get_data',
                description: 'Get some data',
                parameters: [],
                handler: function () use (&$toolExecuted) {
                    $toolExecuted = true;
                    return ['data' => 42];
                },
            );

            // First call returns tool use
            $toolCallResponse = new Response(
                text: 'Let me get that data...',
                toolCalls: [new ToolCall('call_1', 'get_data', [])],
            );

            // Second call returns final response
            $finalResponse = new Response(
                text: 'The data is 42.',
            );

            $this->mockProvider
                ->expects('chat')
                ->twice()
                ->andReturn($toolCallResponse, $finalResponse);

            $agent = new Agent(
                provider: $this->mockProvider,
                model: 'test-model',
                tools: [$tool],
            );

            $response = $agent->run('Get the data');

            expect($toolExecuted)->toBeTrue();
            expect($response->text)->toBe('The data is 42.');
        });

        it('respects maxTurns limit', function () {
            // Always return a tool call to trigger max turns
            $toolCallResponse = new Response(
                text: '',
                toolCalls: [new ToolCall('call_1', 'loop', [])],
            );

            $this->mockProvider
                ->allows('chat')
                ->andReturn($toolCallResponse);

            $tool = Tool::make(
                name: 'loop',
                description: 'Loops forever',
                parameters: [],
                handler: fn() => 'done',
            );

            $agent = new Agent(
                provider: $this->mockProvider,
                model: 'test-model',
                tools: [$tool],
                maxTurns: 3,
            );

            expect(fn() => $agent->run('Loop'))->toThrow(InvalidArgumentException::class);
        });
    });

    describe('hooks', function () {
        it('calls beforeToolCall hook', function () {
            $hookCalled = false;
            $hookToolName = null;

            $tool = Tool::make(
                name: 'test_tool',
                description: 'Test',
                parameters: [],
                handler: fn() => 'result',
            );

            $this->mockProvider
                ->expects('chat')
                ->twice()
                ->andReturn(
                    new Response(text: '', toolCalls: [new ToolCall('call_1', 'test_tool', ['arg' => 1])]),
                    new Response(text: 'Done'),
                );

            $agent = new Agent(
                provider: $this->mockProvider,
                model: 'test-model',
                tools: [$tool],
                hooks: [
                    'beforeToolCall' => function ($name, $input) use (&$hookCalled, &$hookToolName) {
                        $hookCalled = true;
                        $hookToolName = $name;
                    },
                ],
            );

            $agent->run('Test');

            expect($hookCalled)->toBeTrue();
            expect($hookToolName)->toBe('test_tool');
        });

        it('calls afterToolCall hook with duration', function () {
            $hookDuration = null;

            $tool = Tool::make(
                name: 'slow_tool',
                description: 'Slow tool',
                parameters: [],
                handler: function () {
                    usleep(10000); // 10ms
                    return 'done';
                },
            );

            $this->mockProvider
                ->expects('chat')
                ->twice()
                ->andReturn(
                    new Response(text: '', toolCalls: [new ToolCall('call_1', 'slow_tool', [])]),
                    new Response(text: 'Done'),
                );

            $agent = new Agent(
                provider: $this->mockProvider,
                model: 'test-model',
                tools: [$tool],
                hooks: [
                    'afterToolCall' => function ($name, $result, $duration) use (&$hookDuration) {
                        $hookDuration = $duration;
                    },
                ],
            );

            $agent->run('Test');

            expect($hookDuration)->toBeGreaterThan(0);
        });

        it('calls onError hook when tool fails', function () {
            $errorCaught = null;

            $tool = Tool::make(
                name: 'failing_tool',
                description: 'Fails',
                parameters: [],
                handler: function () {
                    throw new RuntimeException('Tool failed!');
                },
            );

            $this->mockProvider
                ->expects('chat')
                ->twice()
                ->andReturn(
                    new Response(text: '', toolCalls: [new ToolCall('call_1', 'failing_tool', [])]),
                    new Response(text: 'Error handled'),
                );

            $agent = new Agent(
                provider: $this->mockProvider,
                model: 'test-model',
                tools: [$tool],
                hooks: [
                    'onError' => function ($error) use (&$errorCaught) {
                        $errorCaught = $error;
                    },
                ],
            );

            $agent->run('Test');

            expect($errorCaught)->toBeInstanceOf(RuntimeException::class);
        });
    });

    describe('addTool', function () {
        it('adds tools dynamically', function () {
            $tool = Tool::make(
                name: 'dynamic',
                description: 'Added later',
                parameters: [],
                handler: fn() => 'dynamic result',
            );

            $this->mockProvider
                ->expects('chat')
                ->twice()
                ->andReturn(
                    new Response(text: '', toolCalls: [new ToolCall('call_1', 'dynamic', [])]),
                    new Response(text: 'Done'),
                );

            $agent = new Agent(
                provider: $this->mockProvider,
                model: 'test-model',
            );

            $agent->addTool($tool);

            $response = $agent->run('Test');
            expect($response->text)->toBe('Done');
        });
    });
});
