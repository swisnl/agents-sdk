<?php

namespace Swis\Agents\Interfaces;

interface OwnableMessageInterface extends MessageInterface
{
    public function setOwner(AgentInterface $owner): void;
}
