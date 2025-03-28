<?php

namespace Swis\Agents\Helpers;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use Swis\Agents\DynamicTool;
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
     * Also includes any dynamic properties registered with the tool.
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

        $properties = [];
        $requiredProperties = [];

        // Process regular properties with attributes
        self::processReflectionProperties($tool, $properties, $requiredProperties);

        if ($tool instanceof DynamicTool) {
            // Process dynamic properties
            self::processDynamicProperties($tool, $properties, $requiredProperties);
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
     * Process reflection properties from the tool class
     *
     * @param Tool $tool The tool being processed
     * @param array<array<string, mixed>> &$properties Properties accumulator
     * @param array<string> &$requiredProperties Required properties accumulator
     */
    private static function processReflectionProperties(Tool $tool, array &$properties, array &$requiredProperties): void
    {
        $reflection = new ReflectionClass($tool);

        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes();
            /** @var \ReflectionAttribute<\Swis\Agents\Tool\ToolParameter>|null $toolParameter */
            $toolParameter = ArrayHelper::first($attributes, fn ($attribute) => $attribute->getName() === Tool\ToolParameter::class);

            // Skip properties that don't have the ToolParameter attribute
            if (! isset($toolParameter)) {
                continue;
            }

            $propertyName = $property->getName();

            // Get the property type if available
            $typeDefinition = null;
            $type = $property->getType();
            if ($type instanceof ReflectionNamedType) {
                $typeDefinition = $type->getName();
            }

            // Build the parameter definition with type and description
            $properties[$propertyName] = [
                'type' => self::mapPropertyType($typeDefinition),
                'description' => $toolParameter->newInstance()->description,
            ];

            // Add to required properties list if it has the Required attribute
            if (ArrayHelper::contains($attributes, fn ($attribute) => $attribute->getName() === Tool\Required::class)) {
                $requiredProperties[] = $property->getName();
            }

            self::processEnumAttributes($attributes, $properties, $propertyName, $tool);
        }
    }

    /**
     * Process enum attributes on a property
     *
     * @param list<ReflectionAttribute<object>> $attributes Property attributes
     * @param array<array<string, mixed>> &$properties Properties accumulator
     * @param string $propertyName The property name
     * @param Tool $tool The tool being processed
     */
    private static function processEnumAttributes(array $attributes, array &$properties, string $propertyName, Tool $tool): void
    {
        /** @var \ReflectionAttribute<\Swis\Agents\Tool\Enum> $enum */
        $enum = ArrayHelper::first($attributes, fn ($attribute) => $attribute->getName() === Tool\Enum::class);
        if ($enum) {
            $properties[$propertyName]['enum'] = $enum->newInstance()->values;
        }

        /** @var \ReflectionAttribute<\Swis\Agents\Tool\DerivedEnum> $derivedEnum */
        $derivedEnum = ArrayHelper::first($attributes, fn ($attribute) => $attribute->getName() === Tool\DerivedEnum::class);
        if ($derivedEnum) {
            $properties[$propertyName]['enum'] = $tool->{$derivedEnum->newInstance()->methodName}();
        }
    }

    /**
     * Process dynamic properties from the tool
     *
     * @param DynamicTool $tool The tool being processed
     * @param array<array<string, mixed>> &$properties Properties accumulator
     * @param array<string> &$requiredProperties Required properties accumulator
     */
    private static function processDynamicProperties(DynamicTool $tool, array &$properties, array &$requiredProperties): void
    {
        $dynamicProps = $tool->getDynamicProperties();
        foreach ($dynamicProps as $propName => $propDetails) {
            $properties[$propName] = [
                'type' => $propDetails['type'],
                'description' => $propDetails['description'],
            ];

            // Add enum values if they exist
            if (isset($propDetails['enum'])) {
                $properties[$propName]['enum'] = $propDetails['enum'];
            }

            // Add to required properties list if needed
            if ($propDetails['required']) {
                $requiredProperties[] = $propName;
            }

            // TODO: Handle array items type
            if ($properties[$propName]['type'] === 'array') {
                $properties[$propName]['items'] = ['type' => 'string'];
            }
        }
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
