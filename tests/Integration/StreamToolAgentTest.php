<?php

namespace Swis\Agents\Tests\Integration;

use Swis\Agents\Agent;
use Swis\Agents\Interfaces\OwnableMessageInterface;
use Swis\Agents\Response\Payload;
use Swis\Agents\Response\ReasoningItem;
use Swis\Agents\Tool;
use Swis\Agents\Tool\Required;
use Swis\Agents\Tool\ToolParameter;
use Swis\Agents\Transporters\ChatCompletionTransporter;

class StreamToolAgentTest extends BaseOrchestratorTestCase
{
    public function testStreamToolAgentInteraction()
    {
        $agent = new Agent(
            name: 'Stream Tool Agent',
            tools: [$this->weatherTool()]
        );

        $response = $this->runAgentTest($agent);

        $conversation = $this->orchestrator->context->conversation();

        $this->assertInstanceOf(ReasoningItem::class, $conversation[2]);

        $this->assertArrayHasKey('location', $conversation[3]->arguments);
        $this->assertEquals('Boston, MA', $conversation[3]->arguments['location']);

        $this->assertEquals('tool', $conversation[4]->role());
        $this->assertEquals('It is currently 20 degrees in Boston, MA', $conversation[4]->content());

        $this->assertEquals('assistant', $conversation[5]->role());
        $this->assertEquals('It\'s 20 degrees in Boston, MA right now.', $conversation[5]->content());

        // Verify final response
        $this->assertSame($agent, $response->owner());
        $this->assertEquals('It\'s 20 degrees in Boston, MA right now.', $response->content());
    }

    public function testStreamToolAgentInteractionWithChatCompletions()
    {
        $agent = new Agent(
            name: 'Stream Tool Agent',
            tools: [$this->weatherTool()],
            transporter: new ChatCompletionTransporter()
        );

        $response = $this->runAgentTest($agent);

        $conversation = $this->orchestrator->context->conversation();

        $this->assertArrayHasKey('location', $conversation[2]->arguments);
        $this->assertEquals('Boston, MA', $conversation[2]->arguments['location']);

        $this->assertEquals('tool', $conversation[3]->role());
        $this->assertEquals('It is currently 20 degrees in Boston, MA', $conversation[3]->content());

        $this->assertEquals('assistant', $conversation[4]->role());
        $this->assertEquals('It\'s 20 degrees in Boston, MA right now.', $conversation[4]->content());

        // Verify final response
        $this->assertSame($agent, $response->owner());
        $this->assertEquals('It\'s 20 degrees in Boston, MA right now.', $response->content());
    }

    protected function runAgentTest(Agent $agent): OwnableMessageInterface
    {
        $tokens = 0;
        $response = $this->orchestrator
            ->withUserInstruction('What is the current weather in Boston, MA?')
            ->runStreamed($agent, function (Payload $token) use (&$tokens) {
                if (! isset($token->content)) {
                    return;
                }
                $tokens++;
            });

        $this->assertInstanceOf(OwnableMessageInterface::class, $response);

        // Verify tokens were streamed in the final response
        $this->assertGreaterThan(0, $tokens);

        // Examine conversation structure
        $conversation = $this->orchestrator->context->conversation();

        // First message is system instruction
        $this->assertEquals('system', $conversation[0]->role());

        // Second message is user question
        $this->assertEquals('user', $conversation[1]->role());
        $this->assertEquals('What is the current weather in Boston, MA?', $conversation[1]->content());

        return $response;
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
