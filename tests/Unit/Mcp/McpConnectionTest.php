<?php

namespace Swis\Agents\Tests\Unit\Mcp;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Exceptions\HandleToolException;
use Swis\Agents\Mcp\McpConnection;
use Swis\Agents\Mcp\McpTool;
use Swis\McpClient\Client;
use Swis\McpClient\Results\CallToolResult;
use Swis\McpClient\Results\JsonRpcError;
use Swis\McpClient\Results\ListToolsResult;
use Swis\McpClient\Schema\Content\TextContent;
use Swis\McpClient\Schema\Tool as McpToolDefinition;

class McpConnectionTest extends TestCase
{
    /**
     * Test basic Connection properties
     */
    public function testBasicProperties(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);

        // Create connection
        $connection = new McpConnection($client, 'test-connection');

        // Test basic properties
        $this->assertEquals('test-connection', $connection->getName());
        $this->assertSame($client, $connection->getClient());
    }

    /**
     * Test connection and disconnection
     */
    public function testConnectAndDisconnect(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);

        // Configure mock behavior
        $client->expects($this->once())
            ->method('isConnected')
            ->willReturn(false);

        $client->expects($this->once())
            ->method('connect');

        $client->expects($this->once())
            ->method('disconnect');

        // Create connection
        $connection = new McpConnection($client, 'test-connection');

        // Test connection
        $connection->connect();

        // Test disconnection
        $connection->disconnect();
    }

    /**
     * Test connecting when already connected
     */
    public function testConnectWhenAlreadyConnected(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);

        // Configure mock behavior
        $client->expects($this->once())
            ->method('isConnected')
            ->willReturn(true);

        $client->expects($this->never())
            ->method('connect');

        // Create connection
        $connection = new McpConnection($client, 'test-connection');

        // Test connection when already connected
        $connection->connect();
    }

    /**
     * Test listing tools
     */
    public function testListTools(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);
        $listToolsResult = $this->createMock(ListToolsResult::class);
        $toolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior
        $toolDefinition->method('getName')->willReturn('test-tool');
        $toolDefinition->method('getDescription')->willReturn('Test tool description');
        $toolDefinition->method('getSchema')->willReturn([]);

        $listToolsResult->method('getTools')
            ->willReturn([$toolDefinition]);

        $client->method('listTools')
            ->willReturn($listToolsResult);

        // Create connection
        $connection = new McpConnection($client, 'test-connection');

        // Test listing tools
        $tools = $connection->listTools();

        $this->assertCount(1, $tools);
        $this->assertArrayHasKey('test-tool', $tools);
        $this->assertInstanceOf(McpTool::class, $tools['test-tool']);
        $this->assertEquals('test-tool', $tools['test-tool']->name());
    }

    /**
     * Test listing tools with allowed tool names
     */
    public function testListToolsWithAllowedNames(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);
        $listToolsResult = $this->createMock(ListToolsResult::class);
        $toolDefinition1 = $this->createMock(McpToolDefinition::class);
        $toolDefinition2 = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior
        $toolDefinition1->method('getName')->willReturn('allowed-tool');
        $toolDefinition1->method('getDescription')->willReturn('Allowed tool');
        $toolDefinition1->method('getSchema')->willReturn([]);

        $toolDefinition2->method('getName')->willReturn('blocked-tool');
        $toolDefinition2->method('getDescription')->willReturn('Blocked tool');
        $toolDefinition2->method('getSchema')->willReturn([]);

        $listToolsResult->method('getTools')
            ->willReturn([$toolDefinition1, $toolDefinition2]);

        $client->method('listTools')
            ->willReturn($listToolsResult);

        // Create connection with allowed tools
        $connection = new McpConnection($client, 'test-connection');
        $connection->withTools('allowed-tool');

        // Test listing tools
        $tools = $connection->listTools();

        $this->assertCount(1, $tools);
        $this->assertArrayHasKey('allowed-tool', $tools);
        $this->assertEquals('allowed-tool', $tools['allowed-tool']->name());
        $this->assertArrayNotHasKey('blocked-tool', $tools);
    }

    /**
     * Test error when listing tools
     */
    public function testListToolsError(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);
        $jsonRpcError = $this->createMock(JsonRpcError::class);

        // Configure mock behavior
        $jsonRpcError->method('getMessage')
            ->willReturn('Test error message');

        $client->method('listTools')
            ->willReturn($jsonRpcError);

        // Create connection
        $connection = new McpConnection($client, 'test-connection');

        // Test listing tools with error
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Error fetching tools: Test error message');

        $connection->listTools();
    }

    /**
     * Test calling a tool
     */
    public function testCallTool(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);
        $callToolResult = $this->createMock(CallToolResult::class);
        $textContent = $this->createMock(TextContent::class);

        // Configure mock behavior
        $mcpToolDefinition->method('getName')->willReturn('test-tool');
        $mcpToolDefinition->method('getDescription')->willReturn('Test tool description');
        $mcpToolDefinition->method('getSchema')->willReturn([
            'properties' => [
                'input' => [
                    'type' => 'string',
                    'description' => 'Test input',
                ],
            ],
        ]);

        $textContent->method('getText')
            ->willReturn('Tool result text');

        $callToolResult->method('getContent')
            ->willReturn([$textContent]);

        $client->method('callTool')
            ->willReturn($callToolResult);

        // Create connection and tool
        $connection = new McpConnection($client, 'test-connection');
        $tool = new McpTool($connection, $mcpToolDefinition);
        $tool->input = 'test input';

        // Test calling the tool
        $result = $connection->callTool($tool);

        $this->assertEquals('Tool result text', $result);
    }

    /**
     * Test error when calling a tool
     */
    public function testCallToolError(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);
        $jsonRpcError = $this->createMock(JsonRpcError::class);

        // Configure mock behavior
        $mcpToolDefinition->method('getName')->willReturn('error-tool');
        $mcpToolDefinition->method('getDescription')->willReturn('Error tool');
        $mcpToolDefinition->method('getSchema')->willReturn([]);

        $jsonRpcError->method('getMessage')
            ->willReturn('Tool error message');

        $client->method('callTool')
            ->willReturn($jsonRpcError);

        // Create connection and tool
        $connection = new McpConnection($client, 'test-connection');
        $tool = new McpTool($connection, $mcpToolDefinition);

        // Test error when calling tool
        $this->expectException(HandleToolException::class);
        $this->expectExceptionMessage('Tool error message');

        $connection->callTool($tool);
    }

    /**
     * Test exception when calling a tool
     */
    public function testCallToolException(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);
        $mcpToolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior
        $mcpToolDefinition->method('getName')->willReturn('exception-tool');
        $mcpToolDefinition->method('getDescription')->willReturn('Exception tool');
        $mcpToolDefinition->method('getSchema')->willReturn([]);

        $client->method('callTool')
            ->willThrowException(new \Exception('Test exception'));

        // Create connection and tool
        $connection = new McpConnection($client, 'test-connection');
        $tool = new McpTool($connection, $mcpToolDefinition);

        // Test exception when calling tool
        $this->expectException(HandleToolException::class);
        $this->expectExceptionMessage('Failed to call MCP tool: Test exception');

        $connection->callTool($tool);
    }

    /**
     * Test tool caching behavior
     */
    public function testToolCaching(): void
    {
        // Create mock objects
        $client = $this->createMock(Client::class);
        $listToolsResult = $this->createMock(ListToolsResult::class);
        $toolDefinition = $this->createMock(McpToolDefinition::class);

        // Configure mock behavior
        $toolDefinition->method('getName')->willReturn('test-tool');
        $toolDefinition->method('getDescription')->willReturn('Test tool description');
        $toolDefinition->method('getSchema')->willReturn([]);

        $listToolsResult->method('getTools')
            ->willReturn([$toolDefinition]);

        // The client should only be called once for listTools
        $client->expects($this->once())
            ->method('listTools')
            ->willReturn($listToolsResult);

        // Create connection
        $connection = new McpConnection($client, 'test-connection');

        // Call listTools twice, second call should use cached tools
        $tools1 = $connection->listTools();
        $tools2 = $connection->listTools();

        $this->assertSame($tools1, $tools2);
    }

    /**
     * Test creating an MCP connection for a Streamable HTTP endpoint
     */
    public function testForStreamableHttp(): void
    {
        $endpoint = 'https://example.com/events';

        // Create a connection using the static method
        $connection = McpConnection::forStreamableHttp($endpoint);

        // Verify the connection was created correctly
        $this->assertInstanceOf(McpConnection::class, $connection);
        $this->assertEquals('MCP server', $connection->getName());
        $this->assertEquals('mcp_tools_' . md5($endpoint), $this->getPrivateProperty($connection, 'cacheKey'));
    }

    /**
     * Test creating an MCP connection for an SSE endpoint
     */
    public function testForSse(): void
    {
        $endpoint = 'https://example.com/events';

        // Create a connection using the static method
        $connection = McpConnection::forSse($endpoint);

        // Verify the connection was created correctly
        $this->assertInstanceOf(McpConnection::class, $connection);
        $this->assertEquals('MCP server', $connection->getName());
        $this->assertEquals('mcp_tools_' . md5($endpoint), $this->getPrivateProperty($connection, 'cacheKey'));
    }

    /**
     * Test creating an MCP connection for a process
     */
    public function testForProcess(): void
    {
        $processCommand = 'node server.js';
        $autoRestartAmount = 3;

        // Create a connection using the static method
        [$connection, $process] = McpConnection::forProcess($processCommand, $autoRestartAmount);

        // Verify the connection was created correctly
        $this->assertInstanceOf(McpConnection::class, $connection);
        $this->assertEquals('MCP server', $connection->getName());
        $this->assertEquals('mcp_tools_' . md5($processCommand), $this->getPrivateProperty($connection, 'cacheKey'));
    }

    /**
     * Helper method to access private properties
     */
    private function getPrivateProperty($object, $propertyName)
    {
        $reflection = new \ReflectionClass($object);
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
