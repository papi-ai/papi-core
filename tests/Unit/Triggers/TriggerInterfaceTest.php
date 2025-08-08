<?php

namespace Tests\Unit\Triggers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Triggers\TriggerInterface;
use Papi\Core\Triggers\BaseTriggerNode;

class TriggerInterfaceTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_should_define_trigger_interface_methods()
    {
        // Arrange
        $trigger = $this->prophesize(TriggerInterface::class);

        // Act & Assert - Verify interface methods exist
        $this->assertTrue(method_exists($trigger->reveal(), 'validateConfiguration'));
        $this->assertTrue(method_exists($trigger->reveal(), 'getTriggerType'));
        $this->assertTrue(method_exists($trigger->reveal(), 'isReady'));
    }

    #[Test]
    public function it_should_reject_input_data_in_trigger_execution()
    {
        // Arrange
        $trigger = new TestTriggerNode('test', 'Test Trigger');

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Trigger nodes do not accept input');

        $trigger->execute(['some' => 'input']);
    }
}

// Test implementation for BaseTriggerNode
class TestTriggerNode extends BaseTriggerNode
{
    public function getTriggerType(): string
    {
        return 'test';
    }

    protected function processTrigger(): array
    {
        return ['type' => 'test_trigger'];
    }

    public function toArray(): array
    {
        return [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'type' => 'test_trigger'
        ];
    }
}
