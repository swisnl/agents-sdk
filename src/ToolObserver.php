<?php

namespace Swis\Agents;

use Swis\Agents\Response\ToolCall;

/**
 * Base ToolObserver class for monitoring tool execution lifecycle.
 *
 * This abstract class defines the observer interface for tool-related events.
 * Implementations can extend this class to add behavior at various points
 * in the tool execution lifecycle, such as when tools are called or when
 * they complete successfully or with errors.
 */
abstract class ToolObserver
{
    /**
     * Called when a tool is about to be executed
     *
     * @param Tool $tool The tool being called
     * @param ToolCall $toolCall The tool call details including parameters
     * @return void
     */
    public function onToolCall(Tool $tool, ToolCall $toolCall): void
    {
    }

    /**
     * Called when a tool call completes successfully
     *
     * @param Tool $tool The tool that was called
     * @param ToolCall $toolCall The tool call details
     * @param string|null $output The output returned by the tool
     * @return void
     */
    public function onSuccess(Tool $tool, ToolCall $toolCall, ?string $output): void
    {
    }

    /**
     * Called when a tool call fails
     *
     * @param Tool $tool The tool that was called
     * @param ToolCall $toolCall The tool call details
     * @param string|null $output The error output or message
     * @return void
     */
    public function onFailure(Tool $tool, ToolCall $toolCall, ?string $output): void
    {
    }
}
