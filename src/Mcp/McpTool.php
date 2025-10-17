<?php

namespace Swis\Agents\Mcp;

use Swis\Agents\DynamicTool;
use Swis\Agents\Exceptions\HandleToolException;
use Swis\McpClient\Schema\Tool as McpToolDefinition;

/**
 * Wrapper for MCP tools that makes them usable with Agents.
 *
 * This class adapts MCP tools to the Agent's Tool interface, allowing
 * seamless integration of MCP tools into the Agents SDK.
 */
class McpTool extends DynamicTool
{
    /**
     * Constructor
     *
     * @param McpConnection $connection The MCP connection
     * @param McpToolDefinition $mcpToolDefinition The MCP tool definition
     */
    public function __construct(
        protected McpConnection $connection,
        protected McpToolDefinition $mcpToolDefinition
    ) {
        // Initialize the tool with name and description from MCP
        parent::__construct(
            $mcpToolDefinition->getName(),
            $mcpToolDefinition->getDescription() ?? "MCP Tool: {$mcpToolDefinition->getName()}"
        );

        // Register dynamic properties based on the MCP tool schema
        $this->registerDynamicProperties();
    }

    /**
     * Get the MCP tool schema
     *
     * @return McpToolDefinition
     */
    public function mcpDefinition(): McpToolDefinition
    {
        return $this->mcpToolDefinition;
    }

    /**
     * Get the connection this tool belongs to
     *
     * @return McpConnection
     */
    public function connection(): McpConnection
    {
        return $this->connection;
    }

    /**
     * Execute the tool
     *
     * Calls the MCP tool with the provided arguments
     *
     * @return string The result of the tool call
     * @throws HandleToolException if the tool call fails
     */
    public function __invoke(): string
    {
        return $this->connection()->callTool($this);
    }

    /**
     * Register dynamic properties based on the MCP tool schema
     */
    protected function registerDynamicProperties(): void
    {
        $schema = $this->mcpToolDefinition->getSchema();

        // If no schema or properties, return
        if (empty($schema) || ! isset($schema['properties'])) {
            return;
        }
        assert(is_array($schema['properties']));

        // For each property in the schema, register a dynamic property
        foreach ($schema['properties'] as $propName => $propDetails) {
            assert(is_string($propName));
            assert(is_array($propDetails));

            // Get the property description
            $description = $propDetails['description'] ?? "Parameter: {$propName}";

            // Get the property type
            $type = $propDetails['type'] ?? 'string';

            // Check if the property is required
            $required = false;
            if (isset($schema['required']) && in_array($propName, $schema['required'])) {
                $required = true;
            }

            // Get enum values if they exist
            $enum = $propDetails['enum'] ?? null;

            // Get the items type when the type is array
            $itemsType = $propDetails['items']['type'] ?? null;

            // Cast object types to stdClass
            $objectClass = $itemsType === 'object' ? \stdClass::class : null;

            // Register the dynamic property
            $this->withDynamicProperty(
                name: $propName,
                type: $type,
                description: $description,
                required: $required,
                enum: $enum,
                itemsType: $itemsType,
                objectClass: $objectClass,
                customSchema: $propDetails,
            );
        }
    }
}
