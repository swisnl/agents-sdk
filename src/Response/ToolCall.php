<?php

namespace Swis\Agents\Response;

use Swis\Agents\Exceptions\UnparsableToolCallException;
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
     *
     * @var array<mixed>
     */
    public array $arguments = [];

    /**
     * Create a new tool call message.
     *
     * @param string $tool The name of the tool to call
     * @param string $id Unique identifier for this tool call (call_id, e.g. call_*)
     * @param string|null $argumentsPayload JSON string containing the arguments for the tool
     * @param string|null $itemId Optional Responses API item id (fc_*) used when replaying
     *                            the tool call as an input item in stateless mode.
     * @throws UnparsableToolCallException
     */
    public function __construct(
        public string $tool,
        public string $id,
        public ?string $argumentsPayload = null,
        ?string $itemId = null,
    ) {
        parent::__construct(
            parameters: array_filter([
                'type' => 'function_call',
                'id' => $itemId,
                'call_id' => $id,
                'name' => $tool,
                'arguments' => $argumentsPayload,
            ], fn ($value) => $value !== null),
            itemId: $itemId,
        );

        $this->parseArgumentsPayload($argumentsPayload, $tool);
    }

    public function toSerializedArray(): array
    {
        return [
            'type' => 'tool_call',
            'tool' => $this->tool,
            'id' => $this->id,
            'arguments_payload' => $this->argumentsPayload,
            'item_id' => $this->itemId(),
            'usage' => $this->usage(),
        ];
    }

    public static function fromSerializedArray(array $data): static
    {
        return new static(
            tool: $data['tool'],
            id: $data['id'],
            argumentsPayload: $data['arguments_payload'] ?? null,
            itemId: $data['item_id'] ?? null,
        );
    }

    /**
     * @throws UnparsableToolCallException
     */
    protected function parseArgumentsPayload(?string $argumentsPayload, string $toolName): void
    {
        if ($argumentsPayload === null) {
            return;
        }

        // Parse the JSON arguments into an associative array for easier access
        $arguments = json_decode($argumentsPayload ?: '[]', true);

        if (! is_array($arguments)) {
            throw new UnparsableToolCallException(sprintf('The arguments for %s tool should be an object', $toolName));
        }

        $this->arguments = $arguments;
    }
}
