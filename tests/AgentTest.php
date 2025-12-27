<?php

namespace PapiAi\Core\Tests;

use PHPUnit\Framework\TestCase;
use PapiAi\Core\Papi;
use PapiAi\Core\Agent;

class AgentTest extends TestCase
{
    public function testWithModel()
    {
        $agent = Papi::agent();
        $agent->withModel('openai', 'gpt-4');

        $this->assertEquals('openai', $agent->getProvider());
        $this->assertEquals('gpt-4', $agent->getModel());
    }

    public function testWithModelChaining()
    {
        $agent = Papi::agent()->withModel('anthropic', 'claude-3-opus');

        $this->assertInstanceOf(Agent::class, $agent);
        $this->assertEquals('anthropic', $agent->getProvider());
        $this->assertEquals('claude-3-opus', $agent->getModel());
    }

    public function testWithMemory()
    {
        $agent = Papi::agent();
        $agent->withMemory('sliding_window', ['size' => 10]);

        $this->assertEquals('sliding_window', $agent->getMemoryType());
        $this->assertEquals(['size' => 10], $agent->getMemoryConfig());
    }

    public function testWithMemoryChaining()
    {
        $agent = Papi::agent()
            ->withModel('openai', 'gpt-4')
            ->withMemory('sliding_window', ['size' => 5]);

        $this->assertEquals('openai', $agent->getProvider());
        $this->assertEquals('sliding_window', $agent->getMemoryType());
        $this->assertEquals(['size' => 5], $agent->getMemoryConfig());
    }
}
