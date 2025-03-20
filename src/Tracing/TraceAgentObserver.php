<?php

namespace Swis\Agents\Tracing;

use Swis\Agents\AgentObserver;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;

/**
 * TraceAgentObserver class for generating and managing execution traces.
 *
 * This observer creates spans for various agent lifecycle events, allowing
 * for detailed tracing of agent execution flows, including message generation,
 * tool calls, and agent handoffs.
 */
class TraceAgentObserver extends AgentObserver
{
    /**
     * The active span for the current agent
     */
    protected ?Span $currentAgentSpan = null;

    /**
     * Reference to the current active agent
     */
    protected ?AgentInterface $currentAgent = null;

    /**
     * Create a new trace agent observer
     *
     * @param Processor $processor The trace processor to use
     */
    public function __construct(protected Processor $processor)
    {
    }

    /**
     * Start a new span when an agent is invoked
     *
     * @param AgentInterface $agent The agent being invoked
     * @param RunContext $context The execution context
     * @return void
     */
    public function beforeInvoke(AgentInterface $agent, RunContext $context): void
    {
        $trace = $this->processor->trace();
        if (! $trace) {
            return;
        }

        // Skip if this is the same agent already being traced
        if (isset($this->currentAgent) && $this->currentAgent->name() === $agent->name()) {
            return;
        }

        $this->currentAgent = $agent;

        // Close any previous agent span before starting a new one
        if ($this->currentAgentSpan) {
            $this->processor->stopSpan($this->currentAgentSpan);
        }

        // Create and start a new span for this agent
        $this->currentAgentSpan = $this->processor->startSpan(SpanFactory::createAgentSpan($agent, $trace));
    }

    /**
     * Create a span for agent response generation
     *
     * @param AgentInterface $agent The agent that generated the response
     * @param MessageInterface $message The generated message
     * @param RunContext $context The execution context
     * @return void
     */
    public function onResponse(AgentInterface $agent, MessageInterface $message, RunContext $context): void
    {
        $trace = $this->processor->trace();
        if (! $trace || ! $this->currentAgentSpan) {
            return;
        }

        // Create a generation span that starts after the previous span
        $span = $this->processor->startSpanAfterPrevious(SpanFactory::createGenerationSpan([$message], $context, $trace, $this->currentAgentSpan));

        // The response is already complete, so we can directly stop this span
        $this->processor->stopSpan($span);
    }

    /**
     * Create a span for agent handoff events
     *
     * @param AgentInterface $agent The agent handing off the conversation
     * @param AgentInterface $handoffToAgent The agent receiving the handoff
     * @param RunContext $context The execution context
     * @return void
     */
    public function beforeHandoff(AgentInterface $agent, AgentInterface $handoffToAgent, RunContext $context): void
    {
        $trace = $this->processor->trace();
        if (! $trace || ! $this->currentAgentSpan) {
            return;
        }

        // Create a handoff span as a child of the current agent span
        $span = $this->processor->startSpan(SpanFactory::createHandoffSpan($agent, $handoffToAgent, $trace, $this->currentAgentSpan));

        // Handoffs are instant operations, so we can close the span immediately
        $this->processor->stopSpan($span);
    }

    /**
     * Start a span for tool call operations
     *
     * @param AgentInterface $agent The agent initiating the tool call
     * @param Tool $tool The tool being called
     * @param ToolCall $toolCall The tool call details
     * @param RunContext $context The execution context
     * @return void
     */
    public function onToolCall(AgentInterface $agent, Tool $tool, ToolCall $toolCall, RunContext $context): void
    {
        $trace = $this->processor->trace();
        if (! $trace || ! $this->currentAgentSpan) {
            return;
        }

        // Create a tool span as a child of the current agent span
        $this->processor->startSpan(SpanFactory::createToolSpan($tool, $toolCall, null, $trace, $this->currentAgentSpan));
    }

    /**
     * Complete the tool call span and record results
     *
     * @param AgentInterface $agent The agent that initiated the tool call
     * @param Tool $tool The tool that was called
     * @param ToolCall $toolCall The tool call details
     * @param string|null $toolOutput The output returned by the tool (or error message)
     * @param bool $success Whether the tool call was successful
     * @param RunContext $context The execution context
     * @return void
     */
    public function afterToolCall(AgentInterface $agent, Tool $tool, ToolCall $toolCall, ?string $toolOutput, bool $success, RunContext $context): void
    {
        // Stop the current span (should be the tool span started in onToolCall)
        $span = $this->processor->stopCurrent();

        // Add the tool output to the span data
        $span->spanData['output'] = $toolOutput;

        // Mark the span as an error if the tool call failed
        if (! $success) {
            $span->withError($toolOutput);
        }
    }
}
