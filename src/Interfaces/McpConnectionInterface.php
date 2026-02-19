<?php

namespace Swis\Agents\Interfaces;

use Swis\Agents\Exceptions\HandleToolException;
use Swis\Agents\Tool;

/**
 * Represents a connection to an MCP server.
 *
 * This class manages the connection with an MCP server and provides
 * access to its tools and functionality.
 */
interface McpConnectionInterface
{
    /**
     * Connect to the MCP server
     *
     * This method should handle the handshake and authentication.
     * It will be called on every Agent invocation, so it should be
     * idempotent.
     */
    public function connect(): void;

    /**
     * Disconnect from the MCP server
     */
    public function disconnect(): void;

    /**
     * Only allow specific tools to be used from this MCP connection.
     *
     * @param string ...$toolNames List of tool names to allow
     * @return self
     */
    public function withTools(string ...$toolNames): self;

    /**
     * Define alternate names for MCP tools.
     *
     * The array should map original MCP tool names to the names exposed
     * to the agent.
     *
     * @param array<string, string> $alternateToolNames
     * @return self
     */
    public function withAlternateToolNames(array $alternateToolNames): self;

    /**
     * Define alternate descriptions for MCP tools.
     *
     * The array should map original MCP tool names to the descriptions
     * exposed to the agent.
     *
     * @param array<string, string> $alternateToolDescriptions
     * @return self
     */
    public function withAlternateToolDescriptions(array $alternateToolDescriptions): self;

    /**
     * List all tools available from this MCP connection
     *
     * @param bool $refresh Whether to refresh the cached tools
     * @return array<Tool> List of Tools
     */
    public function listTools(bool $refresh = false): array;

    /**
     * Execute the tool
     *
     * Calls the MCP tool with the provided arguments
     *
     * @return string The result of the tool call
     * @throws HandleToolException if the tool call fails
     */
    public function callTool(Tool $tool): string;
}
