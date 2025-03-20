<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Message;
use Swis\Agents\Agent;
use Swis\Agents\Interfaces\AgentInterface;

class MessageTest extends TestCase
{
    /**
     * Test message creation with basic properties
     */
    public function testMessageCreation(): void
    {
        $message = new Message(
            role: Message::ROLE_USER,
            content: 'Test message content'
        );
        
        $this->assertEquals(Message::ROLE_USER, $message->role());
        $this->assertEquals('Test message content', $message->content());
    }
    
    /**
     * Test message with an owner agent
     */
    public function testMessageWithOwner(): void
    {
        $agent = $this->createMock(AgentInterface::class);
        
        $message = new Message(
            role: Message::ROLE_ASSISTANT,
            content: 'Test message content'
        );
        
        $message->withOwner($agent);
        
        $this->assertSame($agent, $message->owner());
    }
    
    /**
     * Test message usage statistics
     */
    public function testMessageUsage(): void
    {
        $message = new Message(
            role: Message::ROLE_ASSISTANT,
            content: 'Test message content',
            parameters: [],
            inputTokens: 10,
            outputTokens: 20
        );
        
        $usage = $message->usage();
        
        $this->assertEquals(10, $usage['input_tokens']);
        $this->assertEquals(20, $usage['output_tokens']);
    }
    
    /**
     * Test message JSON serialization
     */
    public function testMessageJsonSerialization(): void
    {
        $message = new Message(
            role: Message::ROLE_SYSTEM,
            content: 'System instruction',
            parameters: ['name' => 'value']
        );
        
        $json = $message->jsonSerialize();
        
        $this->assertEquals(Message::ROLE_SYSTEM, $json['role']);
        $this->assertEquals('System instruction', $json['content']);
        $this->assertEquals('value', $json['name']);
    }
    
    /**
     * Test message string conversion
     */
    public function testMessageToString(): void
    {
        $message = new Message(
            role: Message::ROLE_USER,
            content: 'Test message content'
        );
        
        $this->assertEquals('Test message content', (string) $message);
        
        $nullContentMessage = new Message(
            role: Message::ROLE_USER,
            content: null
        );
        
        $this->assertEquals('', (string) $nullContentMessage);
    }
}