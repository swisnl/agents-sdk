<?php

namespace Swis\Agents\Helpers;

use Swis\Agents\Interfaces\MessageInterface;
use Swis\Agents\Message;
use Swis\Agents\Orchestrator;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\ReasoningItem;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool\ToolOutput;

/**
 * Helper class to serialize and deserialize conversation state
 * for continuing conversations at a later time.
 *
 * Serialization itself lives on each message class (via toSerializedArray()
 * and fromSerializedArray()); this helper only orchestrates the loop and
 * maps the serialized `type` back to the right class.
 */
class ConversationSerializer
{
    /**
     * Map of serialized `type` values to the class that can rehydrate them.
     * Entries without a `type` (plain Message) fall through to Message.
     *
     * @var array<string, class-string<MessageInterface>>
     */
    protected const TYPE_MAP = [
        'reasoning' => ReasoningItem::class,
        'tool_call' => ToolCall::class,
        'tool_output' => ToolOutput::class,
    ];

    /**
     * Serialize the conversation from a RunContext into a portable format
     *
     * @param RunContext $context The context to serialize
     * @return array The serialized conversation data
     */
    public static function serialize(RunContext $context): array
    {
        $serializedMessages = [];
        foreach ($context->conversation() as $message) {
            $serializedMessages[] = $message->toSerializedArray();
        }

        return [
            'conversation' => $serializedMessages,
            'previous_response_id' => $context->previousResponseId(),
            'metadata' => [
                'serialized_at' => time(),
                'version' => '1.1',
            ],
        ];
    }

    public static function serializeFromOrchestrator(Orchestrator $orchestrator): array
    {
        return self::serialize($orchestrator->context);
    }

    /**
     * Deserialize conversation data into a new RunContext
     *
     * @param array $data The serialized conversation data
     * @param RunContext|null $into An existing context to add the conversation to
     * @return RunContext A new context with the conversation history
     */
    public static function deserialize(array $data, ?RunContext $into = null): RunContext
    {
        $context = $into ?? new RunContext();

        foreach ($data['conversation'] as $messageData) {
            $message = self::deserializeMessage($messageData);
            $context->addMessage($message);
        }

        return $context->withPreviousResponseId($data['previous_response_id'] ?? null);
    }

    /**
     * @param array<string, mixed> $messageData
     */
    protected static function deserializeMessage(array $messageData): MessageInterface
    {
        $class = self::TYPE_MAP[$messageData['type'] ?? ''] ?? Message::class;

        return $class::fromSerializedArray($messageData);
    }
}
