<?php

namespace Swis\Agents\Exceptions;

use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Tool\ToolOutput;

class UnparsableToolCallException extends ModelBehaviorException
{
    public string $toolCallId;

    public static function forToolCallId(string $toolCallId, string $message): self
    {
        $instance = new self($message);
        $instance->toolCallId = $toolCallId;

        return $instance;
    }

    public function toMessage(): MessageInterface
    {
        return new ToolOutput($this->toPayload(), $this->toolCallId);
    }
}
