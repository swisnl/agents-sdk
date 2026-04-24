<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Agent;
use Swis\Agents\Helpers\ConversationSerializer;
use Swis\Agents\Message;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\ReasoningItem;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool\ToolOutput;

class ConversationSerializerTest extends TestCase
{
    public function testSerializeContext()
    {
        // Set up test context
        $context = new RunContext();
        $context
            ->withSystemMessage('System instruction')
            ->addUserMessage('Hello');

        // Add an agent message
        $agent = new Agent(
            name: 'TestAgent',
            description: 'Test agent for serialization',
        );
        $context->addAgentMessage('Hi there', $agent);

        // Serialize
        $serialized = ConversationSerializer::serialize($context);

        // Basic validation of serialized data structure
        $this->assertArrayHasKey('conversation', $serialized);
        $this->assertArrayHasKey('metadata', $serialized);

        // Check conversation data
        $this->assertCount(3, $serialized['conversation']);

        // Check system message is first
        $this->assertEquals('system', $serialized['conversation'][0]['role']);
        $this->assertEquals('System instruction', $serialized['conversation'][0]['content']);

        // Check user message
        $this->assertEquals('user', $serialized['conversation'][1]['role']);
        $this->assertEquals('Hello', $serialized['conversation'][1]['content']);

        // Check agent message
        $this->assertEquals('assistant', $serialized['conversation'][2]['role']);
        $this->assertEquals('Hi there', $serialized['conversation'][2]['content']);
    }

    public function testDeserializeContext()
    {
        // Create serialized data
        $serializedData = [
            'conversation' => [
                [
                    'role' => 'system',
                    'content' => 'System instruction',
                    'usage' => ['input_tokens' => null, 'output_tokens' => null],
                ],
                [
                    'role' => 'user',
                    'content' => 'Hello',
                    'usage' => ['input_tokens' => null, 'output_tokens' => null],
                ],
                [
                    'role' => 'assistant',
                    'content' => 'Hi there',
                    'usage' => ['input_tokens' => 10, 'output_tokens' => 5],
                ],
            ],
        ];

        // Deserialize
        $context = ConversationSerializer::deserialize($serializedData);

        // Validate context
        $this->assertInstanceOf(RunContext::class, $context);
        $this->assertFalse($context->isStreamed());

        // Check messages
        $messages = $context->conversation();
        $this->assertCount(3, $messages);

        // Check system message
        $this->assertEquals('system', $messages[0]->role());
        $this->assertEquals('System instruction', $messages[0]->content());

        // Check user message
        $this->assertEquals('user', $messages[1]->role());
        $this->assertEquals('Hello', $messages[1]->content());

        // Check assistant message
        $this->assertEquals('assistant', $messages[2]->role());
        $this->assertEquals('Hi there', $messages[2]->content());
        $this->assertEquals(10, $messages[2]->usage()['input_tokens']);
        $this->assertEquals(5, $messages[2]->usage()['output_tokens']);
    }

    public function testReasoningItemRoundTrip(): void
    {
        $context = new RunContext();
        $context->addMessage(new ReasoningItem(
            id: 'rs_1',
            encryptedContent: 'BLOB',
            summary: [['type' => 'summary_text', 'text' => 'thinking...']],
        ));

        $serialized = ConversationSerializer::serialize($context);

        $this->assertSame('reasoning', $serialized['conversation'][0]['type']);
        $this->assertSame('rs_1', $serialized['conversation'][0]['id']);
        $this->assertSame('BLOB', $serialized['conversation'][0]['encrypted_content']);

        $restored = ConversationSerializer::deserialize($serialized);
        $messages = $restored->conversation();

        $this->assertInstanceOf(ReasoningItem::class, $messages[0]);
        $this->assertSame('rs_1', $messages[0]->id);
        $this->assertSame('BLOB', $messages[0]->encryptedContent);
        $this->assertSame([['type' => 'summary_text', 'text' => 'thinking...']], $messages[0]->summary);
    }

    public function testToolCallRoundTripPreservesItemId(): void
    {
        $context = new RunContext();
        $context->addMessage(new ToolCall(
            tool: 'get_weather',
            id: 'call_1',
            argumentsPayload: '{"city":"Leiden"}',
            itemId: 'fc_1',
        ));
        $context->addMessage(new ToolOutput('Sunny', 'call_1'));

        $serialized = ConversationSerializer::serialize($context);

        $this->assertSame('tool_call', $serialized['conversation'][0]['type']);
        $this->assertSame('fc_1', $serialized['conversation'][0]['item_id']);
        $this->assertSame('tool_output', $serialized['conversation'][1]['type']);
        $this->assertSame('call_1', $serialized['conversation'][1]['call_id']);

        $restored = ConversationSerializer::deserialize($serialized);
        $messages = $restored->conversation();

        $this->assertInstanceOf(ToolCall::class, $messages[0]);
        $this->assertSame('get_weather', $messages[0]->tool);
        $this->assertSame('call_1', $messages[0]->id);
        $this->assertSame('fc_1', $messages[0]->itemId());
        $this->assertSame('{"city":"Leiden"}', $messages[0]->argumentsPayload);

        $this->assertInstanceOf(ToolOutput::class, $messages[1]);
        $this->assertSame('Sunny', $messages[1]->content());
    }

    public function testAssistantMessageRoundTripPreservesItemId(): void
    {
        $context = new RunContext();
        $context->addMessage(new Message(
            role: Message::ROLE_ASSISTANT,
            content: 'Hello there',
            itemId: 'msg_abc',
        ));

        $serialized = ConversationSerializer::serialize($context);
        $this->assertSame('msg_abc', $serialized['conversation'][0]['item_id']);

        $restored = ConversationSerializer::deserialize($serialized);
        $messages = $restored->conversation();

        $this->assertSame('msg_abc', $messages[0]->itemId());
        $this->assertSame('Hello there', $messages[0]->content());
    }
}
