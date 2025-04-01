<?php

use Swis\Agents\Agent;
use Swis\Agents\Mcp\McpConnection;
use Swis\McpClient\Client;

class AirbnbAgent
{
    public function __invoke(): Agent
    {
        return new Agent(
            name: 'Airbnb Agent',
            description: 'This Agent can search for Airbnb listings.',
            instruction: 'Current date: ' . date('D j F Y - H:i'),
            mcpConnections: [
                new AirbnbMcpConnection(),
            ]
        );
    }

}

class AirbnbMcpConnection extends McpConnection
{
    public function __construct()
    {
        [$client, $process] = Client::withProcess(
            command: 'npx -y @openbnb/mcp-server-airbnb --ignore-robots-txt',
            // PHP has the habit of terminating child processes when waiting for CLI user input
            // This option will restart the child process if it terminates unexpectedly
            autoRestartAmount: 99
        );

        parent::__construct(
            client: $client,
            name: 'Airbnb MCP',
        );
    }
}
