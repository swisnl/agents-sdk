<?php

namespace Swis\Agents\Tool;

use Attribute;

/**
 * Attribute to define dynamic possible values for a tool parameter.
 *
 * Similar to Enum, but instead of statically defining the values, it gets
 * the possible values from a method on the tool class. This allows for
 * dynamically generated enumerations based on runtime conditions.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class DerivedEnum
{
    /**
     * @param string $methodName Name of the method on the tool class that returns possible values
     */
    public function __construct(public string $methodName = '')
    {
    }
}
