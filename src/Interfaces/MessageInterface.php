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

    public function __toString(): string;
}
