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

    public function testWithToolsAcceptsTools()
    {
        $agent = Papi::agent();
        $tool = new class {
            public function hello(string $name): string { return 'world ' . $name; }
        };
        
        $agent->withTools([$tool]);
        
        $this->assertCount(1, $agent->getTools());
        $schemas = $agent->getSchemas();
        $this->assertCount(1, $schemas);
        $this->assertArrayHasKey('hello', $schemas[0]['functions']);
        $this->assertEquals('string', $schemas[0]['functions']['hello']['parameters']['name']['type']);
    }
}
