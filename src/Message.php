<?php

namespace Swis\Agents;

use JsonSerializable;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Interfaces\OwnableMessageInterface;

/**
 * Represents a message in an agent conversation.
 *
 * This class implements the core message structure used throughout the agent system.
 * Messages have a role (system, user, assistant, tool), content, and optional
 * parameters. Messages can also track token usage and be associated with an owner agent.
 */
class Message implements OwnableMessageInterface, JsonSerializable
{
    /**
     * Standard role constants for different message types.
     */
    public const ROLE_SYSTEM = 'system';
    public const ROLE_DEVELOPER = 'developer';
    public const ROLE_ASSISTANT = 'assistant';
    public const ROLE_USER = 'user';
    public const ROLE_TOOL = 'tool';
    public const ROLE_REASONING = 'reasoning';

    /**
     * The agent that initiated this message, if applicable.
     */
    protected ?AgentInterface $owner;

    /**
     * Create a new message.
     *
     * @param string|null $role      The message role (system, user, assistant, tool)
     * @param string|null $content   The message content text
     * @param array<string, mixed> $parameters      Additional parameters specific to message type
     * @param int|null $inputTokens  Number of tokens in input processing, for usage tracking
     * @param int|null $outputTokens Number of tokens in output generation, for usage tracking
     * @param string|null $itemId    Optional Responses API item id (e.g. msg_*) used when
     *                               replaying the message as an input item in stateless mode.
     */
    public function __construct(
        protected ?string $role = null,
        protected ?string $content = null,
        protected array $parameters = [],
        protected ?int $inputTokens = null,
        protected ?int $outputTokens = null,
        protected ?string $itemId = null,
    ) {
    }

    /**
     * Get the Responses API item id (msg_*), if any.
     */
    public function itemId(): ?string
    {
        return $this->itemId;
    }

    /**
     * Get the role of the message.
     *
     * @return string|null The message role (system, developer, user, assistant, tool)
     */
    public function role(): ?string
    {
        return $this->role;
    }

    /**
     * Get the content of the message.
     *
     * @return string|null The message content
     */
    public function content(): ?string
    {
        return $this->content;
    }

    /**
     * Get the parameters of the message.
     *
     * @return array<string, mixed> The message parameters
     */
    public function parameters(): array
    {
        return $this->parameters;
    }

    /**
     * Associate this message with an owner agent.
     *
     * Sets the agent that initiated this message.
     *
     * @param AgentInterface|null $owner The agent that created this message
     */
    public function setOwner(?AgentInterface $owner): void
    {
        $this->owner = $owner;
    }

    /**
     * Get the owner of this message.
     *
     * @return AgentInterface|null The agent that initiated this message, if any
     */
    public function owner(): ?AgentInterface
    {
        return $this->owner;
    }

    /**
     * Get token usage statistics for this message.
     *
     * Returns the input and output token counts for tracking usage and costs.
     *
     * @return array<string, int|null> Token usage statistics
     */
    public function usage(): array
    {
        return [
            'input_tokens' => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
        ];
    }

    /**
     * Convert the message to a portable array suitable for persistence
     * (e.g. via ConversationSerializer). Subclasses override this to
     * capture their own shape.
     *
     * @return array<string, mixed>
     */
    public function toSerializedArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
            'parameters' => $this->parameters,
            'usage' => $this->usage(),
            'item_id' => $this->itemId,
        ];
    }

    /**
     * Rehydrate a message from its persisted array representation.
     *
     * @param array<string, mixed> $data
     */
    public static function fromSerializedArray(array $data): static
    {
        return new static(
            role: $data['role'] ?? null,
            content: $data['content'] ?? null,
            parameters: $data['parameters'] ?? [],
            inputTokens: $data['usage']['input_tokens'] ?? null,
            outputTokens: $data['usage']['output_tokens'] ?? null,
            itemId: $data['item_id'] ?? null,
        );
    }

    /**
     * Prepare the message for JSON serialization.
     *
     * Formats the message in a structure suitable for LLM API requests,
     * including any additional parameters specific to the message type.
     *
     * @return array<string, mixed> The message in JSON-serializable format
     */
    public function jsonSerialize(): array
    {
        // When an assistant message carries a Responses API item id (msg_*),
        // emit the Responses input-item shape so it can be replayed in stateless mode.
        if ($this->role === self::ROLE_ASSISTANT && $this->itemId !== null) {
            return array_filter([
                'type' => 'message',
                'id' => $this->itemId,
                'role' => self::ROLE_ASSISTANT,
                'content' => $this->content !== null
                    ? [['type' => 'output_text', 'text' => $this->content]]
                    : null,
                ...$this->parameters,
            ], fn ($value) => $value !== null);
        }

        return array_filter([
            'role' => $this->role,
            'content' => $this->content,
            ...$this->parameters,
        ], fn ($value) => $value !== null);
    }

    /**
     * Convert the message to a string.
     *
     * Returns the message content or an empty string if content is null.
     *
     * @return string The message content
     */
    public function __toString(): string
    {
        return $this->content ?? '';
    }
}
