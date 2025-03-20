<?php

namespace Swis\Agents;

use Illuminate\Support\Str;

/**
 * Base class for all agent tools.
 * 
 * Tools are executable components that provide specific functionality to agents.
 * They can be invoked by agents to perform operations and return results.
 */
abstract class Tool
{
    /**
     * Description of what the tool does, used in tool definitions for LLM.
     */
    protected ?string $toolDescription = null;

    /**
     * Execute the tool's functionality and return the result.
     * 
     * This is the main method that will be called when the tool is invoked.
     * Tool parameters are set as properties before invocation.
     * 
     * @return string|null The result of the tool execution or null if no result
     */
    abstract public function __invoke(): ?string;

    /**
     * Get the tool's name derived from the class name.
     * 
     * Automatically generates a snake_case name by taking the class name,
     * removing the 'Tool' suffix, and converting to snake_case.
     * 
     * @return string The tool name used in tool definitions
     */
    public function name(): string
    {
        return Str::of(class_basename(static::class))->beforeLast('Tool')->snake();
    }

    /**
     * Get the tool's description.
     * 
     * @return string|null The tool description or null if not set
     */
    public function description(): ?string
    {
        return $this->toolDescription;
    }
}
