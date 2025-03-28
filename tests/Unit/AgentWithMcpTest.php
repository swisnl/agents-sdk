<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Agent;
use Swis\Agents\Mcp\McpConnection;
use Swis\Agents\Mcp\McpTool;
use Swis\McpClient\Client;
use Swis\McpClient\Results\ListToolsResult;
use Swis\McpClient\Schema\Tool as McpToolDefinition;

class AgentWithMcpTest extends TestCase
{
    /**
     * Test agent initialization with MCP connections
     */
    public function testAgentWithMcpConnections(): void
    {
        // Create mock MCP connections
        $connection1 = $this->createMock(McpConnection::class);
        $connection2 = $this->createMock(McpConnection::class);

        // Configure names for the connections
        $connection1->method('getName')->willReturn('connection-1');
        $connection2->method('getName')->willReturn('connection-2');

        // Create agent with MCP connections
        $agent = new Agent(
            name: 'Test Agent',
            mcpConnections: [$connection1, $connection2]
        );

        // Test agent properties
        $this->assertEquals('Test Agent', $agent->name());
        $this->assertCount(2, $agent->mcpConnections());
        $this->assertContains($connection1, $agent->mcpConnections());
        $this->assertContains($connection2, $agent->mcpConnections());
    }

    /**
     * Test agent initialization with MCP tools
     */
    public function testAgentWithMcpTools(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);
        $listToolsResult = $this->createMock(ListToolsResult::class);
        $toolDefinition1 = $this->createMock(McpToolDefinition::class);
        $toolDefinition2 = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior for tool definitions
        $toolDefinition1->method('getName')->willReturn('tool-1');
        $toolDefinition1->method('getDescription')->willReturn('Tool 1 description');
        $toolDefinition1->method('getSchema')->willReturn([]);

        $toolDefinition2->method('getName')->willReturn('tool-2');
        $toolDefinition2->method('getDescription')->willReturn('Tool 2 description');
        $toolDefinition2->method('getSchema')->willReturn([]);

        // Configure tools result
        $listToolsResult->method('getTools')
            ->willReturn([$toolDefinition1, $toolDefinition2]);

        // Configure client to return tools
        $client->method('listTools')
            ->willReturn($listToolsResult);

        // Create MCP connection with tools
        $connection = new McpConnection($client, 'test-connection');

        // Create agent with MCP connection
        $agent = new Agent(
            name: 'Test Agent',
            mcpConnections: [$connection]
        );

        // Get the tools from the agent
        $tools = $this->getTools($agent);

        // Test tools
        $this->assertCount(2, $tools);
        $this->assertContainsOnlyInstancesOf(McpTool::class, $tools);

        // Verify tool names
        $toolNames = array_map(function ($tool) {
            return $tool->name();
        }, $tools);

        $this->assertContains('tool-1', $toolNames);
        $this->assertContains('tool-2', $toolNames);
    }

    /**
     * Test agent with both regular tools and MCP tools
     */
    public function testAgentWithRegularAndMcpTools(): void
    {
        // Create a regular tool
        $regularTool = new class () extends \Swis\Agents\Tool {
            public function name(): string
            {
                return 'regular-tool';
            }

            public function __invoke(): string
            {
                return 'Regular tool result';
            }
        };

        // Create mock objects for MCP
        $client = $this->createMock(Client::class);
        $listToolsResult = $this->createMock(ListToolsResult::class);
        $toolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior
        $toolDefinition->method('getName')->willReturn('mcp-tool');
        $toolDefinition->method('getDescription')->willReturn('MCP tool description');
        $toolDefinition->method('getSchema')->willReturn([]);

        $listToolsResult->method('getTools')
            ->willReturn([$toolDefinition]);

        $client->method('listTools')
            ->willReturn($listToolsResult);

        // Create MCP connection
        $connection = new McpConnection($client, 'test-connection');

        // Create agent with both tool types
        $agent = new Agent(
            name: 'Test Agent',
            tools: [$regularTool],
            mcpConnections: [$connection]
        );

        // Get the tools from the agent
        $tools = $this->getTools($agent);

        // Test tools
        $this->assertCount(2, $tools);

        // Verify tool names
        $toolNames = array_map(function ($tool) {
            return $tool->name();
        }, $tools);

        $this->assertContains('regular-tool', $toolNames);
        $this->assertContains('mcp-tool', $toolNames);
    }

    private function getTools(Agent $agent): mixed
    {
        $reflection = new \ReflectionClass($agent);
        $method = $reflection->getMethod('executableTools');
        $method->setAccessible(true);
        $tools = $method->invoke($agent);

        return $tools;
    }
}
