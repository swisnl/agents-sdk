<?php

namespace Swis\Agents\Transporters;

use OpenAI\Responses\Responses\CreateResponse;
use Swis\Agents\Agent;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Message;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Tool\ToolOutput;

/**
 * Transporter implementation that uses the Responses API in stateful mode.
 *
 * Relies on `previous_response_id` so OpenAI retains prior turns server-side.
 * Each request only carries new messages + tool outputs since the last response.
 * Useful when you want to minimise payload size or keep encrypted reasoning off
 * the client entirely. Note that server-side state expires (~30 days) and is
 * unavailable to ZDR-compliant organisations.
 */
class StatefulResponsesTransporter extends BasesResponsesTransporter
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
            'previous_response_id' => $context->previousResponseId(),
            'store' => true,
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
     * In stateful mode only system / developer messages, tool outputs for the
     * current run, and the last user / tool-output message are sent; the rest
     * of the conversation is reconstructed by OpenAI from `previous_response_id`.
     *
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

        $inputs = array_filter(
            $context->conversation(),
            fn (MessageInterface $message) => in_array($message->role(), $allowedRolesForInput, true),
        );
        $inputs = $this->appendToolOutputsToInputs($inputs, $context);

        return $this->appendLastMessageToInputs($inputs, $context);
    }

    /**
     * @param array<MessageInterface> $inputs
     * @return array<MessageInterface>
     */
    protected function appendToolOutputsToInputs(array $inputs, RunContext $context): array
    {
        foreach ($context->toolOutputsForCurrentRun() as $toolOutput) {
            $inputs[] = $toolOutput;
        }

        return $inputs;
    }

    /**
     * @param array<MessageInterface> $inputs
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
     * Capture the server-side response id for the next turn.
     */
    protected function captureResponseMetadata(Agent $agent, RunContext $context, CreateResponse $response): void
    {
        $context->withPreviousResponseId($response->id);
    }
}
