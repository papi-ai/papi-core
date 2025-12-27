<?php

namespace PapiAi\Core\Tests;

use PHPUnit\Framework\TestCase;
use PapiAi\Core\Papi;
use PapiAi\Core\Agent;

class PapiTest extends TestCase
{
    public function testVersion()
    {
        $papi = new Papi();
        $this->assertEquals('0.1.0', $papi->version());
    }

    public function testAgentFactory()
    {
        $agent = Papi::agent();
        $this->assertInstanceOf(Agent::class, $agent);
    }
}
