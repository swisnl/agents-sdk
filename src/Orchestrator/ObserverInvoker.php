<?php

namespace Swis\Agents\Orchestrator;

use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Response\Payload;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;
use Swis\Agents\Interfaces\AgentInterface;

/**
 * ObserverInvoker class for managing observer notifications.
 * 
 * This class is responsible for:
 * - Broadcasting events to registered agent observers
 * - Broadcasting events to registered tool observers
 * - Centralizing the notification logic
 */
class ObserverInvoker {

    /**
     * Notify all agent observers about a response
     * 
     * @param RunContext $context The run context
     * @param AgentInterface $agent The agent that generated the response
     * @param MessageInterface $message The message that was generated
     * @return void
     */
    public function agentOnResponse(RunContext $context, AgentInterface $agent, MessageInterface $message): void
    {
        foreach ($context->agentObservers() as $observer) {
            $observer->onResponse($agent, $message, $context);
        }
    }

    /**
     * Notify all agent observers about an intermediate response token during streaming
     * 
     * @param RunContext $context The run context
     * @param AgentInterface $agent The agent that generated the response
     * @param Payload $payload The response token payload
     * @return void
     */
    public function agentOnResponseInterval(RunContext $context, AgentInterface $agent, Payload $payload): void
    {
        foreach ($context->agentObservers() as $observer) {
            $observer->onResponseInterval($agent, $payload, $context);
        }
    }

    /**
     * Notify all agent observers about a tool call
     * 
     * @param RunContext $context The run context
     * @param AgentInterface $agent The agent that called the tool
     * @param Tool $tool The tool that was called
     * @param ToolCall $toolCall The tool call details
     * @return void
     */
    public function agentOnToolCall(RunContext $context, AgentInterface $agent, Tool $tool, ToolCall $toolCall): void
    {
        foreach ($context->agentObservers() as $observer) {
            $observer->onToolCall($agent, $tool, $toolCall, $context);
        }
    }

    /**
     * Notify all agent observers after a tool call completes
     * 
     * @param RunContext $context The run context
     * @param AgentInterface $agent The agent that called the tool
     * @param Tool $tool The tool that was called
     * @param ToolCall $toolCall The tool call details
     * @param string|null $toolOutput The output from the tool (if successful)
     * @param bool $success Whether the tool call was successful
     * @return void
     */
    public function agentAfterToolCall(RunContext $context, AgentInterface $agent, Tool $tool, ToolCall $toolCall, ?string $toolOutput, bool $success): void {
        foreach ($context->agentObservers() as $observer) {
            $observer->afterToolCall($agent, $tool, $toolCall, $toolOutput, $success, $context);
        }
    }

    /**
     * Notify all agent observers before an agent is invoked
     * 
     * @param RunContext $context The run context
     * @param AgentInterface $agent The agent that will be invoked
     * @return void
     */
    public function agentBeforeInvoke(RunContext $context, AgentInterface $agent): void
    {
        foreach ($context->agentObservers() as $observer) {
            $observer->beforeInvoke($agent, $context);
        }
    }

    /**
     * Notify all agent observers before a handoff to another agent
     * 
     * @param RunContext $context The run context
     * @param AgentInterface $agent The agent handing off
     * @param AgentInterface $handoffToAgent The agent receiving the handoff
     * @return void
     */
    public function agentBeforeHandoff(
        RunContext $context, 
        AgentInterface $agent, 
        AgentInterface $handoffToAgent
    ): void {
        foreach ($context->agentObservers() as $observer) {
            $observer->beforeHandoff($agent, $handoffToAgent, $context);
        }
    }

    /**
     * Notify all tool observers about a tool call
     * 
     * @param RunContext $context The run context
     * @param Tool $tool The tool that was called
     * @param ToolCall $toolCall The tool call details
     * @return void
     */
    public function toolOnToolCall(RunContext $context, Tool $tool, ToolCall $toolCall): void
    {
        foreach ($context->toolObservers() as $observer) {
            $observer->onToolCall($tool, $toolCall);
        }
    }

    /**
     * Notify all tool observers about a successful tool call
     *
     * @param RunContext $context The run context
     * @param Tool $tool The tool that was called
     * @param ToolCall $toolCall The tool call details
     * @param string|null $toolOutput The output from the tool
     * @return void
     */
    public function toolOnSuccess(RunContext $context, Tool $tool, ToolCall $toolCall, ?string $toolOutput): void
    {
        foreach ($context->toolObservers() as $observer) {
            $observer->onSuccess($tool, $toolCall, $toolOutput);
        }
    }

    /**
     * Notify all tool observers about a failed tool call
     *
     * @param RunContext $context The run context
     * @param Tool $tool The tool that was called
     * @param ToolCall $toolCall The tool call details
     * @param string|null $toolOutput The output from the tool (could also be the error message)
     * @return void
     */
    public function toolOnFailure(RunContext $context, Tool $tool, ToolCall $toolCall, ?string $toolOutput): void
    {
        foreach ($context->toolObservers() as $observer) {
            $observer->onFailure($tool, $toolCall, $toolOutput);
        }
    }
}