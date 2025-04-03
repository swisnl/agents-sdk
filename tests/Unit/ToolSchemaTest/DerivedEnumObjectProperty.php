<?php

namespace Swis\Agents\Tests\Unit\ToolSchemaTest;

use Swis\Agents\Tool\DerivedEnum;
use Swis\Agents\Tool\Required;
use Swis\Agents\Tool\ToolParameter;

/**
 * Test class for DerivedEnum with object properties
 * Used for testing DerivedEnum functionality in nested objects
 */
class DerivedEnumObjectProperty
{
    #[ToolParameter('The field to filter by.'), Required]
    #[DerivedEnum('getAvailableFields')]
    public string $field;

    #[ToolParameter('The filter value.'), Required]
    public string $value;

    // Method that will be called for the DerivedEnum in the nested object
    public function getAvailableFields(): array
    {
        return ['title', 'author', 'category', 'year'];
    }
}
