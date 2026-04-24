<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Response\ToolCall;

class ToolCallTest extends TestCase
{
    public function testJsonSerializeWithoutItemIdOmitsIdKey(): void
    {
        $toolCall = new ToolCall(
            tool: 'get_weather',
            id: 'call_abc',
            argumentsPayload: '{"city":"Leiden"}',
        );

        $json = $toolCall->jsonSerialize();

        $this->assertSame('function_call', $json['type']);
        $this->assertSame('call_abc', $json['call_id']);
        $this->assertSame('get_weather', $json['name']);
        $this->assertSame('{"city":"Leiden"}', $json['arguments']);
        $this->assertArrayNotHasKey('id', $json);
    }

    public function testJsonSerializeWithItemIdIncludesId(): void
    {
        $toolCall = new ToolCall(
            tool: 'get_weather',
            id: 'call_abc',
            argumentsPayload: '{"city":"Leiden"}',
            itemId: 'fc_xyz',
        );

        $json = $toolCall->jsonSerialize();

        $this->assertSame('function_call', $json['type']);
        $this->assertSame('fc_xyz', $json['id']);
        $this->assertSame('call_abc', $json['call_id']);
    }
}
