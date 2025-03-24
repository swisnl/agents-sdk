<?php

namespace Swis\Agents\Helpers;

use Swis\Agents\Message;
use Swis\Agents\Orchestrator;
use Swis\Agents\Orchestrator\RunContext;

/**
 * Helper class to serialize and deserialize conversation state
 * for continuing conversations at a later time.
 */
class ConversationSerializer
{
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
            $serializedMessages[] = [
                'role' => $message->role(),
                'content' => $message->content(),
                'parameters' => $message->parameters(),
                'usage' => $message->usage(),
                // We don't serialize the owner as it would be complex to recreate
                // the exact agent instances. We don't need owner information to
                // continue the conversation at a later time.
            ];
        }

        $serialized = [
            'conversation' => $serializedMessages,
            'metadata' => [
                'serialized_at' => time(),
                'version' => '1.0',
            ],
        ];

        return $serialized;
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

        // Restore all messages
        foreach ($data['conversation'] as $messageData) {
            $message = new Message(
                role: $messageData['role'],
                content: $messageData['content'],
                parameters: $messageData['parameters'] ?? [],
                inputTokens: $messageData['usage']['input_tokens'] ?? null,
                outputTokens: $messageData['usage']['output_tokens'] ?? null
            );

            $context->addMessage($message);
        }

        return $context;
    }
}
