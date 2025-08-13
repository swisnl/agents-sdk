<?php

namespace Swis\Agents\Orchestrator;

use OpenAI;
use OpenAI\Contracts\ClientContract;
use Swis\Agents\AgentObserver;
use Swis\Agents\Helpers\EnvHelper;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Interfaces\OwnableMessageInterface;
use Swis\Agents\Message;
use Swis\Agents\Response\Payload;
use Swis\Agents\ToolObserver;

/**
 * RunContext class for managing the execution context of an agent workflow.
 *
 * This class is responsible for:
 * - Maintaining the conversation history
 * - Managing OpenAI client configuration
 * - Registering and managing observers
 * - Handling message creation and management
 */
class RunContext
{
    /**
     * The OpenAI client instance
     */
    protected ClientContract $client;

    /**
     * The conversation history of messages
     *
     * @var array<MessageInterface>
     */
    protected array $conversation = [];

    /**
     * Registered agent observers
     *
     * @var array<AgentObserver>
     */
    protected array $agentObservers = [];

    /**
     * Registered tool observers
     *
     * @var array<ToolObserver>
     */
    protected array $toolObservers = [];

    /**
     * Whether responses should be streamed
     */
    protected bool $isStreamed = false;

    /**
     * The observer invoker instance
     */
    protected ObserverInvoker $observerInvoker;

    /**
     * The previous response id of a Responses request
     */
    protected ?string $previousResponseId = null;

    /**
     * Create a new RunContext instance
     */
    public function __construct()
    {
        // Initialize the OpenAI client with environment variables
        $this->client = OpenAI::client(
            apiKey: EnvHelper::get('OPENAI_API_KEY', ''),
            organization: EnvHelper::get('AGENTS_SDK_DEFAULT_ORGANIZATION'),
            project: EnvHelper::get('AGENTS_SDK_DEFAULT_PROJECT')
        );

        $this->observerInvoker = new ObserverInvoker();
    }

    /**
     * Enable or disable streaming for responses
     *
     * @param bool $streamed Whether to stream responses
     * @return self
     */
    public function streamed(bool $streamed = true): self
    {
        $this->isStreamed = $streamed;

        return $this;
    }

