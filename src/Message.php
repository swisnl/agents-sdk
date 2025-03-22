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
    public const ROLE_SYSTEM = 'system';       // System instructions or context
    public const ROLE_ASSISTANT = 'assistant'; // AI-generated responses
    public const ROLE_USER = 'user';           // Human/user inputs
    public const ROLE_TOOL = 'tool';           // Tool execution results

    /**
     * The agent that initiated this message, if applicable.
     */
    protected ?AgentInterface $owner;

    /**
     * Create a new message.
     *
     * @param string $role           The message role (system, user, assistant, tool)
     * @param string|null $content   The message content text
     * @param array<string, mixed> $parameters      Additional parameters specific to message type
     * @param int|null $inputTokens  Number of tokens in input processing, for usage tracking
     * @param int|null $outputTokens Number of tokens in output generation, for usage tracking
     */
    public function __construct(
        protected string $role,
        protected ?string $content,
        protected array $parameters = [],
        protected ?int $inputTokens = null,
        protected ?int $outputTokens = null,
    ) {
    }

    /**
     * Get the role of the message.
     *
     * @return string The message role (system, user, assistant, tool)
     */
    public function role(): string
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
     * Prepare the message for JSON serialization.
     *
     * Formats the message in a structure suitable for LLM API requests,
     * including any additional parameters specific to the message type.
     *
     * @return array<string, mixed> The message in JSON-serializable format
     */
    public function jsonSerialize(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
            ...$this->parameters,
        ];
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
