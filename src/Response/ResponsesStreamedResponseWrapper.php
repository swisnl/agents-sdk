<?php

namespace Swis\Agents\Response;

use Generator;
use IteratorAggregate;
use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Responses\Responses\CreateStreamedResponse;
use OpenAI\Responses\Responses\Output\OutputFunctionToolCall;
use OpenAI\Responses\Responses\Output\OutputMessage;
use OpenAI\Responses\Responses\Output\OutputReasoning;
use OpenAI\Responses\Responses\Output\OutputReasoningSummary;
use OpenAI\Responses\Responses\Streaming\OutputItem;
use OpenAI\Responses\Responses\Streaming\OutputTextDelta;
use OpenAI\Responses\Responses\Streaming\ReasoningSummaryTextDelta;
use OpenAI\Responses\Responses\Streaming\ReasoningSummaryTextDone;
use OpenAI\Responses\StreamResponse;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Message;
use Swis\Agents\Transporters\BasesResponsesTransporter;

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

    /** @var array<ReasoningItem> */
    protected array $reasoningItems = [];

    /**
     * The id (msg_*) of the assistant message item being streamed, if any.
     */
    protected ?string $assistantItemId = null;

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
     * Reasoning items captured from `response.output_item.done` events.
     *
     * @return array<ReasoningItem>
     */
    public function reasoningItems(): array
    {
        return $this->reasoningItems;
    }

    /**
     * The id (msg_*) of the assistant message item streamed during the response,
     * or null when the response contained no assistant message.
     */
    public function assistantItemId(): ?string
    {
        return $this->assistantItemId;
    }

    /**
     * Extract text from the streamed response chunk.
     */
    protected function getText(OutputTextDelta|ReasoningSummaryTextDelta $response): string
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

        if (! in_array($response->item->type, $this->applicableToolCallItemTypes(), true)) {
            return false;
        }

        return true;
    }

    /**
     * @return string[]
     */
    protected function applicableToolCallItemTypes(): array
    {
        return BasesResponsesTransporter::TOOL_CALL_ITEM_TYPES;
    }

    /**
     * Capture a Tool call from the response output item.
     */
    protected function captureToolCall(OutputItem $response): ToolCall
    {
        return new ToolCall(
            tool: $response->item->name ?? $response->item->type,
            id: $response->item->callId ?? $response->item->id,
            argumentsPayload: $response->item->arguments ?? null,
            itemId: $response->item->id ?? null,
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

            $context = $this->agent->orchestrator()->context;
            $context->observerInvoker()->agentOnStreamEvent($context, $this->agent, $response->event, $response);

            // We need a codepath before and after 0.19, so let's check for the new response class.
            $hasResponseClass = class_exists('\OpenAI\Responses\Responses\Streaming\Response');

            // openai-php/client < 0.19
            if ($hasResponseClass === false && $response->event === 'response.created' && ($response->response instanceof CreateResponse)) {
                $context->withPreviousResponseId($response->response->id);

                continue;
            }

            // openai-php/client >= 0.19
            if ($hasResponseClass === true && $response->event === 'response.created' && $response->response instanceof \OpenAI\Responses\Responses\Streaming\Response) {
                $context->withPreviousResponseId($response->response->response->id);

                continue;
            }

            if ($response->response instanceof OutputItem) {
                $currentItem = $response->response;
            }

            if ($response->event === 'response.output_item.done' && $response->response instanceof OutputItem) {
                match (get_class($response->response->item)) {
                    OutputMessage::class => $context->addMessage($this->messageFromOutputMessage($response->response->item), $this->agent),
                    OutputReasoning::class => $context->addMessage($this->messageFromOutputReasoning($response->response->item), $this->agent),
                    OutputFunctionToolCall::class => null, // ToolCall messages are added to the context by the ToolExecutor.
                    default => null,
                };
            }

            if ($response->response instanceof ReasoningSummaryTextDelta) {
                $context->observerInvoker()->agentOnReasoningInterval($context, $this->agent, new Payload($this->getText($response->response), 'assistant'));

                continue;
            }

            if ($response->response instanceof ReasoningSummaryTextDone) {
                $context->observerInvoker()->agentOnReasoning($context, $this->agent, new Message('assistant', $response->response->text));

                continue;
            }

            if ($response->response instanceof OutputTextDelta) {
                yield new Payload(
                    content: $this->getText($response->response),
                    role: $currentItem?->item->role ?? null,
                    itemId: $currentItem?->item->id ?? null,
                );

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

    protected function messageFromOutputMessage(OutputMessage $item): MessageInterface
    {
        $content = array_values($item->content)[0] ?? null;

        return new Message(
            role: $item->role,
            content: $content->text ?? null,
            itemId: $item->id,
        );
    }

    protected function messageFromOutputReasoning(OutputReasoning $item): MessageInterface
    {
        $summary = array_map(fn (OutputReasoningSummary $summaryItem) => $summaryItem->toArray(), $item->summary);

        return new ReasoningItem(
            id: $item->id,
            encryptedContent: $item->encryptedContent,
            summary: $summary,
        );
    }
}
