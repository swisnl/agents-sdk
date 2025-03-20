<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Agent;
use Swis\Agents\Handoff;
use Swis\Agents\Tool;
use Swis\Agents\Orchestrator;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\AgentObserver;
use Swis\Agents\Interfaces\AgentInterface;

class HandoffTest extends TestCase
{
    /**
     * Test creating a handoff
     */
    public function testHandoffCreation(): void
    {
        $targetAgent = new Agent(
            name: 'Target Agent',
            description: 'An agent to hand off to'
        );
        
        $handoff = new Handoff($targetAgent);
        
        $this->assertEquals('transfer_to_target_agent', $handoff->name());
        $this->assertSame($targetAgent, $handoff->agent);
    }
    
    /**
     * Test the description of the handoff tool
     */
    public function testHandoffDescription(): void
    {
        $targetAgent = new Agent(
            name: 'Weather Agent'
        );
        
        $handoff = new Handoff(
            agent: $targetAgent,
            toolDescription: 'Provides weather information'
        );
        
        $this->assertEquals(
            'Provides weather information',
            $handoff->description()
        );
        
        // Test without agent description
        $targetAgent = new Agent(
            name: 'Weather Agent'
        );
        
        $handoff = new Handoff($targetAgent);
        
        $this->assertEquals(
            'Handoff to the Weather Agent to handle the request.',
            $handoff->description()
        );
    }

    /**
     * Test that agents can be configured with handoffs
     */
    public function testAgentWithHandoffs(): void
    {
        $targetAgent1 = new Agent(name: 'Target 1');
        $targetAgent2 = new Agent(name: 'Target 2');
        
        $agent = new Agent(
            name: 'Main Agent',
            handoffs: [$targetAgent1, $targetAgent2]
        );
        
        $this->assertCount(2, $agent->handoffs());
        $this->assertArrayHasKey('Target 1', $agent->handoffs());
        $this->assertArrayHasKey('Target 2', $agent->handoffs());
    }
    
    /**
     * Test that handoffs are properly converted to tools
     */
    public function testHandoffIsToolInAgent(): void
    {
        $targetAgent = new Agent(name: 'Target');
        
        $agent = new Agent(
            name: 'Main Agent',
            handoffs: [$targetAgent]
        );
        
        // Access protected method via reflection to test
        $reflection = new \ReflectionMethod($agent, 'executableTools');
        $reflection->setAccessible(true);
        
        $tools = $reflection->invoke($agent);

        // Should include handoff tools
        $this->assertArrayHasKey('transfer_to_target', $tools);
        $this->assertInstanceOf(Handoff::class, $tools['transfer_to_target']);
    }
}