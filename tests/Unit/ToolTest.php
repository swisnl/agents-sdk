<?php

namespace Swis\Agents\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Swis\Agents\Tool;
use Swis\Agents\Tool\ToolParameter;

class ToolTest extends TestCase
{
    /**
     * Test tool naming convention
     */
    public function testToolName(): void
    {
        $mockTool = new class () extends Tool {
            public function __invoke(): string
            {
                return 'test result';
            }
        };

        // Class name should be "anonymousClass" and tool name should be "anonymous"
        $this->assertEquals('', $mockTool->name());

        $weatherTool = new class () extends Tool {
            public function __invoke(): string
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
        $mockTool = new class () extends Tool {
            protected ?string $toolDescription = 'Test tool description';

            public function __invoke(): string
            {
                return 'test result';
            }
        };

        $this->assertEquals('Test tool description', $mockTool->description());

        $noDescriptionTool = new class () extends Tool {
            public function __invoke(): string
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
        $mockTool = new class () extends Tool {
            public function __invoke(): string
            {
                return 'test result';
            }
        };

        $this->assertEquals('test result', $mockTool());

        $nullResultTool = new class () extends Tool {
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
        $tool = new class () extends Tool {
            #[ToolParameter('Test parameter')]
            public string $param = '';

            public function __invoke(): string
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

    /**
     * Test tool with array parameter
     */
    public function testToolWithArrayParameter(): void
    {
        $tool = new class () extends Tool {
            #[ToolParameter('Array parameter', 'string')]
            public array $arrayParam = [];

            public function __invoke(): string
            {
                return implode(', ', $this->arrayParam);
            }

            // Expose reflection API for testing
            public function getParamAttributes(): array
            {
                $reflection = new \ReflectionProperty($this, 'arrayParam');

                return $reflection->getAttributes(ToolParameter::class);
            }
        };

        // Test the parameter attribute
        $attributes = $tool->getParamAttributes();
        $this->assertCount(1, $attributes);

        $instance = $attributes[0]->newInstance();
        $this->assertEquals('Array parameter', $instance->description);
        $this->assertEquals('string', $instance->itemsType);

        // Test invocation with array parameter
        $tool->arrayParam = ['value1', 'value2', 'value3'];
        $this->assertEquals('value1, value2, value3', $tool());
    }

    /**
     * Test tool with object parameter
     */
    public function testToolWithObjectParameter(): void
    {
        // Create a test object class for this test
        $testObjectClass = new class () {
            public string $property = 'test';

            public function __toString(): string
            {
                return $this->property;
            }
        };

        $className = get_class($testObjectClass);

        $tool = new class ($className) extends Tool {
            private string $objectClass;

            #[ToolParameter('Object parameter', null, \stdClass::class)]
            public object $objectParam;

            public function __construct(string $objectClass)
            {
                $this->objectClass = $objectClass;
                $this->objectParam = new $objectClass();
            }

            public function __invoke(): string
            {
                return (string)$this->objectParam;
            }

            // Expose reflection API for testing
            public function getParamAttributes(): array
            {
                $reflection = new \ReflectionProperty($this, 'objectParam');

                return $reflection->getAttributes(ToolParameter::class);
            }

            public function getObjectClassName(): string
            {
                return $this->objectClass;
            }
        };

        // Test the parameter attribute
        $attributes = $tool->getParamAttributes();
        $this->assertCount(1, $attributes);

        $instance = $attributes[0]->newInstance();
        $this->assertEquals('Object parameter', $instance->description);
        $this->assertEquals('object', $instance->itemsType);
        $this->assertEquals(\stdClass::class, $instance->objectClass);

        // Create a test object and set it as the parameter
        $objectClassName = $tool->getObjectClassName();
        $testObject = new $objectClassName();
        $testObject->property = 'custom value';
        $tool->objectParam = $testObject;

        // Test invocation with object parameter
        $this->assertEquals('custom value', $tool());
    }
}
