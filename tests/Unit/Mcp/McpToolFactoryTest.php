<?php

namespace Swis\Agents\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Mcp\McpConnection;
use Swis\Agents\Mcp\McpTool;
use Swis\Agents\Mcp\McpToolFactory;
use Swis\McpClient\Schema\Tool as McpToolDefinition;

class McpToolFactoryTest extends TestCase
{
    /**
     * Test creating a single tool
     */
    public function testCreateTool(): void
    {
        // Create mock objects
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior
        $mcpToolDefinition->method('getName')->willReturn('test-tool');
        $mcpToolDefinition->method('getDescription')->willReturn('Test tool description');
        $mcpToolDefinition->method('getSchema')->willReturn([]);

        // Create tool using factory
        $tool = McpToolFactory::createTool($connection, $mcpToolDefinition);

        // Test the created tool
        $this->assertInstanceOf(McpTool::class, $tool);
        $this->assertEquals('test-tool', $tool->name());
        $this->assertEquals('Test tool description', $tool->description());
        $this->assertSame($connection, $tool->connection());
        $this->assertSame($mcpToolDefinition, $tool->mcpDefinition());
    }

    /**
     * Test creating multiple tools
     */
    public function testCreateTools(): void
    {
        // Create mock objects
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition1 = $this->createMock(McpToolDefinition::class);
        $mcpToolDefinition2 = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior
        $mcpToolDefinition1->method('getName')->willReturn('tool-1');
        $mcpToolDefinition1->method('getDescription')->willReturn('Tool 1 description');
        $mcpToolDefinition1->method('getSchema')->willReturn([]);

        $mcpToolDefinition2->method('getName')->willReturn('tool-2');
        $mcpToolDefinition2->method('getDescription')->willReturn('Tool 2 description');
        $mcpToolDefinition2->method('getSchema')->willReturn([]);

        // Create tools using factory
        $tools = McpToolFactory::createTools($connection, [$mcpToolDefinition1, $mcpToolDefinition2]);

        // Test the created tools
        $this->assertCount(2, $tools);
        $this->assertArrayHasKey('tool-1', $tools);
        $this->assertArrayHasKey('tool-2', $tools);

        $this->assertInstanceOf(McpTool::class, $tools['tool-1']);
        $this->assertEquals('tool-1', $tools['tool-1']->name());
        $this->assertEquals('Tool 1 description', $tools['tool-1']->description());

        $this->assertInstanceOf(McpTool::class, $tools['tool-2']);
        $this->assertEquals('tool-2', $tools['tool-2']->name());
        $this->assertEquals('Tool 2 description', $tools['tool-2']->description());
    }

    /**
     * Test creating tools with empty array
     */
    public function testCreateToolsWithEmptyArray(): void
    {
        // Create mock objects
        $connection = $this->createMock(McpConnection::class);

        // Create tools using factory with empty array
        $tools = McpToolFactory::createTools($connection, []);

        // Test result
        $this->assertIsArray($tools);
        $this->assertEmpty($tools);
    }

    /**
     * Test creating tools with alternate names
     */
    public function testCreateToolsWithAlternateNames(): void
    {
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        $mcpToolDefinition->method('getName')->willReturn('real-tool-name');
        $mcpToolDefinition->method('getDescription')->willReturn('Real tool description');
        $mcpToolDefinition->method('getSchema')->willReturn([]);

        $tools = McpToolFactory::createTools(
            $connection,
            [$mcpToolDefinition],
            ['real-tool-name' => 'alias-tool-name']
        );

        $this->assertCount(1, $tools);
        $this->assertArrayHasKey('alias-tool-name', $tools);
        $this->assertEquals('alias-tool-name', $tools['alias-tool-name']->name());
        $this->assertEquals('real-tool-name', $tools['alias-tool-name']->mcpName());
    }

    /**
     * Test creating tools with alternate descriptions
     */
    public function testCreateToolsWithAlternateDescriptions(): void
    {
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        $mcpToolDefinition->method('getName')->willReturn('real-tool-name');
        $mcpToolDefinition->method('getDescription')->willReturn('Original description');
        $mcpToolDefinition->method('getSchema')->willReturn([]);

        $tools = McpToolFactory::createTools(
            $connection,
            [$mcpToolDefinition],
            [],
            ['real-tool-name' => 'Agent-visible description']
        );

        $this->assertCount(1, $tools);
        $this->assertArrayHasKey('real-tool-name', $tools);
        $this->assertEquals('Agent-visible description', $tools['real-tool-name']->description());
    }
}
