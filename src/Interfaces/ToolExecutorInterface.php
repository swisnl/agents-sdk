<?php

namespace Swis\Agents\Interfaces;

use Swis\Agents\Agent;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;

interface ToolExecutorInterface
{
    /**
     * Execute multiple tools.
     *
     * @param array<array{0: Tool, 1: ToolCall}> $tools The tools to execute
     * @param Agent $agent The Agent calling the tools
     */
    public function executeTools(array $tools, Agent $agent): void;

    /**
     * Execute a single tool.
     *
     * Handles the lifecycle of a tool execution including:
     * - Notifying observers before execution
     * - Adding the tool call to the context
     * - Invoking the tool
     *
     * @param Tool $tool The tool instance to execute
     * @param ToolCall $toolCall The original tool call request
     * @param Agent $agent The Agent calling the tool
     */
    public function executeTool(Tool $tool, ToolCall $toolCall, Agent $agent): void;
}
