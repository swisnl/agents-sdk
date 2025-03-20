<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Orchestrator;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\AgentObserver;
use Swis\Agents\ToolObserver;
use Swis\Agents\Message;
use Swis\Agents\Tracing\Processor;
use Swis\Agents\Interfaces\TracingProcessorInterface;
use Swis\Agents\Interfaces\TracingExporterInterface;

class OrchestratorTest extends TestCase
{
    /**
     * Test Orchestrator instantiation
     */
    public function testOrchestratorCreation(): void
    {
        $orchestrator = new Orchestrator('TestWorkflow');
        
        $this->assertInstanceOf(Orchestrator::class, $orchestrator);
        $this->assertInstanceOf(RunContext::class, $orchestrator->context);
    }
    
    /**
     * Test Orchestrator with a custom run context
     */
    public function testOrchestratorWithCustomContext(): void
    {
        $customContext = new RunContext();
        $orchestrator = new Orchestrator('TestWorkflow', $customContext);
        
        $this->assertSame($customContext, $orchestrator->context);
    }
    
    /**
     * Test Orchestrator with a user instruction
     */
    public function testOrchestratorWithUserInstruction(): void
    {
        $orchestrator = new Orchestrator('TestWorkflow');
        $orchestrator->withUserInstruction('Test user instruction');
        
        $messages = $orchestrator->context->conversation();
        $lastMessage = end($messages);
        
        $this->assertEquals(Message::ROLE_USER, $lastMessage->role());
        $this->assertEquals('Test user instruction', $lastMessage->content());
    }
    
    /**
     * Test Orchestrator with agent observers
     */
    public function testOrchestratorWithAgentObserver(): void
    {
        $observer = $this->createMock(AgentObserver::class);
        
        $orchestrator = new Orchestrator('TestWorkflow');
        $orchestrator->withAgentObserver($observer);

        $reflectionProperty = new \ReflectionProperty($orchestrator->context, 'agentObservers');
        $reflectionProperty->setAccessible(true);
        
        $this->assertCount(1, $reflectionProperty->getValue($orchestrator->context));
    }
    
    /**
     * Test Orchestrator with tool observers
     */
    public function testOrchestratorWithToolObserver(): void
    {
        $observer = $this->createMock(ToolObserver::class);
        
        $orchestrator = new Orchestrator('TestWorkflow');
        $orchestrator->withToolObserver($observer);

        $reflectionProperty = new \ReflectionProperty($orchestrator->context, 'toolObservers');
        $reflectionProperty->setAccessible(true);

        $this->assertCount(1, $reflectionProperty->getValue($orchestrator->context));
    }
    
    /**
     * Test Orchestrator's tracing functionality
     */
    public function testOrchestratorTracing(): void
    {
        // Test enabling tracing
        $orchestrator = new Orchestrator('TestWorkflow');
        $orchestrator->enableTracing();
        
        $reflectionProperty = new \ReflectionProperty(Orchestrator::class, 'tracingEnabled');
        $reflectionProperty->setAccessible(true);
        $tracingEnabled = $reflectionProperty->getValue($orchestrator);
        
        $this->assertTrue($tracingEnabled);
        
        // Test disabling tracing
        $orchestrator->disableTracing();
        $tracingEnabled = $reflectionProperty->getValue($orchestrator);
        
        $this->assertFalse($tracingEnabled);
        
        // Test custom tracing processor
        $mockProcessor = $this->createMock(TracingProcessorInterface::class);
        $orchestrator->withTracingProcessor($mockProcessor);
        
        $reflectionProperty = new \ReflectionProperty(Orchestrator::class, 'tracingProcessor');
        $reflectionProperty->setAccessible(true);
        $tracingProcessor = $reflectionProperty->getValue($orchestrator);
        
        $this->assertSame($mockProcessor, $tracingProcessor);
    }
    
    /**
     * Test Orchestrator's agent role configuration
     */
    public function testOrchestratorAgentRole(): void
    {
        $orchestrator = new Orchestrator('TestWorkflow');
        $orchestrator->withAgentRole('custom_role');
        
        $reflectionProperty = new \ReflectionProperty(Orchestrator::class, 'agentRole');
        $reflectionProperty->setAccessible(true);
        $agentRole = $reflectionProperty->getValue($orchestrator);
        
        $this->assertEquals('custom_role', $agentRole);
    }
}