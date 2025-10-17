<?php

namespace Swis\Agents\Helpers;

use ReflectionAttribute;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Swis\Agents\DynamicTool;
use Swis\Agents\Tool;
use Swis\Agents\Tool\ToolParameter;

/**
 * Helper class for working with Tool objects.
 *
 * Provides utility methods for converting Tool classes into definitions
 * that can be sent to LLM models, and helper methods for object casting.
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
            $toolParameterInstance = $toolParameter->newInstance();

            $propertyType = self::mapPropertyType($typeDefinition);
            $properties[$propertyName] = [
                'type' => $propertyType,
                'description' => $toolParameterInstance->description,
            ];

            // Process array or object types
            if ($propertyType === 'array' && $toolParameterInstance->itemsType !== null) {
                self::processArrayItemsType($properties, $propertyName, $toolParameterInstance);
            } elseif ($propertyType === 'object' && $toolParameterInstance->objectClass !== null) {
                self::processObjectProperties($properties, $propertyName, $toolParameterInstance->objectClass);
            }

            // Add to required properties list if it has the Required attribute
            if (ArrayHelper::contains($attributes, fn ($attribute) => $attribute->getName() === Tool\Required::class)) {
                $requiredProperties[] = $property->getName();
            }

            self::processEnumAttributes($attributes, $properties, $propertyName, $tool);
        }
    }

    /**
     * Process array items type
     *
     * @param array<array<string, mixed>> &$properties Properties accumulator
     * @param string $propertyName The property name
     * @param ToolParameter $toolParameter The tool parameter instance
     */
    private static function processArrayItemsType(array &$properties, string $propertyName, ToolParameter $toolParameter): void
    {
        $itemsType = $toolParameter->itemsType ?? 'string';

        // Handle primitive types
        if (in_array($itemsType, ['string', 'number', 'integer', 'boolean'])) {
            $properties[$propertyName]['items'] = ['type' => $itemsType];

            return;
        }

        // Handle object types for arrays
        if (class_exists($itemsType) || $toolParameter->objectClass !== null) {
            $properties[$propertyName]['items'] = ['type' => 'object'];
            // If objectClass is available, use that instead of itemsType
            $className = $toolParameter->objectClass ?? $itemsType;
            self::processObjectProperties($properties[$propertyName]['items'], 'properties', $className);
        }
    }

    /**
     * Process object properties from a class
     *
     * @param array<array<string, mixed>> &$properties Properties accumulator
     * @param string $propertyName The property name
     * @param string $className The class name to process
     */
    private static function processObjectProperties(array &$properties, string $propertyName, string $className): void
    {
        if (! class_exists($className)) {
            return;
        }

        $reflection = new ReflectionClass($className);
        $objectProperties = [];
        $requiredProperties = [];

        // Create an instance of the class to handle DerivedEnum methods
        $instance = null;

        try {
            $instance = $reflection->newInstance();
        } catch (\Throwable $e) {
            // Skip instance creation if it fails
        }

        foreach ($reflection->getProperties(ReflectionProperty::IS_PUBLIC) as $property) {
            $attributes = $property->getAttributes();
            /** @var \ReflectionAttribute<\Swis\Agents\Tool\ToolParameter>|null $toolParameter */
            $toolParameter = ArrayHelper::first($attributes, fn ($attribute) => $attribute->getName() === Tool\ToolParameter::class);

            // Skip properties that don't have the ToolParameter attribute
            if (! isset($toolParameter)) {
                continue;
            }

            $objPropertyName = $property->getName();

            // Get the property type if available
            $typeDefinition = null;
            $type = $property->getType();
            if ($type instanceof ReflectionNamedType) {
                $typeDefinition = $type->getName();
            }

            // Build the parameter definition with type and description
            $toolParameterInstance = $toolParameter->newInstance();

            $propertyType = self::mapPropertyType($typeDefinition);
            $objectProperties[$objPropertyName] = [
                'type' => $propertyType,
                'description' => $toolParameterInstance->description,
            ];

            // Process nested arrays or objects
            if ($propertyType === 'array' && $toolParameterInstance->itemsType !== null) {
                self::processArrayItemsType($objectProperties, $objPropertyName, $toolParameterInstance);
            } elseif ($propertyType === 'object' && $toolParameterInstance->objectClass !== null) {
                self::processObjectProperties($objectProperties, $objPropertyName, $toolParameterInstance->objectClass);
            }

            // Add to required properties list if it has the Required attribute
            if (ArrayHelper::contains($attributes, fn ($attribute) => $attribute->getName() === Tool\Required::class)) {
                $requiredProperties[] = $property->getName();
            }

            self::processEnumAttributes($attributes, $objectProperties, $objPropertyName, $instance);
        }

        if ($propertyName === 'properties') {
            $properties[$propertyName] = $objectProperties;
            if (! empty($requiredProperties)) {
                $properties['required'] = $requiredProperties;
            }
        } else {
            $properties[$propertyName]['properties'] = $objectProperties;
            if (! empty($requiredProperties)) {
                $properties[$propertyName]['required'] = $requiredProperties;
            }
        }
    }

    /**
     * Process enum attributes on a property
     *
     * @param list<ReflectionAttribute<object>> $attributes Property attributes
     * @param array<array<string, mixed>> &$properties Properties accumulator
     * @param string $propertyName The property name
     * @param Tool|object|null $instance The tool or object instance being processed
     */
    private static function processEnumAttributes(array $attributes, array &$properties, string $propertyName, ?object $instance): void
    {
        /** @var \ReflectionAttribute<\Swis\Agents\Tool\Enum> $enum */
        $enum = ArrayHelper::first($attributes, fn ($attribute) => $attribute->getName() === Tool\Enum::class);
        if ($enum) {
            $properties[$propertyName]['enum'] = $enum->newInstance()->possibleValues;
        }

        /** @var \ReflectionAttribute<\Swis\Agents\Tool\DerivedEnum> $derivedEnum */
        $derivedEnum = ArrayHelper::first($attributes, fn ($attribute) => $attribute->getName() === Tool\DerivedEnum::class);
        if ($derivedEnum && $instance !== null) {
            $methodName = $derivedEnum->newInstance()->methodName;
            if (method_exists($instance, $methodName)) {
                $properties[$propertyName]['enum'] = $instance->{$methodName}();
            }
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

            // Use the schema definition when available
            if (isset($propDetails['schema'])) {
                $properties[$propName] = $propDetails['schema'];

                continue;
            }

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

            // Handle array items and objects
            if ($properties[$propName]['type'] === 'array' && isset($propDetails['itemsType'])) {
                $properties[$propName]['items'] = ['type' => $propDetails['itemsType']];

                // If items are objects and a class is specified
                if ($propDetails['itemsType'] === 'object' && isset($propDetails['objectClass'])) {
                    self::processObjectProperties(
                        $properties[$propName]['items'],
                        'properties',
                        $propDetails['objectClass']
                    );
                }
            } elseif ($properties[$propName]['type'] === 'object' && isset($propDetails['objectClass'])) {
                self::processObjectProperties($properties, $propName, $propDetails['objectClass']);
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
            'array' => 'array',
            'stdClass', 'object' => 'object',
            default => (class_exists(ltrim($type ?? '', '?'))) ? 'object' : 'string',
        };
    }

    /**
     * Cast an associative array to an object of the specified class
     *
     * @param mixed $value The value to cast
     * @param string $className The class name to cast to
     * @return object The cast object
     */
    public static function castToObject(mixed $value, string $className): object
    {
        // If already the correct class, return as is
        if ($value instanceof $className) {
            return $value;
        }

        // If not an array, return empty object
        if (! is_array($value)) {
            return new $className();
        }

        $object = new $className();
        $reflection = new \ReflectionClass($className);

        foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
            $propertyName = $property->getName();

            if (! isset($value[$propertyName])) {
                continue;
            }

            $attributes = $property->getAttributes();
            $toolParameter = ArrayHelper::first($attributes, fn ($attribute) => $attribute->getName() === Tool\ToolParameter::class);

            if ($toolParameter === null) {
                $object->{$propertyName} = $value[$propertyName];

                continue;
            }
            /** @var ToolParameter $toolParameter */
            $toolParameter = $toolParameter->newInstance();
            $propertyType = $property->getType() instanceof ReflectionNamedType ? $property->getType()->getName() : 'string';

            // Handle nested objects
            if ($propertyType && class_exists($propertyType) && $toolParameter->objectClass) {
                $object->{$propertyName} = self::castToObject($value[$propertyName], $toolParameter->objectClass);

                continue;
            }

            // Handle arrays of objects
            if ($propertyType === 'array' && $toolParameter->itemsType && $toolParameter->objectClass) {
                $object->{$propertyName} = self::castArrayToObjects($value[$propertyName], $toolParameter->objectClass);

                continue;
            }

            // Default case: set value directly
            $object->{$propertyName} = $value[$propertyName];
        }

        return $object;
    }

    /**
     * Cast an array of associative arrays to an array of objects
     *
     * @param mixed $array The array to cast
     * @param string $className The class name to cast array items to
     * @return array<object> The cast array of objects
     */
    public static function castArrayToObjects(mixed $array, string $className): array
    {
        if (! is_array($array)) {
            return [];
        }

        $result = [];
        foreach ($array as $item) {
            $result[] = self::castToObject($item, $className);
        }

        return $result;
    }
}
