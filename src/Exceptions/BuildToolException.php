<?php

namespace Swis\Agents\Exceptions;

use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool\ToolOutput;

class BuildToolException extends ModelBehaviorException
{
    public ToolCall $toolCall;

    public static function forToolCall(ToolCall $toolCall, string $message): self
    {
        $instance = new self($message);
        $instance->toolCall = $toolCall;

        return $instance;
    }

    public function toMessage(): MessageInterface
    {
        return new ToolOutput($this->toPayload(), $this->toolCall->id);
    }

}
