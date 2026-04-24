<?php

namespace Swis\Agents\Interfaces;

interface MessageInterface
{
    public function role(): ?string;

    public function content(): ?string;

    /**
     * @return array<string, mixed>
     */
    public function parameters(): array;

    public function owner(): ?AgentInterface;

    /**
     * @return array<string, ?int>
     */
    public function usage(): array;

    /**
     * Convert the message to a portable array suitable for persistence.
     *
     * Distinct from jsonSerialize(), which produces the LLM API payload shape.
     *
     * @return array<string, mixed>
     */
    public function toSerializedArray(): array;

    public function __toString(): string;
}
