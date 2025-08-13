<?php

namespace Swis\Agents\Transporters;

use OpenAI\Responses\Responses\CreateResponse;
use OpenAI\Responses\Responses\Output\OutputMessage;
use OpenAI\Responses\Responses\Output\OutputMessageContentOutputText;
use Swis\Agents\Agent;
use Swis\Agents\Helpers\ToolHelper;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Interfaces\Transporter;
use Swis\Agents\Message;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\Payload;
use Swis\Agents\Response\ResponsesStreamedResponseWrapper;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;
use Swis\Agents\Tool\ToolOutput;

/**
 * Transporter implementation that uses the Responses API.
 */
class ResponsesTransporter implements Transporter
{
    /**
     * Invoke the Responses endpoint for the given agent and context.
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
     * Build the request payload for the Responses API.
     *
     * @param Agent $agent
     * @param RunContext $context
     *
     * @return array<string,mixed>
     */
    protected function buildRequestPayload(Agent $agent, RunContext $context): array
    {
        $modelSettings = $agent->modelSettings();

        $payload = [
            'model' => $modelSettings->modelName,
            'temperature' => $modelSettings->temperature,
            'max_output_tokens' => $modelSettings->maxTokens,
            'previous_response_id' => $context->previousResponseId(),
            'input' => $this->buildInputs($agent, $context),
        ];

        $tools = $this->buildToolsPayload($agent->executableTools());
        if (! empty($tools)) {
            $payload['tools'] = $tools;
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

        $allowedRolesForInput = [
            Message::ROLE_SYSTEM,
            Message::ROLE_DEVELOPER,
        ];

        $inputs = array_filter($context->conversation(), fn (MessageInterface $message) => in_array($message->role(), $allowedRolesForInput));

        return $this->appendLastMessageToInputs($inputs, $context);
    }

    /**
     * Append the last message to the input, only when valid for input
     *
     * @param array<MessageInterface> $inputs
     * @param RunContext $context
     * @return array<MessageInterface>
     */
    protected function appendLastMessageToInputs(array $inputs, RunContext $context): array
    {
        $lastMessage = $context->lastMessage();

        if ($lastMessage === null) {
            return $inputs;
        }

        if ($lastMessage->role() === Message::ROLE_USER) {
            $inputs[] = $lastMessage;

            return $inputs;
        }

        if ($lastMessage instanceof ToolOutput) {
            $inputs[] = $lastMessage;

            return $inputs;
        }

        return $inputs;
    }

    /**
     * Transform tools into the payload structure expected by the API.
     *
     * @param array<Tool> $tools
     *
     * @return array<int, array<string,mixed>>
     */
    protected function buildToolsPayload(array $tools): array
    {
        return array_values(array_map(fn (Tool $tool) => $this->toolToPayload($tool), $tools));
    }

    /**
     * Convert a single tool into its payload representation.
     *
     * @param Tool $tool
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
     * @param Agent $agent
     * @param RunContext $context
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
     * @param Agent $agent
     * @param RunContext $context
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
        $context->withPreviousResponseId($response->id);

        if ($this->isToolCall($response)) {
            $this->handleToolCallResponse($agent, $response);

            return;
        }

        $content = '';
        $role = null;
        foreach ($response->output as $item) {
            if (! $item instanceof OutputMessage) {
                continue;
            }

            $contents = array_filter($item->content, fn ($content) => $content instanceof OutputMessageContentOutputText);
            $contents = array_map(fn ($content) => $content->text, $contents);

            $content .= implode($contents);
            $role = $item->role;
        }

        $payload = new Payload(
            content: $content,
            role: $role,
            choice: 0,
            inputTokens: $response->usage?->inputTokens,
            outputTokens: $response->usage?->outputTokens,
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
        $applicableToolCallItemTypes = $this->applicableToolCallItemTypes();

        foreach ($response->output as $item) {
            if (in_array($item->type, $applicableToolCallItemTypes)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the list of item types that should be handled as Tool call
     *
     * @return string[]
     */
    public function applicableToolCallItemTypes(): array
    {
        return [
            'file_search_call',
            'function_call',
            'web_search_call',
            'computer_call',
            'code_interpreter_call',
        ];
    }

    /**
     * Extract tool calls from the response.
     *
     * @return array<ToolCall>
     */
    protected function toolCallsFromResponse(CreateResponse $response): array
    {
        $applicableToolCallItemTypes = $this->applicableToolCallItemTypes();

        $toolCalls = [];
        foreach ($response->output as $item) {
            if (! in_array($item->type, $applicableToolCallItemTypes)) {
                continue;
            }

            $toolCalls[] = new ToolCall(
                tool: $item->name ?? $item->type,
                id: $item->callId ?? $item->id,
                argumentsPayload: $item->arguments ?? null,
            );
        }

        return $toolCalls;
    }
}
