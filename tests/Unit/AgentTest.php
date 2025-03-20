<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Agent;
use Swis\Agents\Orchestrator;
use Swis\Agents\Tool;

class AgentTest extends TestCase
{
    /**
     * Test agent creation with basic properties
     */
    public function testAgentCreation(): void
    {
        $agent = new Agent(
            name: 'TestAgent',
            description: 'A test agent',
            instruction: 'Test instruction'
        );

        $this->assertEquals('TestAgent', $agent->name());
        $this->assertEquals('A test agent', $agent->description());
        $this->assertEquals('Test instruction', $agent->instruction());
    }

    /**
     * Test that agent properties can be set using closure functions
     */
    public function testAgentWithClosures(): void
    {
        $context = new Orchestrator\RunContext();
        $orchestrator = new Orchestrator(context: $context);
        
        $agent = new Agent(
            name: fn() => 'Dynamic Agent',
            description: fn() => 'Dynamic description',
            instruction: fn() => 'Dynamic instruction'
        );
        
        $agent->setOrchestrator($orchestrator);
        
        $this->assertEquals('Dynamic Agent', $agent->name());
        $this->assertEquals('Dynamic description', $agent->description());
        $this->assertEquals('Dynamic instruction', $agent->instruction());
    }

    /**
     * Test that tools can be added to an agent
     */
    public function testAgentWithTools(): void
    {
        $mockTool = $this->createMock(Tool::class);
        $mockTool->method('name')->willReturn('mock_tool');
        
        $agent = new Agent(
            name: 'TestAgent',
            tools: [$mockTool]
        );
        
        $this->assertCount(1, $agent->tools());
        $this->assertArrayHasKey('mock_tool', $agent->tools());
    }

    /**
     * Test that handoffs can be added to an agent
     */
    public function testAgentWithHandoffs(): void
    {
        $handoffAgent = new Agent(name: 'HandoffAgent');
        
        $agent = new Agent(
            name: 'TestAgent',
            handoffs: [$handoffAgent]
        );
        
        $this->assertCount(1, $agent->handoffs());
        $this->assertArrayHasKey('HandoffAgent', $agent->handoffs());
    }

    /**
     * Test that tools and handoffs can be added after creation
     */
    public function testAddToolsAndHandoffsAfterCreation(): void
    {
        $agent = new Agent(name: 'TestAgent');
        
        $mockTool = $this->createMock(Tool::class);
        $mockTool->method('name')->willReturn('mock_tool');
        
        $handoffAgent = new Agent(name: 'HandoffAgent');
        
        $agent->withTool($mockTool);
        $agent->withHandoff($handoffAgent);
        
        $this->assertCount(1, $agent->tools());
        $this->assertCount(1, $agent->handoffs());
    }

    /**
     * Test handoff instruction customization
     */
    public function testCustomHandoffInstruction(): void
    {
        $agent = new Agent(name: 'TestAgent');
        
        $customInstruction = 'Custom handoff instruction';
        $agent->withHandoffInstruction($customInstruction);
        
        $this->assertEquals($customInstruction, $agent->handoffInstruction());
    }
}