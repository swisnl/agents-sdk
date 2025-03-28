<?php

namespace Swis\Agents;

use Closure;
use OpenAI\Contracts\ClientContract;
use Swis\Agents\Helpers\ConversationSerializer;
use Swis\Agents\Helpers\EnvHelper;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Interfaces\TracingProcessorInterface;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Orchestrator\StreamedAgentObserver;
use Swis\Agents\Tracing\OpenAIExporter;
use Swis\Agents\Tracing\Processor;

/**
 * Orchestrator class for managing agent workflows and interactions.
 *
 * The Orchestrator is responsible for:
 * - Managing and running agents
 * - Managing the run context and conversation flow
 * - Handling tracing/observability
 */
class Orchestrator
{
    /**
     * The default role for agent messages
     */
    protected string $agentRole = Message::ROLE_ASSISTANT;

    /**
     * Whether tracing is enabled for this orchestration
     */
    protected bool $tracingEnabled = true;

    /**
     * The processor responsible for tracing and exporting spans
     */
    protected ?TracingProcessorInterface $tracingProcessor = null;

    /**
     * The name of the workflow for tracing purposes
     *
     * @var string|null
     */
    protected ?string $name = null;

    /**
     * The context in which the orchestration is running
     *
     * @var RunContext
     */
    public RunContext $context;

    /**
     * Create a new Orchestrator instance
     *
     * @param string|null $name Optional workflow name for tracing
     * @param RunContext|null $context Optional run context (will be created if not provided)
     */
    public function __construct(
        ?string $name = null,
        ?RunContext $context = null
    ) {
        $this->name = $name;
        $this->context = $context ?? new RunContext();

        // Check environment for tracing configuration
        if (EnvHelper::get('AGENTS_SDK_DISABLE_TRACING', false)) {
            $this->disableTracing();
        }
    }

    /**
     * Deserialize conversation data into a new RunContext
     *
     * @param array $data The serialized conversation data
     * @return self
     */
    public function withContextFromData(array $data): self
    {
        ConversationSerializer::deserialize($data, $this->context);

        return $this;
    }

    /**
     * Set the OpenAI client to use for this orchestration
     *
     * @param ClientContract $client The OpenAI client
     * @return self
     */
    public function withClient(ClientContract $client): self
    {
        $this->context->withClient($client);

        return $this;
    }

    /**
     * Enable tracing for this orchestration
     *
     * @return self
     */
    public function enableTracing(): self
    {
        $this->tracingEnabled = true;

        return $this;
    }

    /**
     * Disable tracing for this orchestration
     *
     * @return self
     */
    public function disableTracing(): self
    {
        $this->tracingEnabled = false;

        return $this;
    }

    /**
     * Set a custom tracing processor
     *
     * @param TracingProcessorInterface $processor The tracing processor to use
     * @return self
     */
    public function withTracingProcessor(TracingProcessorInterface $processor): self
    {
        $this->tracingProcessor = $processor;

        return $this;
    }

    /**
     * Add a user instruction to the conversation
     *
     * @param string $instruction The user's instruction text
     * @return self
     */
    public function withUserInstruction(string $instruction): self
    {
        $this->context->addUserMessage($instruction);

        return $this;
    }

    /**
     * Add an agent observer to monitor agent events
     *
     * @param AgentObserver $observer The observer to add
     * @return self
     */
    public function withAgentObserver(AgentObserver $observer): self
    {
        $this->context->withAgentObserver($observer);

        return $this;
    }

    /**
     * Add a tool observer to monitor tool events
     *
     * @param ToolObserver $observer The observer to add
     * @return self
     */
    public function withToolObserver(ToolObserver $observer): self
    {
        $this->context->withToolObserver($observer);

        return $this;
    }

    /**
     * Set the role used to identify agent messages
     *
     * @param string $agentRole The role to use
     * @return self
     */
    public function withAgentRole(string $agentRole): self
    {
        $this->agentRole = $agentRole;

        return $this;
    }

    /**
     * Run an agent to completion and return its final message
     *
     * @param AgentInterface $agent The agent to run
     * @return MessageInterface|null The final message from the agent, or null if no message was produced
     */
    public function run(AgentInterface $agent): ?MessageInterface
    {
        $this->prepareTrace();
        $this->prepareAgent($agent);
        $agent->invoke();

        $lastMessage = $this->context->lastMessage();

        // Only return the message if it's from the agent (with the expected role)
        if ($lastMessage === null || $lastMessage->role() !== $this->agentRole) {
            return null;
        }

        $this->tracingProcessor?->stopCurrent();

        return $lastMessage;
    }

    /**
     * Run an agent with streaming responses
     *
     * @param AgentInterface $agent The agent to run
     * @param Closure $onResponse Callback that receives streaming token response
     * @return MessageInterface|null The final message from the agent, or null if no message was produced
     */
    public function runStreamed(AgentInterface $agent, Closure $onResponse): ?MessageInterface
    {
        $this->context->streamed();

        // Set up streaming observer with the provided callback
        $observer = (new StreamedAgentObserver())->withResponseCallback($onResponse);
        $this->context
            ->removeAgentObserver(StreamedAgentObserver::class)
            ->withAgentObserver($observer);

        return $this->run($agent);
    }

    /**
     * Prepare an agent for execution
     *
     * @param AgentInterface $agent The agent to prepare
     * @return void
     */
    protected function prepareAgent(AgentInterface $agent): void
    {
        $agent->setOrchestrator($this);
    }

    /**
     * Initialize tracing if it's enabled and not already started
     *
     * @return void
     */
    protected function prepareTrace(): void
    {
        if (! $this->tracingEnabled || $this->tracingProcessor?->isStarted()) {
            return;
        }

        // Use the default processor if none was provided
        $this->tracingProcessor = $this->tracingProcessor ?? new Processor(
            new OpenAIExporter(),
            $this->context
        );

        $this->tracingProcessor->start($this->name ?? 'Unnamed Workflow');
    }
}
