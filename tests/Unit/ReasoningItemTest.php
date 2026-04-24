<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Message;
use Swis\Agents\Response\ReasoningItem;

class ReasoningItemTest extends TestCase
{
    public function testRoleIsReasoning(): void
    {
        $item = new ReasoningItem(id: 'rs_123');

        $this->assertSame(Message::ROLE_REASONING, $item->role());
    }

    public function testJsonSerializeEmitsResponsesInputShape(): void
    {
        $item = new ReasoningItem(
            id: 'rs_123',
            encryptedContent: 'ENCRYPTED_BLOB',
            summary: [['type' => 'summary_text', 'text' => 'thinking…']],
        );

        $this->assertSame(
            [
                'type' => 'reasoning',
                'id' => 'rs_123',
                'encrypted_content' => 'ENCRYPTED_BLOB',
                'summary' => [['type' => 'summary_text', 'text' => 'thinking…']],
            ],
            $item->jsonSerialize(),
        );
    }

    public function testJsonSerializeOmitsNullEncryptedContent(): void
    {
        $item = new ReasoningItem(id: 'rs_123');

        $json = $item->jsonSerialize();

        $this->assertArrayNotHasKey('encrypted_content', $json);
        $this->assertSame('reasoning', $json['type']);
        $this->assertSame('rs_123', $json['id']);
        $this->assertSame([], $json['summary']);
    }
}
