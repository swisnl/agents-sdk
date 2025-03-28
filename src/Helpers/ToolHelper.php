<?php

namespace Swis\Agents\Helpers;

use ReflectionClass;
use ReflectionNamedType;
use Swis\Agents\Tool;

/**
 * Helper class for working with Tool objects.
 *
 * Provides utility methods for converting Tool classes into definitions
 * that can be sent to LLM models.
 */
class ToolHelper
{
    /**
     * Convert a Tool object into an LLM-compatible function definition.
     *
     * Uses reflection to analyze the tool class and its properties to
     * generate a structured definition that includes parameters, types,
     * descriptions, and enumerations.
     *
     * @param Tool $tool The tool to convert to a definition
     * @return array The function definition in LLM-compatible format
     */
    public static function toolToDefinition(Tool $tool): array
    {
        $definition = [
            'name' => $tool->name(),
            'description' => $tool->description(),
        ];

        $reflection = new ReflectionClass($tool);

        $properties = [];
        $requiredProperties = [];
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes();

            /** @var \ReflectionAttribute<\Swis\Agents\Tool\ToolParameter>|null $toolParameter */
            $toolParameter = ArrayHelper::first($attributes, fn ($attribute) => $attribute->getName() === Tool\ToolParameter::class);

            // Skip properties that don't have the ToolParameter attribute
            if (! isset($toolParameter)) {
                continue;
            }

            // Get the property type if available
            $typeDefinition = null;
            $type = $property->getType();
            if ($type instanceof ReflectionNamedType) {
                $typeDefinition = $type->getName();
            }

            // Build the parameter definition with type and description
            $properties[$property->getName()] = [
                'type' => self::mapPropertyType($typeDefinition),
                'description' => $toolParameter->newInstance()->description,
            ];

            // Add to required properties list if it has the Required attribute
            if (ArrayHelper::contains($attributes, fn ($attribute) => $attribute->getName() === Tool\Required::class)) {
                $requiredProperties[] = $property->getName();
            }

            /** @var \ReflectionAttribute<\Swis\Agents\Tool\Enum> $enum */
            $enum = ArrayHelper::first($attributes, fn ($attribute) => $attribute->getName() === Tool\Enum::class);
            if ($enum) {
                $properties[$property->getName()]['enum'] = $enum->newInstance()->values;
            }

            /** @var \ReflectionAttribute<\Swis\Agents\Tool\DerivedEnum> $derivedEnum */
            $derivedEnum = ArrayHelper::first($attributes, fn ($attribute) => $attribute->getName() === Tool\DerivedEnum::class);
            if ($derivedEnum) {
                $properties[$property->getName()]['enum'] = $tool->{$derivedEnum->newInstance()->methodName}();
            }
        }

        // Only add parameters section if there are properties to include
        if (! empty($properties)) {
            $definition['parameters'] = [
                'type' => 'object',
                'properties' => $properties,
                'required' => $requiredProperties,
            ];
        }

        return $definition;
    }

    /**
     * Map PHP property types to JSON Schema types.
     *
     * Converts PHP's native type system to the type names expected in
     * JSON Schema / OpenAI function definitions.
     *
     * @param string|null $type PHP type name or null if not typed
     * @return string The corresponding JSON Schema type
     */
    protected static function mapPropertyType(?string $type): string
    {
        return match (ltrim($type ?? '', '?')) {
            'float' => 'number',
            'int' => 'integer',
            'bool' => 'boolean',
            default => 'string',
        };
    }
}
