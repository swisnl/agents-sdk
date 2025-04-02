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
     * @return McpTool The wrapped MCP tool
     */
    public static function createTool(McpConnection $connection, McpToolDefinition $mcpToolDefinition): McpTool
    {
        return new McpTool($connection, $mcpToolDefinition);
    }

    /**
     * Create McpTool instances for all McpTool definitions
     *
     * @param McpConnection $connection The MCP connection
     * @param array<McpToolDefinition> $mcpToolDefinitions The MCP tool definitions
     * @return array<string, McpTool> Array of MCP tools
     */
    public static function createTools(McpConnection $connection, array $mcpToolDefinitions): array
    {
        $tools = [];
        foreach ($mcpToolDefinitions as $mcpToolDefinition) {
            $tool = self::createTool($connection, $mcpToolDefinition);
            $tools[$tool->name()] = $tool;
        }

        return $tools;
    }
}
