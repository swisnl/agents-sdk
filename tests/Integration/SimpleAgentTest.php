<?php

namespace Swis\Agents\Tests\Integration;

use Swis\Agents\Agent;

class SimpleAgentTest extends BaseOrchestratorTestCase
{
    public function testSimpleAgentInteraction()
    {
        $agent = new Agent('Simple Agent');
        $response = $this->orchestrator->run($agent);

        $this->assertSame($agent, $response->owner());
        $this->assertEquals('Hello there, how may I assist you today?', $response->content());
    }
}