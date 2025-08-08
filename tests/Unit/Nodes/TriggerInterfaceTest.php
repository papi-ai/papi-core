<?php

namespace Tests\Unit\Nodes;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Nodes\Trigger;

class TriggerInterfaceTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_should_define_trigger_interface_methods()
    {
        // Arrange
        $trigger = $this->prophesize(Trigger::class);

        // Act & Assert - Verify interface methods exist
        $this->assertTrue(method_exists($trigger->reveal(), 'getTriggerType'));
        $this->assertTrue(method_exists($trigger->reveal(), 'validateConfiguration'));
        $this->assertTrue(method_exists($trigger->reveal(), 'isReady'));
    }
}
