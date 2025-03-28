<?php

use Swis\Agents\Agent;
use Swis\Agents\Mcp\McpConnection;
use Swis\McpClient\Client;

/**
 * For this example you need to install the math-mcp server.
 * npm install git@github.com:EthanHenrickson/math-mcp.git
 */
class McpAgent
{
    public function __invoke(): Agent
    {
        return new Agent(
            name: 'Calculator Agent',
            description: 'This Agent can perform arithmetic operations.',
            mcpConnections: [
                new MathMcpConnection(),
            ]
        );
    }

}

class MathMcpConnection extends McpConnection
{
    public function __construct()
    {
        [$client, $process] = Client::withProcess(
            command: '/opt/homebrew/bin/node ' . realpath(__DIR__ . '/../../node_modules/math-mcp/build/index.js'),
            // PHP has the habit of terminating child processes when waiting for CLI user input
            // This option will restart the child process if it terminates unexpectedly
            autoRestartAmount: 99
        );

        parent::__construct(
            client: $client,
            name: 'Math MCP',
        );
    }
}
