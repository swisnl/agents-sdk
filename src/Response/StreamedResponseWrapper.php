<?php

namespace Swis\Agents\Response;

use Generator;
use IteratorAggregate;
use OpenAI\Responses\Chat\CreateStreamedResponse;
use OpenAI\Responses\StreamResponse;
use Swis\Agents\Exceptions\UnparsableToolCallException;
use Swis\Agents\Interfaces\AgentInterface;
use Throwable;

/**
 * Wraps a streamed response from OpenAI and handles tool calls.
 *
 * @implements IteratorAggregate<Payload>
 */
class StreamedResponseWrapper implements IteratorAggregate
{
    protected bool $isFinished = false;
    protected array $generated = [];
    protected ?string $currentToolCallId = null;
    protected array $capturedToolCalls = [];

    /**
     * @param \OpenAI\Responses\StreamResponse<\OpenAI\Responses\Chat\CreateStreamedResponse> $response
     * @param \Swis\Agents\Interfaces\AgentInterface $agent
     */
    public function __construct(
        protected StreamResponse $response,
        protected AgentInterface $agent
    ) {
    }

    protected function isApplicableResponse(CreateStreamedResponse $response): bool
    {
        return isset($response->choices[0]->delta);
    }

    protected function isToolCall(CreateStreamedResponse $response): bool
    {
        return ! empty($response->choices[0]->delta->toolCalls) && isset($response->choices[0]->delta->toolCalls[0]->function);
    }

    protected function captureToolCallStream(CreateStreamedResponse $response): void
    {
        $toolCalls = $response->choices[0]->delta->toolCalls;
        foreach ($toolCalls as $toolCall) {
            if (isset($toolCall->id, $toolCall->function, $toolCall->function->name)) {
                $this->currentToolCallId = $toolCall->id;
                $this->capturedToolCalls[$this->currentToolCallId] = [
                    'name' => $toolCall->function->name,
                    'arguments' => '',
                ];
            }

            $this->capturedToolCalls[$this->currentToolCallId]['arguments'] .= $toolCall->function->arguments;
        }
    }

    protected function shouldHandleToolCall(CreateStreamedResponse $response): bool
    {
        return $response->choices[0]->finishReason === 'tool_calls'
            || $response->choices[0]->finishReason === 'stop' && ! empty($this->capturedToolCalls);
    }

    protected function handleToolCall(): void
    {
        if (empty($this->capturedToolCalls)) {
            return;
        }

        $toolCalls = [];
        foreach ($this->capturedToolCalls as $toolCallId => $toolCallData) {
            try {
                $toolCall = new ToolCall(
                    tool: $toolCallData['name'],
                    id: $toolCallId,
                    argumentsPayload: $toolCallData['arguments'],
                );
            } catch (Throwable $e) {
                throw UnparsableToolCallException::forToolCallId($toolCallId, $e->getMessage());
            }

            $toolCalls[] = $toolCall;
        }

        $this->agent->executeTools($toolCalls);
    }

    protected function getPayload(CreateStreamedResponse $response): Payload
    {
        return new Payload(
            content: $response->choices[0]->delta->content ?? null,
            role: $response->choices[0]->delta->role ?? null,
            choice: $response->choices[0]->index ?? 0,
            inputTokens: $response->usage?->promptTokens,
            outputTokens: $response->usage?->completionTokens,
        );
    }

    /**
     * @return Generator<Payload>
     */
    public function getIterator(): Generator
    {
        $generated = $this->generated;
        if (!$this->isFinished) {
            $generated = $this->response->getIterator();
        }

        foreach ($generated as $response) {
            $this->generated[] = $response;

            if (!$this->isApplicableResponse($response)) {
                continue;
            }

            if ($this->isToolCall($response)) {
                $this->captureToolCallStream($response);

                continue;
            }

            if ($this->shouldHandleToolCall($response)) {
                $this->handleToolCall();

                break;
            }

            yield $this->getPayload($response);
        }

        $this->isFinished = true;
    }
}
