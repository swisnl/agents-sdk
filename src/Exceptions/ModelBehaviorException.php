<?php

namespace Swis\Agents\Exceptions;

use Exception;
use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Message;

class ModelBehaviorException extends Exception
{
    protected string $role = Message::ROLE_USER;

    public function toPayload(): string
    {
        return json_encode(['error' => $this->getMessage()], JSON_THROW_ON_ERROR);
    }

    public function toMessage(): MessageInterface
    {
        return new Message($this->role, $this->toPayload());
    }
}
