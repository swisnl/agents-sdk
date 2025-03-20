<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Tool;
use Swis\Agents\Tool\Required;
use Swis\Agents\Tool\ToolParameter;

class ToolTest extends TestCase
{
    /**
     * Test tool naming convention
     */
    public function testToolName(): void
    {
        $mockTool = new class extends Tool {
            public function __invoke(): ?string
            {
                return 'test result';
            }
        };
        
        // Class name should be "anonymousClass" and tool name should be "anonymous"
        $this->assertEquals('', $mockTool->name());
        
        $weatherTool = new class extends Tool {
            public function __invoke(): ?string
            {
                return 'weather result';
            }

            public function name(): string
            {
                return 'weather';
            }
        };
        
        $this->assertEquals('weather', $weatherTool->name());
    }
    
    /**
     * Test getting tool description
     */
    public function testToolDescription(): void
    {
        $mockTool = new class extends Tool {
            protected ?string $toolDescription = 'Test tool description';
            
            public function __invoke(): ?string
            {
                return 'test result';
            }
        };
        
        $this->assertEquals('Test tool description', $mockTool->description());
        
        $noDescriptionTool = new class extends Tool {
            public function __invoke(): ?string
            {
                return 'test result';
            }
        };
        
        $this->assertNull($noDescriptionTool->description());
    }
    
    /**
     * Test tool invocation
     */
    public function testToolInvocation(): void
    {
        $mockTool = new class extends Tool {
            public function __invoke(): ?string
            {
                return 'test result';
            }
        };
        
        $this->assertEquals('test result', $mockTool());
        
        $nullResultTool = new class extends Tool {
            public function __invoke(): ?string
            {
                return null;
            }
        };
        
        $this->assertNull($nullResultTool());
    }
    
    /**
     * Test tool parameter functionality
     */
    public function testToolParameter(): void
    {
        $tool = new class extends Tool {
            #[ToolParameter('Test parameter')]
            public string $param = '';
            
            public function __invoke(): ?string
            {
                return $this->param;
            }
            
            // Expose reflection API for testing
            public function getParamAttributes(): array
            {
                $reflection = new \ReflectionProperty($this, 'param');
                return $reflection->getAttributes(ToolParameter::class);
            }
        };
        
        // Test the parameter attribute
        $attributes = $tool->getParamAttributes();
        $this->assertCount(1, $attributes);
        
        $instance = $attributes[0]->newInstance();
        $this->assertEquals('Test parameter', $instance->description);
        
        // Test invocation with parameter
        $tool->param = 'test value';
        $this->assertEquals('test value', $tool());
    }
}