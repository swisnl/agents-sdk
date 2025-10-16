<?php

namespace Swis\Agents\Tests\Integration;

use Swis\Agents\Agent;
use Swis\Agents\Response\Payload;
use Swis\Agents\Transporters\ChatCompletionTransporter;

class StreamAgentTest extends BaseOrchestratorTestCase
{
    public function testSimpleAgentInteraction()
    {
        $agent = new Agent('Stream Agent');

        $tokenCount = 0;
        $response = $this->orchestrator->runStreamed($agent, function (Payload $token) use (&$tokenCount) {
            if (! isset($token->content)) {
                return;
            }

            $tokenCount++;
        });

        $this->assertEquals(9, $tokenCount);
        $this->assertSame($agent, $response->owner());
        $this->assertEquals('Hi! How can I help you today?', $response->content());
    }

    public function testSimpleAgentInteractionWitChatCompletions()
    {
        $agent = new Agent(
            name: 'Stream Agent',
            transporter: new ChatCompletionTransporter()
        );

        $tokenCount = 0;
        $response = $this->orchestrator->runStreamed($agent, function (Payload $token) use (&$tokenCount) {
            if (! isset($token->content)) {
                return;
            }

            $tokenCount++;
        });

        $this->assertEquals(9, $tokenCount);
        $this->assertSame($agent, $response->owner());
        $this->assertEquals('Hello! How can I help you today?', $response->content());
    }
}
