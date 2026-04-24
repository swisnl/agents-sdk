<?php

namespace Swis\Agents\Tests\Integration;

use Swis\Agents\Agent;
use Swis\Agents\Response\ReasoningItem;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;
use Swis\Agents\Tool\Required;
use Swis\Agents\Tool\ToolOutput;
use Swis\Agents\Tool\ToolParameter;
use Swis\Agents\Transporters\ChatCompletionTransporter;

class ToolAgentTest extends BaseOrchestratorTestCase
{
    public function testToolAgentInteraction()
    {
        $agent = new Agent(
            name: 'Simple Agent',
            tools: [$this->weatherTool()]
        );

        $response = $this->orchestrator
            ->withUserInstruction('What is the current weather in Boston, MA?')
            ->run($agent);

        $conversation = $this->orchestrator->context->conversation();

        $this->assertInstanceOf(ReasoningItem::class, $conversation[2]);

        $this->assertInstanceOf(ToolCall::class, $conversation[3]);
        $this->assertArrayHasKey('location', $conversation[3]->arguments);
        $this->assertEquals('Boston, MA', $conversation[3]->arguments['location']);

        $this->assertInstanceOf(ToolOutput::class, $conversation[4]);
        $this->assertEquals('tool', $conversation[4]->role());
        $this->assertEquals('It is currently 20 degrees in Boston, MA', $conversation[4]->content());
        $this->assertArrayHasKey('call_id', $conversation[4]->jsonSerialize());
        $this->assertEquals('call_trlgKnhMpYSC7CFXKw3CceUZ', $conversation[4]->jsonSerialize()['call_id']);

        $this->assertEquals('It\'s 20 degrees in Boston, MA right now.', $response->content());
    }

    public function testToolAgentInteractionWithChatCompletions()
    {
        $agent = new Agent(
            name: 'Simple Agent',
            tools: [$this->weatherTool()],
            transporter: new ChatCompletionTransporter()
        );

        $response = $this->orchestrator
            ->withUserInstruction('What is the current weather in Boston, MA?')
            ->run($agent);

        $conversation = $this->orchestrator->context->conversation();

        $this->assertInstanceOf(ToolCall::class, $conversation[2]);
        $this->assertArrayHasKey('location', $conversation[2]->arguments);
        $this->assertEquals('Boston, MA', $conversation[2]->arguments['location']);

        $this->assertInstanceOf(ToolOutput::class, $conversation[3]);
        $this->assertEquals('tool', $conversation[3]->role());
        $this->assertEquals('It is currently 20 degrees in Boston, MA', $conversation[3]->content());
        $this->assertArrayHasKey('call_id', $conversation[3]->jsonSerialize());
        $this->assertEquals('call_trlgKnhMpYSC7CFXKw3CceUZ', $conversation[3]->jsonSerialize()['call_id']);

        $this->assertEquals('It\'s 20 degrees in Boston, MA right now.', $response->content());
    }

    protected function weatherTool(): Tool
    {
        return new class () extends Tool {
            #[ToolParameter('The name of the location.'), Required]
            public string $location;

            public function name(): string
            {
                return 'get_current_weather';
            }

            public function __invoke(): ?string
            {
                return 'It is currently 20 degrees in ' . $this->location;
            }
        };
    }
}
