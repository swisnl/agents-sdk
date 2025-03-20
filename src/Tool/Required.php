<?php

namespace Swis\Agents\Tool;

use Attribute;

/**
 * Attribute to mark a tool parameter as required.
 *
 * When this attribute is applied to a property that has the ToolParameter attribute,
 * it indicates that the parameter must be provided when the tool is invoked.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Required
{
}
