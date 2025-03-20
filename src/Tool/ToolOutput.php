<?php

namespace Swis\Agents\Tool;

use Swis\Agents\Message;

/**
 * Represents the output/result of a tool invocation.
 * 
 * This class extends the base Message class to represent tool outputs
 * in the conversation context. It links the output to the specific
 * tool call that generated it via the toolCallId.
 */
class ToolOutput extends Message
{
    /**
     * Create a new tool output message.
     * 
     * @param string $content     The content/result returned by the tool
     * @param string $toolCallId  The ID of the tool call that this is a response to
     */
    public function __construct(string $content, string $toolCallId)
    {
        parent::__construct(Message::ROLE_TOOL, $content, ['tool_call_id' => $toolCallId]);
    }
}