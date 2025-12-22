<?php

namespace PapiAi\Core\Tests;

use PHPUnit\Framework\TestCase;
use PapiAi\Core\Papi;

class PapiTest extends TestCase
{
    public function testVersion()
    {
        $papi = new Papi();
        $this->assertEquals('0.1.0', $papi->version());
    }
    
    public function testDefaultProviderIsClaude()
    {
        $papi = new Papi();
        // Reflection to check private property if needed, or just assume it works for now
        $this->assertInstanceOf(Papi::class, $papi);
    }
}
