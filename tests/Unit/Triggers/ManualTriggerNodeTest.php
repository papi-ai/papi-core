<?php

namespace Tests\Unit\Triggers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Triggers\ManualTriggerNode;

class ManualTriggerNodeTest extends TestCase
{
    use ProphecyTrait;
    
    #[Test]
    public function it_should_create_manual_trigger_with_valid_configuration()
    {
        // Arrange
        $config = [
            'query' => 'Process this data',
            'user' => 'admin'
        ];
        
        // Act
        $trigger = new ManualTriggerNode('test', 'Test Manual Trigger', $config);
        
        // Assert
        $this->assertEquals('manual', $trigger->getTriggerType());
        $this->assertTrue($trigger->isReady());
    }

    #[Test]
    public function it_should_allow_empty_configuration()
    {
        // Act
        $trigger = new ManualTriggerNode('test', 'Test Manual Trigger', []);
        
        // Assert
        $this->assertEquals('manual', $trigger->getTriggerType());
        $this->assertTrue($trigger->isReady());
    }

    #[Test]
    public function it_should_output_structured_manual_data()
    {
        // Arrange
        $config = [
            'query' => 'Test query',
            'user' => 'testuser'
        ];
        
        $trigger = new ManualTriggerNode('test', 'Test Manual Trigger', $config);
        
        // Act
        $output = $trigger->execute();
        
        // Assert
        $this->assertEquals('manual_trigger', $output['type']);
        $this->assertEquals('Test query', $output['query']);
        $this->assertEquals('testuser', $output['user']);
        $this->assertArrayHasKey('timestamp', $output);
        $this->assertArrayHasKey('metadata', $output);
    }
} 
