<?php

namespace Swis\Agents\Interfaces;

use Swis\Agents\Model\ModelSettings;
use Swis\Agents\Orchestrator;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;

/**
 * Interface for defining an Agent in the Agents SDK
 *
 * This interface represents the core contract for Agents.
 */
interface AgentInterface
{
    /**
     * Sets the orchestrator that manages this agent
     *
     * The orchestrator handles message routing, context management, and
     * coordinates interactions between agents.
     *
     * @param Orchestrator $orchestrator The orchestrator instance to use
     */
    public function setOrchestrator(Orchestrator $orchestrator): void;

    /**
     * Gets the orchestrator that manages this agent
     *
     * @return Orchestrator $orchestrator The orchestrator instance for this Agent
     */
    public function orchestrator(): Orchestrator;

    /**
     * Gets the name of this agent
     *
     * @return string The unique name identifying this agent
     */
    public function name(): string;

    /**
     * Gets the description of this agent
     *
     * A short description of the agent's purpose and capabilities
     * for documentation and discovery purposes.
     *
     * @return string The agent description
     */
    public function description(): string;

    /**
     * Gets the system instruction for the LLM
     *
     * These instructions define the agent's behavior, capabilities,
     * and how it should respond to various inputs.
     *
     * @return string The complete system instruction
     */
    public function instruction(): string;

    /**
     * Gets the model settings for the LLM
     *
     * Includes configuration like model name, temperature, and token limits.
     *
     * @return ModelSettings The configured model settings
     */
    public function modelSettings(): ModelSettings;

    /**
     * Gets all tools registered with this agent
     *
     * Tools are functions that the agent can use to perform actions
     * or access external systems and data.
     *
     * @return array<Tool> The collection of available tools
     */
    public function tools(): array;

    /**
     * Gets all handoffs registered with this agent
     *
     * Handoffs allow an agent to transfer control to other specialized
     * agents when appropriate.
     *
     * @return array<Tool> The collection of available handoff targets
     */
    public function handoffs(): array;

    /**
     * Invokes the agent, sending a request to the LLM
     *
     * This method prepares the context, builds the request payload,
     * sends it to the LLM, and processes the response, including
     * handling tool calls and handoffs.
     */
    public function invoke(): void;

    /**
     * Let the agent executes tool calls
     *
     * @param array<ToolCall> $toolCalls The tool calls to execute
     */
    public function executeTools(array $toolCalls): void;
}
