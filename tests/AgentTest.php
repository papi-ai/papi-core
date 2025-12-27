<?php

namespace PapiAi\Core\Tests;

use PapiAi\Core\Agent;
use PapiAi\Core\Papi;
use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
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
