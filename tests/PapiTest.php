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
}
