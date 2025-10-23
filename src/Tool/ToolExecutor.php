<?php

namespace Swis\Agents\Tool;

use Swis\Agents\Agent;
use Swis\Agents\Exceptions\HandleToolException;
use Swis\Agents\Interfaces\ToolExecutorInterface;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;

class ToolExecutor implements ToolExecutorInterface
{
    /**
     * Execute multiple tools.
     *
     * @param array<array{0: Tool, 1: ToolCall}> $tools The tools to execute
     * @param Agent $agent The Agent calling the tools
     */
    public function executeTools(array $tools, Agent $agent): void
    {
        foreach ($tools as [$tool, $toolCall]) {
            $this->executeTool($tool, $toolCall, $agent);
        }
    }

    /**
     * Execute a single tool.
     *
     * Handles the lifecycle of a tool execution including:
     * - Notifying observers before execution
     * - Adding the tool call to the context
     * - Invoking the tool
     *
     * @param Tool $tool The tool instance to execute
     * @param ToolCall $toolCall The original tool call request
     * @param Agent $agent The Agent calling the tool
     */
    public function executeTool(Tool $tool, ToolCall $toolCall, Agent $agent): void
    {
        $context = $agent->orchestrator()->context;

        // Notify observers of the tool call
        $context->observerInvoker()->agentOnToolCall($context, $agent, $tool, $toolCall);
        $context->observerInvoker()->toolOnToolCall($context, $tool, $toolCall);

        // Add the tool call to the conversation context
        $context->addMessage($toolCall);

        // Execute the tool and handle its result
        $this->invokeTool($tool, $toolCall, $context, $agent);
    }

    /**
     * Invoke a tool and handle its result.
     *
     * Executes the tool, notifies observers of success or failure,
     * and adds the tool output to the conversation context.
     *
     * @param Tool $tool The tool to invoke
     * @param ToolCall $toolCall The original tool call
     * @param RunContext $context The current run context
     * @param Agent $agent The Agent calling the tool
     */
    protected function invokeTool(Tool $tool, ToolCall $toolCall, RunContext $context, Agent $agent): void
    {
        $success = false;

        try {
            // Execute the tool and get its result
            $result = $tool();
            $context->observerInvoker()->toolOnSuccess($context, $tool, $toolCall, $result);
            $success = true;
        } catch (HandleToolException $e) {
            // Handle tool execution errors
            $result = $e->toPayload();
            $context->observerInvoker()->toolOnFailure($context, $tool, $toolCall, $result);
        }

        // Notify observers after tool execution
        $context->observerInvoker()->agentAfterToolCall($context, $agent, $tool, $toolCall, $result, $success);

        // Create and add tool output message to the context
        $toolOutput = new ToolOutput((string)$result, $toolCall->id);
        $context->addMessage($toolOutput);
    }
}
