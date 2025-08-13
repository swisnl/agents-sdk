<?php

namespace Swis\Agents\Tests\Integration;

use Swis\Agents\Agent;
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

        $this->runAgentTest($agent);
    }

    public function testToolAgentInteractionWithChatCompletions()
    {
        $agent = new Agent(
            name: 'Simple Agent',
            tools: [$this->weatherTool()],
            transporter: new ChatCompletionTransporter()
        );

        $this->runAgentTest($agent);
    }

    protected function runAgentTest(Agent $agent)
    {
        $response = $this->orchestrator
            ->withUserInstruction('What is the current weather in Boston, MA?')
            ->run($agent);

        $this->assertInstanceOf(ToolCall::class, $this->orchestrator->context->conversation()[2]);
        $this->assertArrayHasKey('location', $this->orchestrator->context->conversation()[2]->arguments);
        $this->assertEquals('Boston, MA', $this->orchestrator->context->conversation()[2]->arguments['location']);

        $this->assertInstanceOf(ToolOutput::class, $this->orchestrator->context->conversation()[3]);
        $this->assertEquals('tool', $this->orchestrator->context->conversation()[3]->role());
        $this->assertEquals('It is currently 20 degrees in Boston, MA', $this->orchestrator->context->conversation()[3]->content());
        $this->assertArrayHasKey('call_id', $this->orchestrator->context->conversation()[3]->jsonSerialize());
        $this->assertEquals('call_trlgKnhMpYSC7CFXKw3CceUZ', $this->orchestrator->context->conversation()[3]->jsonSerialize()['call_id']);

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
