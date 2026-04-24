<?php

namespace Swis\Agents\Transporters;

use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Responses\Responses\Output\OutputMessage;
use OpenAI\Responses\Responses\Output\OutputMessageContentOutputText;
use OpenAI\Responses\Responses\Output\OutputReasoning;
use Swis\Agents\Agent;
use Swis\Agents\Helpers\ToolHelper;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Interfaces\Transporter;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\Payload;
use Swis\Agents\Response\ReasoningItem;
use Swis\Agents\Response\ResponsesStreamedResponseWrapper;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;

/**
 * Base class for transporters that speak OpenAI's Responses API.
 *
 * Two concrete variants extend this:
 * - {@see ResponsesTransporter}: stateless, rebuilds the full input each turn and
 *   replays encrypted reasoning items locally.
 * - {@see StatefulResponsesTransporter}: stateful, relies on `previous_response_id`
 *   so the server retains prior turns.
 */
abstract class BasesResponsesTransporter implements Transporter
{
    /**
     * Item types in a Responses API response that represent a tool call.
     *
     * Shared with {@see ResponsesStreamedResponseWrapper}.
     *
     * @var string[]
     */
    public const TOOL_CALL_ITEM_TYPES = [
        'file_search_call',
        'function_call',
        'web_search_call',
        'computer_call',
        'code_interpreter_call',
    ];

    /**
     * Invoke the Responses endpoint for the given agent and context.
     */
    public function invoke(Agent $agent, RunContext $context): void
    {
        $payload = $this->buildRequestPayload($agent, $context);
        $context->startNewRun();

        if ($context->isStreamed()) {
            $this->invokeStreamed($agent, $context, $payload);
        } else {
            $this->invokeDirect($agent, $context, $payload);
        }
    }

    /**
     * Build the request payload for the Responses API.
     *
     * @return array<string,mixed>
     */
    abstract protected function buildRequestPayload(Agent $agent, RunContext $context): array;

    /**
     * Build the `input` array for the request payload.
     *
     * @return array<MessageInterface>
     */
    abstract protected function buildInputs(Agent $agent, RunContext $context): array;

    /**
     * @return string[]
     *
     * @deprecated Use {@see self::TOOL_CALL_ITEM_TYPES}. Kept for BC where callers
     *             instantiate a transporter just to read this list.
     */
    public function applicableToolCallItemTypes(): array
    {
        return self::TOOL_CALL_ITEM_TYPES;
    }

    /**
     * Transform tools into the payload structure expected by the API.
     *
     * @param array<Tool> $tools
     * @return array<int, array<string,mixed>>
     */
    protected function buildToolsPayload(array $tools): array
    {
        return array_values(array_map(fn (Tool $tool) => $this->toolToPayload($tool), $tools));
    }

    /**
     * Convert a single tool into its payload representation.
     *
     * @return array<string, mixed>
     */
    protected function toolToPayload(Tool $tool): array
    {
        return [
            'type' => 'function',
            ...ToolHelper::toolToDefinition($tool),
        ];
    }

    /**
     * Handle a non-streamed Responses request.
     *
     * @param array<string,mixed> $payload
     */
    protected function invokeDirect(Agent $agent, RunContext $context, array $payload): void
    {
        $response = $context->client()->responses()->create($payload);
        $this->handleResponse($agent, $context, $response);
    }

    /**
     * Handle a streamed Responses request.
     *
     * @param array<string,mixed> $payload
     */
    protected function invokeStreamed(Agent $agent, RunContext $context, array $payload): void
    {
        $response = $context->client()->responses()->createStreamed($payload);
        $streamedResponse = new ResponsesStreamedResponseWrapper($response, $agent);

        $message = '';
        $lastPayload = null;

        foreach ($streamedResponse as $responsePayload) {
            $context->observerInvoker()->agentOnResponseInterval($context, $agent, $responsePayload);
            if (isset($responsePayload->content)) {
                $message .= $responsePayload->content;
            }
            $lastPayload = $responsePayload;
        }

        if (! empty($message) && isset($lastPayload)) {
            $lastPayload->content = $message;
            $context->addAgentMessage($lastPayload, $agent);
            if ($context->lastMessage() !== null) {
                $context->observerInvoker()->agentOnResponse($context, $agent, $context->lastMessage());
            }
        }
    }

