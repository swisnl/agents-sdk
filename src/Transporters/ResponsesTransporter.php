<?php

namespace Swis\Agents\Transporters;

use OpenAI\Responses\Responses\CreateResponse;
use Swis\Agents\Agent;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Orchestrator\RunContext;

/**
 * Transporter implementation that uses the Responses API in stateless mode.
 *
 * Instead of relying on `previous_response_id`, this transporter replays the full
 * conversation (including encrypted reasoning items) on every turn. It asks the
 * API for `reasoning.encrypted_content` so prior reasoning can be carried between
 * turns without any server-side state.
 *
 * This is the default transporter because it works for ZDR-compliant organisations
 * and conversations that outlive the server-side retention window.
 */
class ResponsesTransporter extends BasesResponsesTransporter
{
    /**
     * Build the request payload for the Responses API.
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
            'store' => false,
            'include' => ['reasoning.encrypted_content'],
            'input' => $this->buildInputs($agent, $context),
            ...$modelSettings->extraOptions ?? [],
        ];

        $tools = $this->buildToolsPayload($agent->executableTools());
        if (! empty($tools)) {
            $payload['tools'] = $tools;
        }

        return $payload;
    }

    /**
     * Build the inputs for the request payload.
     *
     * Emits every item in the conversation in chronological order. Each item's
     * `jsonSerialize()` already returns a Responses API input-item shape:
     * - Plain messages (system/user/developer) → `{role, content}`
     * - Assistant messages with a msg_* itemId → `{type:'message', id, role:'assistant', content:[{type:'output_text', text}]}`
     * - ReasoningItem → `{type:'reasoning', id, encrypted_content, summary}`
     * - ToolCall → `{type:'function_call', id?, call_id, name, arguments}`
     * - ToolOutput → `{type:'function_call_output', call_id, output}`
     *
     * @return array<MessageInterface>
     */
    protected function buildInputs(Agent $agent, RunContext $context): array
    {
        $instruction = $agent->prepareInstruction();
        $context->withSystemMessage($instruction);

        return array_values($context->conversation());
    }

    /**
     * In stateless mode we do not store `previous_response_id` – every turn is
     * self-contained. We do, however, capture the reasoning items so the next
     * turn can replay them.
     */
    protected function captureResponseMetadata(Agent $agent, RunContext $context, CreateResponse $response): void
    {
        foreach ($this->reasoningItemsFromResponse($response) as $reasoningItem) {
            $context->addMessage($reasoningItem, $agent);
        }
    }
}
