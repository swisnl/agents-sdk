<?php

namespace Swis\Agents\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Exceptions\HandleToolException;
use Swis\Agents\Mcp\McpConnection;
use Swis\Agents\Mcp\McpTool;
use Swis\McpClient\Schema\Tool as McpToolDefinition;

class McpToolTest extends TestCase
{
    /**
     * Test basic McpTool creation and properties
     */
    public function testBasicProperties(): void
    {
        // Create mock objects
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior
        $mcpToolDefinition->method('getName')->willReturn('test-tool');
        $mcpToolDefinition->method('getDescription')->willReturn('Test tool description');
        $mcpToolDefinition->method('getSchema')->willReturn([]);

        // Create McpTool
        $tool = new McpTool($connection, $mcpToolDefinition);

        // Test basic properties
        $this->assertEquals('test-tool', $tool->name());
        $this->assertEquals('test-tool', $tool->mcpName());
        $this->assertEquals('Test tool description', $tool->description());
        $this->assertSame($connection, $tool->connection());
        $this->assertSame($mcpToolDefinition, $tool->mcpDefinition());
    }

    /**
     * Test alternate tool names keep original MCP name
     */
    public function testAlternateToolName(): void
    {
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        $mcpToolDefinition->method('getName')->willReturn('real-tool-name');
        $mcpToolDefinition->method('getDescription')->willReturn('Test tool description');
        $mcpToolDefinition->method('getSchema')->willReturn([]);

        $tool = new McpTool($connection, $mcpToolDefinition, 'alias-tool-name');

        $this->assertEquals('alias-tool-name', $tool->name());
        $this->assertEquals('real-tool-name', $tool->mcpName());
    }

    /**
     * Test alternate description is exposed to the agent
     */
    public function testAlternateToolDescription(): void
    {
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        $mcpToolDefinition->method('getName')->willReturn('real-tool-name');
        $mcpToolDefinition->method('getDescription')->willReturn('Original description');
        $mcpToolDefinition->method('getSchema')->willReturn([]);

        $tool = new McpTool($connection, $mcpToolDefinition, null, 'Agent-visible description');

        $this->assertEquals('Agent-visible description', $tool->description());
    }

    /**
     * Test registering dynamic properties from schema
     */
    public function testDynamicProperties(): void
    {
        // Create mock objects
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior with a schema containing properties
        $schema = [
            'properties' => [
                'query' => [
                    'type' => 'string',
                    'description' => 'Search query',
                ],
                'limit' => [
                    'type' => 'number',
                    'description' => 'Result limit',
                ],
            ],
            'required' => ['query'],
        ];

        $mcpToolDefinition->method('getName')->willReturn('search-tool');
        $mcpToolDefinition->method('getDescription')->willReturn('Search tool');
        $mcpToolDefinition->method('getSchema')->willReturn($schema);

        // Create McpTool
        $tool = new McpTool($connection, $mcpToolDefinition);

        // Test dynamic properties
        $properties = $tool->getDynamicProperties();
        $this->assertCount(2, $properties);

        // Check query property
        $this->assertArrayHasKey('query', $properties);
        $this->assertEquals('string', $properties['query']['type']);
        $this->assertEquals('Search query', $properties['query']['description']);
        $this->assertTrue($properties['query']['required']);

        // Check limit property
        $this->assertArrayHasKey('limit', $properties);
        $this->assertEquals('number', $properties['limit']['type']);
        $this->assertEquals('Result limit', $properties['limit']['description']);
        $this->assertFalse($properties['limit']['required']);

        // Test setting and getting values
        $tool->query = 'test query';
        $tool->limit = 10;

        $values = $tool->getDynamicPropertyValues();
        $this->assertEquals('test query', $values['query']);
        $this->assertEquals(10, $values['limit']);

        $this->assertEquals('test query', $tool->query);
        $this->assertEquals(10, $tool->limit);
    }

    /**
     * Test tool invocation
     */
    public function testInvocation(): void
    {
        // Create mock objects
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior
        $mcpToolDefinition->method('getName')->willReturn('echo-tool');
        $mcpToolDefinition->method('getDescription')->willReturn('Echo tool');
        $mcpToolDefinition->method('getSchema')->willReturn([
            'properties' => [
                'message' => [
                    'type' => 'string',
                    'description' => 'Message to echo',
                ],
            ],
        ]);

        $connection->method('callTool')->willReturn('Echo response');

        // Create McpTool
        $tool = new McpTool($connection, $mcpToolDefinition);
        $tool->message = 'Hello world';

        // Test invocation
        $this->assertEquals('Echo response', $tool());
    }

    /**
     * Test error handling during invocation
     */
    public function testInvocationError(): void
    {
        // Create mock objects
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior to throw exception
        $mcpToolDefinition->method('getName')->willReturn('error-tool');
        $mcpToolDefinition->method('getDescription')->willReturn('Error tool');
        $mcpToolDefinition->method('getSchema')->willReturn([]);

        $connection->method('callTool')->willThrowException(
            new HandleToolException('Test error')
        );

        // Create McpTool
        $tool = new McpTool($connection, $mcpToolDefinition);

        // Test invocation
        $this->expectException(HandleToolException::class);
        $this->expectExceptionMessage('Test error');
        $tool();
    }

    /**
     * Test handling enum values in properties
     */
    public function testEnumProperties(): void
    {
        // Create mock objects
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior with enum values
        $schema = [
            'properties' => [
                'color' => [
                    'type' => 'string',
                    'description' => 'Color selection',
                    'enum' => ['red', 'green', 'blue'],
                ],
            ],
        ];

        $mcpToolDefinition->method('getName')->willReturn('color-tool');
        $mcpToolDefinition->method('getDescription')->willReturn('Color tool');
        $mcpToolDefinition->method('getSchema')->willReturn($schema);

        // Create McpTool
        $tool = new McpTool($connection, $mcpToolDefinition);

        // Test enum property
        $properties = $tool->getDynamicProperties();
        $this->assertArrayHasKey('color', $properties);
        $this->assertArrayHasKey('enum', $properties['color']);
        $this->assertEquals(['red', 'green', 'blue'], $properties['color']['enum']);
    }

    /**
     * Test fallback description when none is provided
     */
    public function testDescriptionFallback(): void
    {
        // Create mock objects
        $connection = $this->createMock(McpConnection::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock to return null description
        $mcpToolDefinition->method('getName')->willReturn('no-desc-tool');
        $mcpToolDefinition->method('getDescription')->willReturn(null);
        $mcpToolDefinition->method('getSchema')->willReturn([]);

        // Create McpTool
        $tool = new McpTool($connection, $mcpToolDefinition);

        // Test that description has default value
        $this->assertEquals('MCP Tool: no-desc-tool', $tool->description());
    }
}
