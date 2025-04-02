<?php

namespace Swis\Agents\Tests\Integration;

use PHPUnit\Framework\MockObject\MockObject;
use Swis\Agents\Agent;
use Swis\Agents\Mcp\McpConnection;
use Swis\McpClient\Client;
use Swis\McpClient\Results\CallToolResult;
use Swis\McpClient\Results\ListToolsResult;
use Swis\McpClient\Schema\Content\TextContent;
use Swis\McpClient\Schema\Tool as McpToolDefinition;

class McpAgentTest extends BaseOrchestratorTestCase
{
    private Client|MockObject $mcpClient;
    private McpConnection $mcpConnection;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock MCP client
        $this->mcpClient = $this->createMock(Client::class);

        // Configure the client to return a list of tools
        $listToolsResult = $this->createToolsResultMock();
        $this->mcpClient->method('listTools')->willReturn($listToolsResult);

        // Configure the client to return a calculation result
        $callToolResult = $this->createMock(CallToolResult::class);
        $textContent = $this->createMock(TextContent::class);
        $textContent->method('getText')->willReturn('12');
        $callToolResult->method('getContent')->willReturn([$textContent]);
        $this->mcpClient->method('callTool')->willReturn($callToolResult);

        // Create an MCP connection with the mock client
        $this->mcpConnection = new McpConnection($this->mcpClient, 'test-mcp');
    }

    public function testMcpAgentInteraction()
    {
        // Create an agent with the MCP connection
        $agent = new Agent(
            name: 'Calculator Agent',
            description: 'This Agent can perform arithmetic operations.',
            mcpConnections: [$this->mcpConnection]
        );

        // Run the agent with a calculation prompt
        $response = $this->orchestrator
            ->withUserInstruction('What is 5 + 7?')
            ->run($agent);

        // Verify the response
        $this->assertEquals('The result of 5 + 7 is 12.', $response->content());

        // Verify the conversation flow
        $conversation = $this->orchestrator->context->conversation();

        // Verify the tool call
        $this->assertEquals('assistant', $conversation[2]->role());
        $this->assertArrayHasKey('expression', $conversation[2]->arguments);
        $this->assertEquals('5 + 7', $conversation[2]->arguments['expression']);

        // Verify the tool response
        $this->assertEquals('tool', $conversation[3]->role());
        $this->assertEquals('12', $conversation[3]->content());
    }

    /**
     * Create a mock ListToolsResult with a calculate tool
     */
    private function createToolsResultMock(): ListToolsResult|MockObject
    {
        $listToolsResult = $this->createMock(ListToolsResult::class);

        // Create a mock tool definition
        $toolDefinition = $this->createMock(McpToolDefinition::class);
        $toolDefinition->method('getName')->willReturn('calculate');
        $toolDefinition->method('getDescription')->willReturn('Calculate a mathematical expression');
        $toolDefinition->method('getSchema')->willReturn([
            'properties' => [
                'expression' => [
                    'type' => 'string',
                    'description' => 'Mathematical expression to evaluate',
                ],
            ],
            'required' => ['expression'],
        ]);

        // Configure the result to return the tool
        $listToolsResult->method('getTools')->willReturn([$toolDefinition]);

        return $listToolsResult;
    }
}
