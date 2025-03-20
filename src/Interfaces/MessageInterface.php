<?php

namespace Swis\Agents\Interfaces;

interface MessageInterface
{
    public function role(): string;
    public function content(): ?string;
    public function owner(): ?AgentInterface;

    /**
     * @return array<string, int>
     */
    public function usage(): array;
    public function __toString(): string;
}