    /**
     * Set the OpenAI client to use
     *
     * @param ClientContract $client The OpenAI client
     * @return self
     */
    public function withClient(ClientContract $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Set the previous response id to continue the conversation
     *
     * @param string|null $previousResponseId The previous response id of a Responses request
     * @return $this
     */
    public function withPreviousResponseId(?string $previousResponseId): self
    {
        $this->previousResponseId = $previousResponseId;

        return $this;
    }

    /**
     * Add a developer message to the conversation
     *
     * @param string $message The message content
     * @return self
     */
    public function addDeveloperMessage(string $message): self
    {
        $this->addMessage(new Message(Message::ROLE_DEVELOPER, $message));

        return $this;
    }

    /**
     * Add a user message to the conversation
     *
     * @param string $message The message content
     * @return self
     */
    public function addUserMessage(string $message): self
    {
        $this->addMessage(new Message(Message::ROLE_USER, $message));

        return $this;
    }

    /**
     * Add an agent message to the conversation
     *
     * @param string|Payload $message The message content or payload
     * @param AgentInterface $owner The agent that generated this message
     * @return self
     */
    public function addAgentMessage(string|Payload $message, AgentInterface $owner): self
    {
        $messageInstance = new Message(
            role: Message::ROLE_ASSISTANT,
            content: (string) $message,
        );

        if ($message instanceof Payload) {
            $messageInstance = $this->messageFromPayload($message, Message::ROLE_ASSISTANT);
        }

        $this->addMessage($messageInstance, $owner);

        return $this;
    }

    /**
     * Convert a payload to a message instance
     *
     * @param Payload $payload The payload to convert
     * @param string $role The role for the message
     * @return Message The created message
     */
    protected function messageFromPayload(Payload $payload, string $role): Message
    {
        return new Message(
            role: $role,
            content: $payload->content,
            inputTokens: $payload->inputTokens,
            outputTokens: $payload->outputTokens,
        );
    }

    /**
     * Set or replace the system message in the conversation
     *
     * @param string $message The system message content
     * @return self
     */
    public function withSystemMessage(string $message): self
    {
        // Remove any existing system messages
        $this->conversation = array_values(array_filter(
            $this->conversation,
            fn (MessageInterface $message) => $message->role() !== Message::ROLE_SYSTEM
        ));

        // Add the new system message at the beginning
        array_unshift($this->conversation, new Message(Message::ROLE_SYSTEM, $message));

        return $this;
    }

    /**
     * Add one or more agent observers
     *
     * @param AgentObserver ...$observer The observers to add
     * @return self
     */
    public function withAgentObserver(AgentObserver ...$observer): self
    {
        $this->agentObservers = array_merge($this->agentObservers, $observer);

        return $this;
    }

    /**
     * Remove agent observers by class name
     *
     * @param string $observerClass The class of observers to remove
     * @return self
     */
    public function removeAgentObserver(string $observerClass): self
    {
        $this->agentObservers = array_filter(
            $this->agentObservers,
            fn (AgentObserver $observer) => ! is_a($observer, $observerClass)
        );

        return $this;
    }

    /**
     * Add one or more tool observers
     *
     * @param ToolObserver ...$observer The observers to add
     * @return self
     */
    public function withToolObserver(ToolObserver ...$observer): self
    {
        $this->toolObservers = array_merge($this->toolObservers, $observer);

        return $this;
    }

    /**
     * Remove tool observers by class name
     *
     * @param string $observerClass The class of observers to remove
     * @return self
     */
    public function removeToolObserver(string $observerClass): self
    {
        $this->toolObservers = array_filter(
            $this->toolObservers,
            fn (ToolObserver $observer) => ! is_a($observer, $observerClass)
        );

        return $this;
    }

    /**
     * Set a custom observer invoker
     *
     * @param ObserverInvoker $observerInvoker The observer invoker to use
     * @return self
     */
    public function withObserverInvoker(ObserverInvoker $observerInvoker): self
    {
        $this->observerInvoker = $observerInvoker;

        return $this;
    }

    /**
     * Add a message to the conversation
     *
     * @param MessageInterface $message The message to add
     * @param AgentInterface|null $owner The owner agent (if applicable)
     * @return void
     */
    public function addMessage(MessageInterface $message, ?AgentInterface $owner = null): void
    {
        if (isset($owner) && $message instanceof OwnableMessageInterface) {
            $message->setOwner($owner);
        }

        $this->conversation[] = $message;
    }

    /**
     * Get the OpenAI client
     *
     * @return ClientContract The client
     */
    public function client(): ClientContract
    {
        return $this->client;
    }

    /**
     * Get the previous response id to continue a Responses conversation
     *
     * @return string|null The previous response id
     */
    public function previousResponseId(): ?string
    {
        return $this->previousResponseId;
    }

    /**
     * Check if streaming is enabled
     *
     * @return bool Whether streaming is enabled
     */
    public function isStreamed(): bool
    {
        return $this->isStreamed;
    }

    /**
     * Get the full conversation history
     *
     * @return array<MessageInterface> The conversation
     */
    public function conversation(): array
    {
        return $this->conversation;
    }

    /**
     * Get the last message in the conversation
     *
     * @return MessageInterface|null The last message, or null if none
     */
    public function lastMessage(): ?MessageInterface
    {
        return end($this->conversation) ?: null;
    }

    /**
     * Get all registered agent observers
     *
     * @return array<AgentObserver> The agent observers
     */
    public function agentObservers(): array
    {
        return $this->agentObservers;
    }

    /**
     * Get all registered tool observers
     *
     * @return array<ToolObserver> The tool observers
     */
    public function toolObservers(): array
    {
        return $this->toolObservers;
    }

    /**
     * Get the observer invoker
     *
     * @return ObserverInvoker The observer invoker
     */
    public function observerInvoker(): ObserverInvoker
    {
        return $this->observerInvoker;
    }
}
