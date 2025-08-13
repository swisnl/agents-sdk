<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Agent;
use Swis\Agents\Interfaces\Transporter;
use Swis\Agents\Transporters\ResponsesTransporter;

/**
 * @covers \Swis\Agents\Agent
 */
class TransporterTest extends TestCase
{
    /**
     * Assert that the default transporter is the Responses transporter.
     */
    public function testAgentUsesResponsesTransporterByDefault(): void
    {
        $agent = new Agent('Test');

        $this->assertInstanceOf(ResponsesTransporter::class, $agent->transporter());
    }

    /**
     * Ensure that a custom transporter can be injected.
     */
    public function testCustomTransporterCanBeInjected(): void
    {
        $mock = $this->createMock(Transporter::class);
        $agent = new Agent('Test');
        $agent->withTransporter($mock);

        $this->assertSame($mock, $agent->transporter());
    }
}
