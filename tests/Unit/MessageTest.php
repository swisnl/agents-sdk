<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Agent;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Message;

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

        $message->setOwner($agent);

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

    /**
     * An assistant message with an itemId serialises to the Responses API
     * input-item shape, so it can be replayed in stateless mode.
     */
    public function testAssistantMessageWithItemIdSerialisesAsResponsesInputItem(): void
    {
        $message = new Message(
            role: Message::ROLE_ASSISTANT,
            content: 'Hello there',
            itemId: 'msg_abc123',
        );

        $json = $message->jsonSerialize();

        $this->assertSame('message', $json['type']);
        $this->assertSame('msg_abc123', $json['id']);
        $this->assertSame(Message::ROLE_ASSISTANT, $json['role']);
        $this->assertSame([[ 'type' => 'output_text', 'text' => 'Hello there' ]], $json['content']);
    }

    /**
     * Without an itemId, assistant messages keep the legacy jsonSerialize shape
     * so the Chat Completions transporter is unaffected.
     */
    public function testAssistantMessageWithoutItemIdKeepsLegacyShape(): void
    {
        $message = new Message(
            role: Message::ROLE_ASSISTANT,
            content: 'Hello there',
        );

        $json = $message->jsonSerialize();

        $this->assertArrayNotHasKey('type', $json);
        $this->assertArrayNotHasKey('id', $json);
        $this->assertSame(Message::ROLE_ASSISTANT, $json['role']);
        $this->assertSame('Hello there', $json['content']);
    }
}
