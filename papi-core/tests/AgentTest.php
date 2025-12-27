<?php

namespace PapiAi\Core\Tests;

use PapiAi\Core\Agent;
use PHPUnit\Framework\TestCase;

class AgentTest extends TestCase
{
    public function testItCanBeInstantiated()
    {
        $agent = new Agent();
        $this->assertInstanceOf(Agent::class, $agent);
    }

    public function testWithToolsStoresTools()
    {
        $agent = new Agent();
        $tool = new class {
            public function exampleTool() {}
        };
        
        $agent->withTools([$tool]);
        
        $this->assertCount(1, $agent->getTools());
        $this->assertSame($tool, $agent->getTools()[0]);
    }
}
