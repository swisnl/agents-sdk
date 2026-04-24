<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Agent;
use Swis\Agents\Interfaces\Transporter;
use Swis\Agents\Transporters\BasesResponsesTransporter;
use Swis\Agents\Transporters\ResponsesTransporter;
use Swis\Agents\Transporters\StatefulResponsesTransporter;

/**
 * @covers \Swis\Agents\Agent
 */
class TransporterTest extends TestCase
{
    /**
     * Assert that the default transporter is the (stateless) Responses transporter.
     */
    public function testAgentUsesResponsesTransporterByDefault(): void
    {
        $agent = new Agent('Test');

        $this->assertInstanceOf(ResponsesTransporter::class, $agent->transporter());
        $this->assertInstanceOf(BasesResponsesTransporter::class, $agent->transporter());
        $this->assertNotInstanceOf(StatefulResponsesTransporter::class, $agent->transporter());
    }

    /**
     * Ensure the stateful Responses transporter can be opted into.
     */
    public function testStatefulResponsesTransporterCanBeInjected(): void
    {
        $agent = new Agent('Test');
        $agent->withTransporter(new StatefulResponsesTransporter());

        $this->assertInstanceOf(StatefulResponsesTransporter::class, $agent->transporter());
        $this->assertInstanceOf(BasesResponsesTransporter::class, $agent->transporter());
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
