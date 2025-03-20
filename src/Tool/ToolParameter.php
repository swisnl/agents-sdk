<?php

namespace Swis\Agents\Tool;

use Attribute;

/**
 * Attribute to define a property as a parameter for a tool.
 *
 * This attribute marks a class property as a parameter that can be passed to the tool
 * when it's invoked. It also provides a description of the parameter that will be
 * shown in the tool's definition.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class ToolParameter
{
    /**
     * @param string $description Human-readable description of the parameter
     */
    public function __construct(public string $description = '')
    {
    }
}
