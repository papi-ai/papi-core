<?php

namespace Tests\Unit\Triggers;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use Papi\Core\Triggers\EmailTriggerNode;

class EmailTriggerNodeTest extends TestCase
{
    use ProphecyTrait;
    
    #[Test]
    public function it_should_create_email_trigger_with_valid_configuration()
    {
        // Arrange
        $config = [
            'subject' => 'New support ticket',
            'body' => 'A new support ticket has been created',
            'sender' => 'noreply@example.com',
            'recipients' => ['support@example.com']
        ];
        
        // Act
        $trigger = new EmailTriggerNode('test', 'Test Email Trigger', $config);
        
        // Assert
        $this->assertEquals('email', $trigger->getTriggerType());
        $this->assertTrue($trigger->isReady());
    }

    #[Test]
    public function it_should_throw_exception_when_subject_and_body_are_missing()
    {
        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email trigger requires subject or body');
        
        new EmailTriggerNode('test', 'Test Email Trigger', []);
    }

    #[Test]
    public function it_should_output_structured_email_data()
    {
        // Arrange
        $config = [
            'subject' => 'Test Subject',
            'body' => 'Test Body',
            'sender' => 'sender@example.com',
            'recipients' => ['recipient@example.com']
        ];
        
        $trigger = new EmailTriggerNode('test', 'Test Email Trigger', $config);
        
        // Act
        $output = $trigger->execute();
        
        // Assert
        $this->assertEquals('email', $output['type']);
        $this->assertEquals('Test Subject', $output['subject']);
        $this->assertEquals('Test Body', $output['body']);
        $this->assertEquals('sender@example.com', $output['sender']);
        $this->assertEquals(['recipient@example.com'], $output['recipients']);
        $this->assertArrayHasKey('timestamp', $output);
        $this->assertArrayHasKey('metadata', $output);
    }
} 
