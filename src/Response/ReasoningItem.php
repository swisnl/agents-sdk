<?php

namespace Swis\Agents\Response;

use JsonSerializable;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Interfaces\OwnableMessageInterface;
use Swis\Agents\Message;

/**
 * Represents a reasoning item returned by the Responses API.
 *
 * In stateless mode the SDK captures these so they can be replayed on the
 * next turn via the `input` array, letting the model reuse prior reasoning
 * without relying on server-side state (previous_response_id).
 */
class ReasoningItem implements OwnableMessageInterface, JsonSerializable
{
    protected ?AgentInterface $owner = null;

    /**
     * @param string $id The item id (rs_*) returned by the Responses API.
     * @param string|null $encryptedContent The opaque encrypted reasoning blob. Only
     *                                      present when the request included
     *                                      `include: ['reasoning.encrypted_content']`.
     * @param array<int, array<string, mixed>> $summary The public summary entries.
     */
    public function __construct(
        public string $id,
        public ?string $encryptedContent = null,
        public array $summary = [],
    ) {
    }

    public function role(): ?string
    {
        return Message::ROLE_REASONING;
    }

    public function content(): ?string
    {
        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function parameters(): array
    {
        return [
            'id' => $this->id,
            'encrypted_content' => $this->encryptedContent,
            'summary' => $this->summary,
        ];
    }

    public function setOwner(?AgentInterface $owner): void
    {
        $this->owner = $owner;
    }

    public function owner(): ?AgentInterface
    {
        return $this->owner;
    }

    /**
     * @return array<string, ?int>
     */
    public function usage(): array
    {
        return [
            'input_tokens' => null,
            'output_tokens' => null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toSerializedArray(): array
    {
        return [
            'type' => 'reasoning',
            'id' => $this->id,
            'encrypted_content' => $this->encryptedContent,
            'summary' => $this->summary,
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromSerializedArray(array $data): static
    {
        return new static(
            id: $data['id'] ?? '',
            encryptedContent: $data['encrypted_content'] ?? null,
            summary: $data['summary'] ?? [],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_filter([
            'type' => 'reasoning',
            'id' => $this->id,
            'encrypted_content' => $this->encryptedContent,
            'summary' => $this->summary,
        ], fn ($value) => $value !== null);
    }

    public function __toString(): string
    {
        return '';
    }
}
