<?php

namespace Swis\Agents\Tests\Integration;

use Swis\Agents\Agent;
use Swis\Agents\Transporters\ChatCompletionTransporter;

class HandoffAgentTest extends BaseOrchestratorTestCase
{
    public function testToolAgentInteraction()
    {
        $targetAgent = new Agent('Target Agent');
        $startAgent = new Agent(
            name: 'Start Agent',
            handoffs: [$targetAgent]
        );

        $response = $this->orchestrator
            ->withUserInstruction('Handoff to Target Agent')
            ->run($startAgent);

        $this->assertSame($targetAgent, $response->owner());
        $this->assertSame('Hello there, how may I assist you today?', $response->content());
    }

    public function testToolAgentInteractionWithChatCompletions()
    {
        $targetAgent = new Agent(
            name: 'Target Agent',
            transporter: new ChatCompletionTransporter()
        );
        $startAgent = new Agent(
            name: 'Start Agent',
            handoffs: [$targetAgent],
            transporter: new ChatCompletionTransporter()
        );

        $response = $this->orchestrator
            ->withUserInstruction('Handoff to Target Agent')
            ->run($startAgent);

        $this->assertSame($targetAgent, $response->owner());
        $this->assertSame('Hello there, how may I assist you today?', $response->content());
    }
}
