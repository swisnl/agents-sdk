<?php

namespace Swis\Agents;

use Swis\Agents\Helpers\ToolHelper;

/**
 * Base class for all agent tools.
 *
 * Tools are executable components that provide specific functionality to agents.
 * They can be invoked by agents to perform operations and return results.
 *
 * @phpstan-type ToolProperty array{type: string, description: string, required: bool, enum?: array<string>, itemsType?: string, objectClass?: string}
 */
abstract class DynamicTool extends Tool
{
    /**
     * Dynamic properties storage
     *
     * @var array<string, ToolProperty>
     */
    protected array $dynamicProperties = [];

    /**
     * @var array<string, mixed>
     */
    protected array $dynamicPropertyValues = [];

    /**
     * Constructor
     *
     * @param string $name Name of the tool
     * @param string|null $description Optional description of the tool
     */
    public function __construct(protected string $name, protected ?string $description = null)
    {
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    /**
     * Register a dynamic property with attributes
     *
     * @param string $name Property name
     * @param string $type Property type
     * @param string $description Property description
     * @param bool $required Whether the property is required
     * @param array<string>|null $enum Optional enum values
     * @param string|null $itemsType For array properties, the type of items in the array
     * @param string|null $objectClass For object properties, the class to cast to/from
     * @return self
     */
    public function withDynamicProperty(
        string $name,
        string $type,
        string $description,
        bool $required = false,
        ?array $enum = null,
        ?string $itemsType = null,
        ?string $objectClass = null
    ): self {
        $property = [
            'type' => $type,
            'description' => $description,
            'required' => $required,
        ];

        if ($enum !== null) {
            $property['enum'] = $enum;
        }

        if ($itemsType !== null) {
            $property['itemsType'] = $itemsType;
        }

        if ($objectClass !== null) {
            $property['objectClass'] = $objectClass;
        }

        $this->dynamicProperties[$name] = $property;

        return $this;
    }

    /**
     * Get all registered dynamic properties
     *
     * @return array<string, ToolProperty>
     */
    public function getDynamicProperties(): array
    {
        return $this->dynamicProperties;
    }

    /**
     * Get all dynamic property values
     *
     * @return array<string, mixed>
     */
    public function getDynamicPropertyValues(): array
    {
        return $this->dynamicPropertyValues;
    }

    /**
     * Magic method to set dynamic properties
     */
    public function __set(string $name, mixed $value): void
    {
        $property = $this->dynamicProperties[$name] ?? null;

        // Default handling for undefined or simple types
        if ($property === null) {
            $this->dynamicPropertyValues[$name] = $value;

            return;
        }

        // Handle object type casting
        if ($property['type'] === 'object' && isset($property['objectClass'])) {
            $this->dynamicPropertyValues[$name] = $this->castToObject($value, $property['objectClass']);

            return;
        }

        // Handle array type with object items
        if ($property['type'] === 'array' && isset($property['itemsType']) &&
            $property['itemsType'] === 'object' && isset($property['objectClass'])) {
            $this->dynamicPropertyValues[$name] = $this->castArrayToObjects($value, $property['objectClass']);

            return;
        }

        // Default case for other property types
        $this->dynamicPropertyValues[$name] = $value;
    }

    /**
     * Magic method to get dynamic properties
     */
    public function __get(string $name): mixed
    {
        return $this->dynamicPropertyValues[$name] ?? null;
    }

    /**
     * Cast an associative array to an object of the specified class
     *
     * @param mixed $value The value to cast
     * @param string $className The class name to cast to
     * @return object The cast object
     */
    protected function castToObject(mixed $value, string $className): object
    {
        return ToolHelper::castToObject($value, $className);
    }

    /**
     * Cast an array of associative arrays to an array of objects
     *
     * @param mixed $array The array to cast
     * @param string $className The class name to cast array items to
     * @return array<object> The cast array of objects
     */
    protected function castArrayToObjects(mixed $array, string $className): array
    {
        return ToolHelper::castArrayToObjects($array, $className);
    }
}
