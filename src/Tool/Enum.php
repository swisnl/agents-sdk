<?php

namespace Swis\Agents\Tool;

use Attribute;

/**
 * Attribute to define possible values for a tool parameter.
 *
 * When this attribute is applied to a property with the ToolParameter attribute,
 * it restricts the parameter to a predefined set of values (enumeration).
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Enum
{
    /**
     * @param array<scalar> $possibleValues List of allowed values for this parameter
     */
    public function __construct(public array $possibleValues = [])
    {
    }
}
