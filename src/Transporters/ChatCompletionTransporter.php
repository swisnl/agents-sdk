<?php

namespace Swis\Agents\Transporters;

use OpenAI\Responses\Chat\CreateResponse;
use Swis\Agents\Agent;
use Swis\Agents\Helpers\ToolHelper;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Interfaces\Transporter;
use Swis\Agents\Message;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\Payload;
use Swis\Agents\Response\StreamedResponseWrapper;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;
use Swis\Agents\Tool\ToolOutput;

/**
 * Transporter implementation that communicates via the Chat Completions API.
 */
class ChatCompletionTransporter implements Transporter
{
    /**
     * Invoke the chat completions endpoint for the given agent and context.
     */
    public function invoke(Agent $agent, RunContext $context): void
    {
        $payload = $this->buildRequestPayload($agent, $context);

        if ($context->isStreamed()) {
            $this->invokeStreamed($agent, $context, $payload);
        } else {
            $this->invokeDirect($agent, $context, $payload);
        }
    }

    /**
     * Build the request payload for the Chat Completions API.
     *
     * @param Agent $agent The agent making the request.
     * @param RunContext $context The current run context.
     *
     * @return array<string,mixed>
     */
    protected function buildRequestPayload(Agent $agent, RunContext $context): array
    {
        $modelSettings = $agent->modelSettings();

        $payload = [
            'model' => $modelSettings->modelName,
            'temperature' => $modelSettings->temperature,
            'max_completion_tokens' => $modelSettings->maxTokens,
            'messages' => $this->buildInputs($agent, $context),
            ...$modelSettings->extraOptions ?? [],
        ];

        $tools = $this->buildToolsPayload($agent->executableTools());
        if (! empty($tools)) {
            $payload['tools'] = $tools;
        }

        if ($context->isStreamed()) {
            $payload['stream_options'] = ['include_usage' => true];
        }

        return $payload;
    }

    /**
     * Build the inputs for the request payload
     *
     * @param Agent $agent
     * @param RunContext $context
     * @return array<MessageInterface>
     */
    protected function buildInputs(Agent $agent, RunContext $context): array
    {
        $instruction = $agent->prepareInstruction();
        $context->withSystemMessage($instruction);

        $inputs = $context->conversation();
        foreach ($inputs as $key => $input) {
            $inputs[$key] = match (get_class($input)) {
                ToolCall::class => $this->buildToolInput($input),
                ToolOutput::class => $this->buildToolOutput($input),
                default => $input,
            };
        }

        return $inputs;
    }

    /**
     * Convert a ToolCall to a ChatCompletion compatible message
     *
     * @param ToolCall $toolCall
     * @return MessageInterface
     */
    protected function buildToolInput(ToolCall $toolCall): MessageInterface
    {
        return new Message(
            role: Message::ROLE_ASSISTANT,
            parameters: [
                'tool_calls' => [
                    [
                        'id' => $toolCall->id,
                        'type' => 'function',
                        'function' => ['name' => $toolCall->tool, 'arguments' => $toolCall->argumentsPayload],
                    ],
                ],
            ]
        );
    }

    /**
     * Convert a ToolOutput to a ChatCompletion compatible message
     *
     * @param ToolOutput $toolOutput
     * @return MessageInterface
     */
    protected function buildToolOutput(ToolOutput $toolOutput): MessageInterface
    {
        return new Message(
            role: Message::ROLE_TOOL,
            content: $toolOutput->content(),
            parameters: ['tool_call_id' => $toolOutput->parameters()['call_id']]
        );
    }

    /**
     * Transform tools into the payload structure expected by the API.
     *
     * @param array<Tool> $tools
     *
     * @return array<int, array<string, mixed>>
     */
    protected function buildToolsPayload(array $tools): array
    {
        return array_values(array_map(fn (Tool $tool) => $this->toolToPayload($tool), $tools));
    }

    /**
     * Convert a single tool into its payload representation.
     *
     * @param Tool $tool
     * @return array{type: string, function: array<string, mixed>}
     */
    protected function toolToPayload(Tool $tool): array
    {
        return [
            'type' => 'function',
            'function' => ToolHelper::toolToDefinition($tool),
        ];
    }

    /**
     * Handle a non-streamed chat completion request.
     *
     * @param Agent $agent
     * @param RunContext $context
     * @param array<string,mixed> $payload
     */
    protected function invokeDirect(Agent $agent, RunContext $context, array $payload): void
    {
        $response = $context->client()->chat()->create($payload);
        $this->handleResponse($agent, $context, $response);
    }

    /**
     * Handle a streamed chat completion request.
     *
     * @param Agent $agent
     * @param RunContext $context
     * @param array<string,mixed> $payload
     */
    protected function invokeStreamed(Agent $agent, RunContext $context, array $payload): void
    {
        $response = $context->client()->chat()->createStreamed($payload);
        $streamedResponse = new StreamedResponseWrapper($response, $agent);

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
        if ($this->isToolCall($response)) {
            $this->handleToolCallResponse($agent, $response);

            return;
        }

        $payload = new Payload(
            content: $response->choices[0]->message->content ?? null,
            role: $response->choices[0]->message->role ?? null,
            choice: $response->choices[0]->index ?? 0,
            inputTokens: $response->usage?->promptTokens,
            outputTokens: $response->usage?->completionTokens,
        );

        $context->observerInvoker()->agentOnResponseInterval($context, $agent, $payload);
        $context->addAgentMessage($payload, $agent);
        if ($context->lastMessage() !== null) {
            $context->observerInvoker()->agentOnResponse($context, $agent, $context->lastMessage());
        }
    }

    /**
     * Handle responses that contain tool calls.
     */
    protected function handleToolCallResponse(Agent $agent, CreateResponse $response): void
    {
        $toolCalls = $this->toolCallsFromResponse($response);
        $agent->executeTools($toolCalls);
    }

    /**
     * Determine if the response contains tool calls.
     */
    protected function isToolCall(CreateResponse $response): bool
    {
        return ! empty($response->choices[0]->message->toolCalls);
    }

    /**
     * Extract tool call information from the response.
     *
     * @return array<ToolCall>
     */
    protected function toolCallsFromResponse(CreateResponse $response): array
    {
        $toolCalls = [];
        foreach ($response->choices[0]->message->toolCalls as $toolCallData) {
            $toolCalls[] = new ToolCall(
                tool: $toolCallData->function->name,
                id: $toolCallData->id,
                argumentsPayload: $toolCallData->function->arguments,
            );
        }

        return $toolCalls;
    }
}
