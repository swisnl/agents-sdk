<?php

namespace Swis\Agents\Traits;

use OpenAI\Responses\Chat\CreateResponse;
use Swis\Agents\Exceptions\BuildToolException;
use Swis\Agents\Exceptions\HandleToolException;
use Swis\Agents\Handoff;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;
use Swis\Agents\Helpers\ToolHelper;
use Swis\Agents\Tool\ToolOutput;
use Throwable;

/**
 * Provides functionality for working with tools in agents.
 * 
 * This trait handles the lifecycle of tool calls from a model response:
 * - Extracting tool calls from model responses
 * - Building tool instances with arguments
 * - Executing tools and handling their output
 * - Converting tool definitions to API payloads
 * - Supporting agent handoffs
 */
trait HasToolCallingTrait
{
    /**
     * Execute a list of tool calls.
     * 
     * Processes each tool call in sequence, handling special cases
     * like handoffs. After all tools are executed, reinvokes the agent.
     * 
     * @param array<ToolCall> $toolCalls List of tool calls to execute
     */
    public function executeTools(array $toolCalls): void
    {
        foreach ($toolCalls as $toolCall) {
            $tool = $this->buildTool($toolCall);

            // Special handling for agent handoffs
            if ($tool instanceof Handoff) {
                $this->executeHandoff($tool);

                // No further processing after handoff
                return;
            }

            $this->executeTool($tool, $toolCall);
        }

        // After all tools are executed, give control back to the agent
        $this->invoke();
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
     */
    public function executeTool(Tool $tool, ToolCall $toolCall): void
    {
        $context = $this->orchestrator->context;

        // Notify observers of the tool call
        $context->observerInvoker()->agentOnToolCall($context, $this, $tool, $toolCall);
        $context->observerInvoker()->toolOnToolCall($context, $tool, $toolCall);

        // Add the tool call to the conversation context
        $context->addMessage($toolCall);
        
        // Execute the tool and handle its result
        $this->invokeTool($tool, $toolCall, $context);
    }

    /**
     * Execute a handoff to another agent.
     * 
     * Transfers control from the current agent to another agent
     * through a handoff tool.
     * 
     * @param Handoff $handoffTool The handoff tool containing the target agent
     */
    public function executeHandoff(Handoff $handoffTool): void
    {
        // Notify observers about the upcoming handoff
        $this->orchestrator->context->observerInvoker()->agentBeforeHandoff(
            $this->orchestrator->context,
            $this,
            $handoffTool->agent,
        );

        // Execute the handoff, which will transfer control to the new agent
        $handoffTool();
    }

    /**
     * Build a tool instance from a tool call.
     * 
     * Creates a new instance of the requested tool with the
     * provided arguments set as properties.
     * 
     * @param ToolCall $toolCall The tool call containing tool name and arguments
     * @return Tool The instantiated tool with arguments set
     * @throws BuildToolException If the tool cannot be built
     */
    protected function buildTool(ToolCall $toolCall): Tool
    {
        $tools = $this->executableTools();

        try {
            // Create a new instance of the tool by cloning the prototype
            $tool = clone $tools[$toolCall->tool];

            // Set each argument as a property on the tool
            $arguments = $toolCall->arguments;
            foreach ($arguments as $argument => $value) {
                $tool->{$argument} = $value;
            }

            return $tool;
        }
        catch (Throwable $e) {
            throw BuildToolException::forToolCall($toolCall, $e->getMessage());
        }
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
     */
    protected function invokeTool(Tool $tool, ToolCall $toolCall, RunContext $context): void
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
        $context->observerInvoker()->agentAfterToolCall($context, $this, $tool, $toolCall, $result, $success);

        // Create and add tool output message to the context
        $toolOutput = new ToolOutput($result, $toolCall->id);
        $context->addMessage($toolOutput);
    }

    /**
     * Check if a model response contains tool calls.
     * 
     * @param CreateResponse $response The model response to check
     * @return bool True if the response contains tool calls
     */
    protected function isToolCall(CreateResponse $response): bool
    {
        return !empty($response->choices[0]?->message?->toolCalls);
    }

    /**
     * Extract tool calls from a model response.
     * 
     * Converts the raw API response format into ToolCall objects
     * that can be processed by the agent.
     * 
     * @param CreateResponse $response The model response containing tool calls
     * @return array<ToolCall> List of extracted tool calls
     */
    protected function toolCallsFromResponse(CreateResponse $response): array
    {
        $toolCalls = [];

        foreach ($response->choices[0]->message->toolCalls as $toolCallData) {
            $toolCall = new ToolCall(
                tool: $toolCallData->function->name,
                id: $toolCallData->id,
                argumentsPayload: $toolCallData->function->arguments,
            );

            $toolCalls[] = $toolCall;
        }

        return $toolCalls;
    }

    /**
     * Convert a list of tools to API payload format.
     * 
     * Transforms tool objects into the structure expected by the LLM API.
     * 
     * @param array<Tool> $tools List of tool objects to convert
     * @return array List of tool definitions in API format
     */
    protected function buildToolsPayload(array $tools): array
    {
        return array_values(array_map(fn (Tool $tool) => $this->toolToPayload($tool), $tools));
    }

    /**
     * Convert a single tool to API payload format.
     * 
     * @param Tool $tool The tool to convert
     * @return array The tool definition in API format
     */
    protected function toolToPayload(Tool $tool): array
    {
        return [
            'type' => 'function',
            'function' => ToolHelper::toolToDefinition($tool),
        ];
    }
}
