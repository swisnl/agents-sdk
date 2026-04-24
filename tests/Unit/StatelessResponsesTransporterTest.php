<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Agent;
use Swis\Agents\Orchestrator;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\ReasoningItem;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool\ToolOutput;
use Swis\Agents\Transporters\ResponsesTransporter;

class StatelessResponsesTransporterTest extends TestCase
{
    public function testBuildRequestPayloadIsStatelessAndIncludesEncryptedReasoning(): void
    {
        [$agent, $context] = $this->agentWithContext();
        $context->addUserMessage('Hi');

        $transporter = new class () extends ResponsesTransporter {
            public function expose(Agent $agent, RunContext $context): array
            {
                return $this->buildRequestPayload($agent, $context);
            }
        };

        $payload = $transporter->expose($agent, $context);

        $this->assertFalse($payload['store']);
        $this->assertSame(['reasoning.encrypted_content'], $payload['include']);
        $this->assertArrayNotHasKey('previous_response_id', $payload);
    }

    public function testBuildInputsReplaysFullConversationInOrder(): void
    {
        [$agent, $context] = $this->agentWithContext();

        $context->addUserMessage('What\'s the weather in Leiden?');
        $context->addMessage(new ReasoningItem(
            id: 'rs_1',
            encryptedContent: 'BLOB',
            summary: [['type' => 'summary_text', 'text' => 'considering']],
        ));
        $context->addMessage(new ToolCall(
            tool: 'get_weather',
            id: 'call_1',
            argumentsPayload: '{"city":"Leiden"}',
            itemId: 'fc_1',
        ));
        $context->addMessage(new ToolOutput('Sunny', 'call_1'));

        $transporter = new class () extends ResponsesTransporter {
            public function expose(Agent $agent, RunContext $context): array
            {
                return $this->buildRequestPayload($agent, $context);
            }
        };

        $payload = $transporter->expose($agent, $context);
        $serialisedInputs = array_map(
            fn ($item) => $item->jsonSerialize(),
            $payload['input'],
        );

        // System instruction first (injected by prepareInstruction / withSystemMessage),
        // then user message, reasoning item, tool call, tool output -> in order.
        $types = array_map(
            fn ($input) => $input['type'] ?? $input['role'] ?? null,
            $serialisedInputs,
        );

        $this->assertSame(
            ['system', 'user', 'reasoning', 'function_call', 'function_call_output'],
            $types,
        );

        $reasoning = $serialisedInputs[2];
        $this->assertSame('rs_1', $reasoning['id']);
        $this->assertSame('BLOB', $reasoning['encrypted_content']);

        $functionCall = $serialisedInputs[3];
        $this->assertSame('fc_1', $functionCall['id']);
        $this->assertSame('call_1', $functionCall['call_id']);
        $this->assertSame('get_weather', $functionCall['name']);

        $functionOutput = $serialisedInputs[4];
        $this->assertSame('call_1', $functionOutput['call_id']);
        $this->assertSame('Sunny', $functionOutput['output']);
    }

    public function testUserExtraOptionsOverrideStatelessDefaults(): void
    {
        [$agent, $context] = $this->agentWithContext();
        $agent->modelSettings()->extraOptions = ['store' => true, 'include' => ['custom']];
        $context->addUserMessage('Hi');

        $transporter = new class () extends ResponsesTransporter {
            public function expose(Agent $agent, RunContext $context): array
            {
                return $this->buildRequestPayload($agent, $context);
            }
        };

        $payload = $transporter->expose($agent, $context);

        $this->assertTrue($payload['store']);
        $this->assertSame(['custom'], $payload['include']);
    }

    /**
     * @return array{0: Agent, 1: RunContext}
     */
    private function agentWithContext(): array
    {
        $agent = new Agent(name: 'Tester', instruction: 'You are helpful.');
        $orchestrator = new Orchestrator('Workflow');
        $orchestrator->disableTracing();
        $agent->setOrchestrator($orchestrator);

        return [$agent, $orchestrator->context];
    }
}
