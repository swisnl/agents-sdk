<?php

namespace Swis\Agents\Tests\Integration;

use Swis\Agents\Agent;
use Swis\Agents\Transporters\ChatCompletionTransporter;

class SimpleAgentTest extends BaseOrchestratorTestCase
{
    public function testSimpleAgentInteraction()
    {
        $agent = new Agent('Simple Agent');
        $response = $this->orchestrator->run($agent);

        $this->assertSame($agent, $response->owner());
        $this->assertEquals('Hello there, how may I assist you today?', $response->content());
    }

    public function testSimpleAgentInteractionWitChatCompletions()
    {
        $agent = new Agent(
            name: 'Simple Agent',
            transporter: new ChatCompletionTransporter()
        );
        $response = $this->orchestrator->run($agent);

        $this->assertSame($agent, $response->owner());
        $this->assertEquals('Hello there, how may I assist you today?', $response->content());
    }
}
