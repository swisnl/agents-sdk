<?php

namespace Swis\Agents\Response;

class Payload
{
    public function __construct(
        public ?string $content,
        public ?string $role = null,
        public int $choice = 0,
        public ?int $inputTokens = null,
        public ?int $outputTokens = null,
    ) {
    }

    public function __toString(): string
    {
        return $this->content ?? '';
    }
}
