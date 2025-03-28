<?php

namespace Swis\Agents;

/**
 * Base class for all agent tools.
 *
 * Tools are executable components that provide specific functionality to agents.
 * They can be invoked by agents to perform operations and return results.
 *
 * @phpstan-type ToolProperty array{type: string, description: string, required: bool, enum?: array<string>}
 */
abstract class DynamicTool extends Tool
{
    /**
     * Dynamic properties storage
     *
     * @var array<ToolProperty>
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
     * @return self
     */
    public function withDynamicProperty(
        string $name,
        string $type,
        string $description,
        bool $required = false,
        ?array $enum = null
    ): self {
        $property = [
            'type' => $type,
            'description' => $description,
            'required' => $required,
        ];

        if ($enum !== null) {
            $property['enum'] = $enum;
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
        // This allows setting values for dynamic properties
        $this->dynamicPropertyValues[$name] = $value;
    }

    /**
     * Magic method to get dynamic properties
     */
    public function __get(string $name): mixed
    {
        return $this->dynamicPropertyValues[$name] ?? null;
    }
}
