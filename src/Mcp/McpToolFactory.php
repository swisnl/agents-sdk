<?php

namespace Swis\Agents\Mcp;

use Swis\McpClient\Schema\Tool as McpToolDefinition;

/**
 * Factory class for creating MCP tool wrappers.
 *
 * This class is responsible for converting raw MCP tool definitions
 * into usable McpTool instances that can be used by Agents.
 */
class McpToolFactory
{
    /**
     * Create a McpTool instance from a McpTool definition
     *
     * @param McpConnection $connection The MCP connection
     * @param McpToolDefinition $mcpToolDefinition The MCP tool definitions
     * @param string|null $toolName The tool name exposed to the agent
     * @param string|null $toolDescription The tool description exposed to the agent
     * @return McpTool The wrapped MCP tool
     */
    public static function createTool(
        McpConnection $connection,
        McpToolDefinition $mcpToolDefinition,
        ?string $toolName = null,
        ?string $toolDescription = null
    ): McpTool {
        return new McpTool($connection, $mcpToolDefinition, $toolName, $toolDescription);
    }

    /**
     * Create McpTool instances for all McpTool definitions
     *
     * @param McpConnection $connection The MCP connection
     * @param array<McpToolDefinition> $mcpToolDefinitions The MCP tool definitions
     * @param array<string, string> $alternateToolNames Map of MCP tool names => agent-visible tool names
     * @param array<string, string> $alternateToolDescriptions Map of MCP tool names => agent-visible tool descriptions
     * @return array<string, McpTool> Array of MCP tools
     */
    public static function createTools(
        McpConnection $connection,
        array $mcpToolDefinitions,
        array $alternateToolNames = [],
        array $alternateToolDescriptions = []
    ): array {
        $tools = [];
        foreach ($mcpToolDefinitions as $mcpToolDefinition) {
            $mcpToolName = $mcpToolDefinition->getName();
            $toolName = $alternateToolNames[$mcpToolName] ?? null;
            $toolDescription = $alternateToolDescriptions[$mcpToolName] ?? null;
            $tool = self::createTool($connection, $mcpToolDefinition, $toolName, $toolDescription);
            $tools[$tool->name()] = $tool;
        }

        return $tools;
    }
}