    /**
     * Process a non-streamed API response.
     */
    protected function handleResponse(Agent $agent, RunContext $context, CreateResponse $response): void
    {
        $this->captureResponseMetadata($agent, $context, $response);

        if ($this->isToolCall($response)) {
            $this->handleToolCallResponse($agent, $context, $response);

            return;
        }

        $payload = $this->payloadFromResponse($response);

        $context->observerInvoker()->agentOnResponseInterval($context, $agent, $payload);
        $context->addAgentMessage($payload, $agent);
        if ($context->lastMessage() !== null) {
            $context->observerInvoker()->agentOnResponse($context, $agent, $context->lastMessage());
        }
    }

    /**
     * Record response-level metadata (response id, reasoning items, ...) on the context.
     */
    protected function captureResponseMetadata(Agent $agent, RunContext $context, CreateResponse $response): void
    {
    }

    /**
     * Extract the assistant text payload from the response output.
     */
    protected function payloadFromResponse(CreateResponse $response): Payload
    {
        $content = '';
        $role = null;
        $itemId = null;
        foreach ($response->output as $item) {
            if (! $item instanceof OutputMessage) {
                continue;
            }

            $contents = array_filter($item->content, fn ($content) => $content instanceof OutputMessageContentOutputText);
            $contents = array_map(fn ($content) => $content->text, $contents);

            $content .= implode($contents);
            $role = $item->role;
            $itemId = $item->id ?: $itemId;
        }

        return new Payload(
            content: $content,
            role: $role,
            choice: 0,
            inputTokens: $response->usage?->inputTokens,
            outputTokens: $response->usage?->outputTokens,
            itemId: $itemId,
        );
    }

    /**
     * Handle responses that contain tool calls.
     */
    protected function handleToolCallResponse(Agent $agent, RunContext $context, CreateResponse $response): void
    {
        $toolCalls = $this->toolCallsFromResponse($response);
        $agent->executeTools($toolCalls);
    }

    /**
     * Determine if the response contains tool calls.
     */
    protected function isToolCall(CreateResponse $response): bool
    {
        foreach ($response->output as $item) {
            if (in_array($item->type, self::TOOL_CALL_ITEM_TYPES, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Extract tool calls from the response.
     *
     * @return array<ToolCall>
     */
    protected function toolCallsFromResponse(CreateResponse $response): array
    {
        $toolCalls = [];
        foreach ($response->output as $item) {
            if (! in_array($item->type, self::TOOL_CALL_ITEM_TYPES, true)) {
                continue;
            }

            $toolCalls[] = new ToolCall(
                tool: $item->name ?? $item->type,
                id: $item->callId ?? $item->id,
                argumentsPayload: $item->arguments ?? null,
                itemId: $item->id ?? null,
            );
        }

        return $toolCalls;
    }

    /**
     * Iterate the response output and return every reasoning item with its
     * encrypted content. Returns an empty array when none are present.
     *
     * @return array<ReasoningItem>
     */
    protected function reasoningItemsFromResponse(CreateResponse $response): array
    {
        $items = [];
        foreach ($response->output as $item) {
            if (! $item instanceof OutputReasoning) {
                continue;
            }

            $items[] = new ReasoningItem(
                id: $item->id,
                encryptedContent: $item->encryptedContent ?? null,
                summary: $this->extractReasoningSummary($item),
            );
        }

        return $items;
    }

    /**
     * Normalise the summary entries of a reasoning output item.
     *
     * @return array<int, array{text: string, type: string}>
     */
    protected function extractReasoningSummary(OutputReasoning $item): array
    {
        return array_map(function ($summary) {
            return $summary->toArray();
        }, $item->summary);
    }
}
