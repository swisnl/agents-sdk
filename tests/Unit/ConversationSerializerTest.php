<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Agent;
use Swis\Agents\Helpers\ConversationSerializer;
use Swis\Agents\Message;
use Swis\Agents\Orchestrator\RunContext;

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
}
