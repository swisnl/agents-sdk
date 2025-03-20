<?php

namespace Swis\Agents\Response;

use Swis\Agents\Message;

/**
 * Represents a request from the LLM to call a specific tool.
 * 
 * This class captures a tool invocation request with its name, ID, and arguments.
 * It extends the base Message class to represent tool calls in the conversation context.
 */
class ToolCall extends Message
{
    /**
     * Parsed arguments as an associative array.
     */
    public array $arguments = [];

    /**
     * Create a new tool call message.
     * 
     * @param string $tool             The name of the tool to call
     * @param string $id               Unique identifier for this tool call
     * @param string|null $argumentsPayload JSON string containing the arguments for the tool
     */
    public function __construct(public string $tool, public string $id, public ?string $argumentsPayload = null)
    {
        parent::__construct(Message::ROLE_ASSISTANT, null, ['tool_calls' => [
            ['id' => $id, 'type' => 'function', 'function' => ['name' => $tool, 'arguments' => $argumentsPayload]],
        ]]);

        // Parse the JSON arguments into an associative array for easier access
        $this->arguments = json_decode($argumentsPayload ?: '[]', true);
    }
}
