<?php

namespace Swis\Agents;

use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\Payload;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Interfaces\AgentInterface;

/**
 * Base AgentObserver class for monitoring and responding to agent lifecycle events.
 * 
 * This abstract class defines the observer interface for agent events.
 * Implementations can extend this class to add behavior at various points 
 * in the agent lifecycle, such as when responses are generated, tools are called,
 * or handoffs occur between agents.
 */
abstract class AgentObserver
{
    /**
     * Called when an agent generates a complete response message
     *
     * @param AgentInterface $agent The agent that generated the response
     * @param MessageInterface $message The complete message that was generated
     * @param RunContext $context The execution context
     * @return void
     */
    public function onResponse(AgentInterface $agent, MessageInterface $message, RunContext $context): void
    {
    }

    /**
     * Called during streaming when a partial response token is received
     *
     * @param AgentInterface $agent The agent that generated the response token
     * @param Payload $payload The response payload
     * @param RunContext $context The execution context
     * @return void
     */
    public function onResponseInterval(AgentInterface $agent, Payload $payload, RunContext $context): void
    {
    }

    /**
     * Called when an agent initiates a tool call
     *
     * @param AgentInterface $agent The agent initiating the tool call
     * @param Tool $tool The tool being called
     * @param ToolCall $toolCall The tool call details including parameters
     * @param RunContext $context The execution context
     * @return void
     */
    public function onToolCall(AgentInterface $agent, Tool $tool, ToolCall $toolCall, RunContext $context): void
    {
    }

    /**
     * Called after a tool call has completed (successfully or with error)
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
    }

    /**
     * Called immediately before an agent is invoked
     *
     * @param AgentInterface $agent The agent about to be invoked
     * @param RunContext $context The execution context
     * @return void
     */
    public function beforeInvoke(AgentInterface $agent, RunContext $context): void
    {
    }

    /**
     * Called when the conversation is handed off from one agent to another
     *
     * @param AgentInterface $agent The agent handing off the conversation
     * @param AgentInterface $handoffToAgent The agent receiving the handoff
     * @param RunContext $context The execution context
     * @return void
     */
    public function beforeHandoff(AgentInterface $agent, AgentInterface $handoffToAgent, RunContext $context): void
    {
    }
}
