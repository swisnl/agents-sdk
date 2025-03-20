<?php

namespace Swis\Agents\Tracing;

use Swis\Agents\Agent;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;

/**
 * Factory for creating different types of spans to track agent operations.
 * 
 * This class provides specialized methods for creating spans that represent
 * different operations in the agent workflow, such as agent initialization,
 * tool calls, message generation, and agent handoffs.
 */
class SpanFactory
{
    /**
     * Span type for agent lifecycle events
     */
    const SPAN_TYPE_AGENT = 'agent';
    
    /**
     * Span type for tool/function calls
     */
    const SPAN_TYPE_TOOL = 'function';
    
    /**
     * Span type for LLM response generation
     */
    const SPAN_TYPE_GENERATION = 'generation';
    
    /**
     * Span type for agent handoff events
     */
    const SPAN_TYPE_HANDOFF = 'handoff';

    /**
     * Create a span that represents an agent's execution lifecycle.
     * 
     * This span captures agent metadata like available tools and handoffs.
     *
     * @param Agent $agent The agent being traced
     * @param Trace $trace The parent trace
     * @param Span|null $parent Optional parent span
     * @return Span
     */
    public static function createAgentSpan(Agent $agent, Trace $trace, ?Span $parent = null): Span
    {
        return new Span(
            traceId: $trace->id,
            parentId: $parent?->id,
            spanData: [
                'type' => self::SPAN_TYPE_AGENT,
                'name' => $agent->name(),
                'handoffs' => array_keys($agent->handoffs()),
                'tools' => array_keys($agent->tools()),
            ],
        );
    }

    /**
     * Create a span for a tool call operation.
     * 
     * This span captures the input parameters and output results of a tool invocation.
     *
     * @param Tool $tool The tool being called
     * @param ToolCall $toolCall Details of the tool call including arguments
     * @param string|null $output Result of the tool call or error message
     * @param Trace $trace The parent trace
     * @param Span|null $parent Optional parent span (typically the agent span)
     * @return Span
     */
    public static function createToolSpan(Tool $tool, ToolCall $toolCall, ?string $output, Trace $trace, ?Span $parent = null): Span
    {
        return new Span(
            traceId: $trace->id,
            parentId: $parent?->id,
            spanData: [
                'type' => self::SPAN_TYPE_TOOL,
                'name' => $tool->name(),
                'input' => $toolCall->argumentsPayload,
                'output' => $output,
            ],
        );
    }

    /**
     * Create a span for an LLM message generation operation.
     * 
     * This span captures the prompts, responses, model information, and usage statistics
     * for a language model generation event.
     *
     * @param array<MessageInterface> $messages The generated messages
     * @param RunContext $context The execution context containing the conversation history
     * @param Trace $trace The parent trace
     * @param Span|null $parent Optional parent span (typically the agent span)
     * @return Span
     */
    public static function createGenerationSpan(array $messages, RunContext $context, Trace $trace, ?Span $parent = null): Span
    {
        $input = array_diff($context->conversation(), $messages);
        $lastMessage = end($messages);

        return new Span(
            traceId: $trace->id,
            parentId: $parent?->id,
            spanData: [
                'type' => self::SPAN_TYPE_GENERATION,
                'input' => $input,
                'output' => $messages,
                'model' => $lastMessage->owner()?->modelSettings()->modelName,
                'model_config' => [
                    'temperature' => $lastMessage->owner()?->modelSettings()->temperature ?? .7,
                ],
                'usage' => array_map(fn($usage) => $usage ?? 0, $lastMessage->usage()),
            ],
        );
    }

    /**
     * Create a span for an agent handoff event.
     * 
     * This span captures when control of a conversation is transferred
     * from one agent to another.
     *
     * @param Agent $from The agent handing off control
     * @param Agent $to The agent receiving control
     * @param Trace $trace The parent trace
     * @param Span|null $parent Optional parent span
     * @return Span
     */
    public static function createHandoffSpan(Agent $from, Agent $to, Trace $trace, ?Span $parent = null): Span
    {
        return new Span(
            traceId: $trace->id,
            parentId: $parent?->id,
            spanData: [
                'type' => self::SPAN_TYPE_HANDOFF,
                'from_agent' => $from->name(),
                'to_agent' => $to->name(),
            ],
        );
    }
}