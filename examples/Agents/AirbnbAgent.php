<?php

use Swis\Agents\Agent;
use Swis\Agents\Mcp\McpConnection;

class AirbnbAgent
{
    public function __invoke(): Agent
    {
        return new Agent(
            name: 'Airbnb Agent',
            description: 'This Agent can search for Airbnb listings.',
            instruction: 'Current date: ' . date('D j F Y - H:i'),
            mcpConnections: [
                McpConnection::forProcess('npx -y @openbnb/mcp-server-airbnb --ignore-robots-txt', 99)
            ]
        );
    }
}
