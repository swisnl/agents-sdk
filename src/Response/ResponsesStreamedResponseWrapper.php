<?php

namespace Swis\Agents\Response;

use Generator;
use IteratorAggregate;
use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Responses\Responses\CreateStreamedResponse;
use OpenAI\Responses\Responses\Streaming\OutputItem;
use OpenAI\Responses\Responses\Streaming\OutputTextDelta;
use OpenAI\Responses\StreamResponse;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Transporters\ResponsesTransporter;

/**
 * Basic streamed response wrapper for the Responses endpoint.
 *
 * @implements IteratorAggregate<Payload>
 */
class ResponsesStreamedResponseWrapper implements IteratorAggregate
{
    protected bool $isFinished = false;

    /** @var array<CreateStreamedResponse> */
    protected array $generated = [];

    /**
     * @param StreamResponse<CreateStreamedResponse> $response The streamed response from the API.
     * @param AgentInterface $agent The agent handling tool calls.
     */
    public function __construct(
        protected StreamResponse $response,
        protected AgentInterface $agent
    ) {
    }

    /**
     * Extract text from the streamed response chunk.
     *
     * @param OutputTextDelta $response
     * @return string
     */
    protected function getText(OutputTextDelta $response): string
    {
        return $response->delta;
    }

    /**
     * Determine if the response chunk contains tool calls.
     */
    protected function isToolCall(mixed $response): bool
    {
        if (! $response instanceof OutputItem) {
            return false;
        }

        // Ensure the tool call and all of its arguments are completely generated before proceeding
        if (isset($response->item->status) && $response->item->status !== 'completed') {
            return false;
        }

        if (! in_array($response->item->type, $this->applicableToolCallItemTypes())) {
            return false;
        }

        return true;
    }

    /**
     * Returns the list of item types that should be handled as Tool call
     *
     * @return string[]
     */
    protected function applicableToolCallItemTypes(): array
    {
        return (new ResponsesTransporter())->applicableToolCallItemTypes();
    }

    /**
     * Capture Tool call from the response output item.
     *
     * @param OutputItem $response
     * @return ToolCall
     */
    protected function captureToolCall(OutputItem $response): ToolCall
    {
        return new ToolCall(
            tool: $response->item->name ?? $response->item->type,
            id: $response->item->callId ?? $response->item->id,
            argumentsPayload: $response->item->arguments ?? null,
        );
    }

    /**
     * @return Generator<Payload>
     */
    public function getIterator(): Generator
    {
        $generated = $this->generated;
        if (! $this->isFinished) {
            $generated = $this->response->getIterator();
        }

        /** @var OutputItem|null $currentItem */
        $currentItem = null;

        /** @var array<ToolCall> $capturedToolCalls */
        $capturedToolCalls = [];
        foreach ($generated as $response) {
            assert($response instanceof CreateStreamedResponse);
            $this->generated[] = $response;

            if ($response->event === 'response.created' && $response->response instanceof CreateResponse) {
                $this->agent->orchestrator()->context->withPreviousResponseId($response->response->id);

                continue;
            }

            if ($response->response instanceof OutputItem) {
                $currentItem = $response->response;
            }

            if ($response->response instanceof OutputTextDelta) {
                yield new Payload(content: $this->getText($response->response), role: $currentItem?->item->role ?? null);

                continue;
            }

            // Start collecting all parallel Tool calls,
            if ($this->isToolCall($response->response)) {
                assert($response->response instanceof OutputItem);
                $capturedToolCalls[] = $this->captureToolCall($response->response);
            }

            // and execute them when done collecting
            if (! empty($capturedToolCalls) && $response->event === 'response.completed') {
                $this->agent->executeTools($capturedToolCalls);

                break;
            }
        }

        $this->isFinished = true;
    }
}
