<?php

namespace Swis\Agents;

use Closure;
use Swis\Agents\Exceptions\McpConnectionFailed;
use Swis\Agents\Exceptions\ModelBehaviorException;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Interfaces\McpConnectionInterface;
use Swis\Agents\Interfaces\Transporter;
use Swis\Agents\Model\ModelSettings;
use Swis\Agents\Traits\HasToolCallingTrait;
use Swis\Agents\Transporters\ResponsesTransporter;

/**
 * Agent class is the core component of the Agents SDK
 *
 * An Agent encapsulates a specific role or capability with its own name, instructions,
 * tools, and settings. It can interact with an LLM to process inputs and generate appropriate
 * responses. Agents can also transfer control to other agents (handoffs) when specific
 * tasks are better handled by specialized agents.
 */
class Agent implements AgentInterface
{
    use HasToolCallingTrait;

    /**
     * The orchestrator that manages this agent and handles messaging
     */
    protected Orchestrator $orchestrator;

    /**
     * Custom instruction for handoffs, if null uses default
     */
    protected ?string $handoffInstruction = null;

    /**
     * Collection of tools available to this agent
     *
     * @var array<string, Tool>
     */
    protected array $tools = [];

    /**
     * Collection of other agents that this agent can hand off to
     *
     * @var array<string, Handoff|Agent>
     */
    protected array $handoffs = [];

    /**
     * Collection of MCP connections available to this agent
     *
     * @var array<McpConnectionInterface>
     */
    protected array $mcpConnections = [];

    /**
     * Handles communication with the OpenAI API.
     */
    protected Transporter $transporter;

    /**
     * Creates a new Agent instance
     *
     * @param string|Closure $name The name of the agent
     * @param string|Closure $description Short description of the agent's purpose
     * @param string|Closure $instruction System instructions for the LLM
     * @param ModelSettings|Closure $modelSettings LLM configuration settings
     * @param array<Tool> $tools Tools available to this agent
     * @param array<Handoff|Agent> $handoffs Other agents this agent can hand off to
     * @param array<McpConnectionInterface> $mcpConnections MCP connections available to this agent
     * @param Transporter|null $transporter Transporter to use (e.g. Chat Completions instead of Responses)
     */
    public function __construct(
        protected string|Closure $name,
        protected string|Closure $description = '',
        protected string|Closure $instruction = '',
        protected ModelSettings|Closure $modelSettings = new ModelSettings(),
        array $tools = [],
        array $handoffs = [],
        array $mcpConnections = [],
        ?Transporter $transporter = null
    ) {
        if (! empty($tools)) {
            $this->withTool(...$tools);
        }

        if (! empty($handoffs)) {
            $this->withHandoff(...$handoffs);
        }

        if (! empty($mcpConnections)) {
            $this->withMcpConnection(...$mcpConnections);
        }

        $this->transporter = $transporter ?? new ResponsesTransporter();
    }

    /**
     * Set a custom transporter.
     */
    public function withTransporter(Transporter $transporter): self
    {
        $this->transporter = $transporter;

        return $this;
    }

    /**
     * Get the transporter in use.
     */
    public function transporter(): Transporter
    {
        return $this->transporter;
    }

    /**
     * Sets the orchestrator for this agent
     *
     * The orchestrator manages the agent's context and messaging flow
     */
    public function setOrchestrator(Orchestrator $orchestrator): void
    {
        $this->orchestrator = $orchestrator;
    }

    /**
     * Gets the orchestrator for this agent
     */
    public function orchestrator(): Orchestrator
    {
        return $this->orchestrator;
    }

    /**
     * Sets the agent's name
     *
     * @param string|Closure $name The name or a closure that returns the name
     */
    public function withName(string|Closure $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Sets the agent's description
     *
     * @param string|Closure $description The description or a closure that returns it
     */
    public function withDescription(string|Closure $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Sets the agent's system instruction for the LLM
     *
     * @param string|Closure $instruction The instruction or a closure that returns it
     */
    public function withInstruction(string|Closure $instruction): self
    {
        $this->instruction = $instruction;

        return $this;
    }

    /**
     * Sets the model settings for the LLM
     *
     * @param ModelSettings|Closure $modelSettings The settings or a closure that returns them
     */
    public function withModelSettings(ModelSettings|Closure $modelSettings): self
    {
        $this->modelSettings = $modelSettings;

        return $this;
    }

    /**
     * Sets a custom handoff instruction
     *
     * @param string $handoffInstruction The custom instruction for handoffs
     */
    public function withHandoffInstruction(string $handoffInstruction): self
    {
        $this->handoffInstruction = $handoffInstruction;

        return $this;
    }

    /**
     * Gets the agent's name
     *
     * @return string The resolved name
     */
    public function name(): string
    {
        return $this->resolveClosure($this->name);
    }

    /**
     * Gets the agent's description
     *
     * @return string The resolved description
     */
    public function description(): string
    {
        return $this->resolveClosure($this->description);
    }

    /**
     * Gets the agent's system instruction
     *
     * @return string The resolved instruction
     */
    public function instruction(): string
    {
        return $this->resolveClosure($this->instruction);
    }

    /**
     * Gets the agent's model settings
     *
     * @return ModelSettings The resolved model settings
     */
    public function modelSettings(): ModelSettings
    {
        if ($this->modelSettings instanceof Closure) {
            return ($this->modelSettings)($this->orchestrator->context);
        }

        return $this->modelSettings;
    }

    /**
     * Gets the handoff instruction, falling back to default if not set
     *
     * @return string The handoff instruction
     */
    public function handoffInstruction(): string
    {
        return $this->handoffInstruction ?? $this->defaultHandoffInstruction();
    }

    /**
     * Adds one or more tools to this agent
     *
     * @param Tool ...$tools The tools to add
     */
    public function withTool(Tool ...$tools): self
    {
        $keyedTools = [];
        foreach ($tools as $tool) {
            $keyedTools[$tool->name()] = $tool;
        }

        $this->tools = array_merge($this->tools, $keyedTools);

        return $this;
    }

    /**
     * Adds one or more handoff agents
     *
     * @param Handoff|Agent ...$handoffs The agents to add as handoff targets
     */
    public function withHandoff(Handoff|Agent ...$handoffs): self
    {
        $keyedHandoffs = [];
        foreach ($handoffs as $handoff) {
            $keyedHandoffs[$handoff->name()] = $handoff;
        }

        $this->handoffs = array_merge($this->handoffs, $keyedHandoffs);

        return $this;
    }

    /**
     * Adds one or more MCP connections to this agent
     *
     * @param McpConnectionInterface ...$connections The MCP connections to add
     * @return self
     */
    public function withMcpConnection(McpConnectionInterface ...$connections): self
    {
        $this->mcpConnections = array_merge($this->mcpConnections, $connections);

        return $this;
    }

    /**
     * Gets all tools registered with this agent
     *
     * @return array<string, Tool> The registered tools
     */
    public function tools(): array
    {
        return $this->tools;
    }

    /**
     * Gets all handoffs registered with this agent
     *
     * @return array<string, Handoff|Agent> The registered handoffs
     */
    public function handoffs(): array
    {
        return $this->handoffs;
    }

    /**
     * Gets all MCP connections registered with this agent
     *
     * @return array<McpConnectionInterface> The registered MCP connections
     */
    public function mcpConnections(): array
    {
        return $this->mcpConnections;
    }

    /**
     * Invokes the agent, sending a request to the LLM and processing the response
     *
     * This method prepares the context, builds the request payload, and handles
     * the LLM response, including streaming if enabled.
     */
    public function invoke(): void
    {
        $this->prepareHandoffs();
        $this->prepareMcpConnections();
        $context = $this->orchestrator->context;

        $context->observerInvoker()->agentBeforeInvoke($context, $this);

        try {
            $this->transporter->invoke($this, $context);
        } catch (ModelBehaviorException $e) {
            // If the model behavior is incorrect, add the error message and
            // let the model retry
            $this->orchestrator->context->addMessage($e->toMessage());
            $this->invoke();
        }
    }

    /**
     * Prepares all MCP connections by ensuring they are connected
     */
    protected function prepareMcpConnections(): void
    {
        foreach ($this->mcpConnections as $connection) {
            try {
                $connection->connect();
            } catch (\Throwable $e) {
                throw new McpConnectionFailed('Connecting to MCP server failed', 0, $e);
            }
        }
    }

    /**
     * Prepares the instruction by combining handoff instruction if needed
     *
     * @return string The complete instruction for the LLM
     */
    public function prepareInstruction(): string
    {
        $instruction = $this->instruction();

        if (! empty($this->handoffs)) {
            $instruction = sprintf("%s\n\n%s", $this->handoffInstruction(), $instruction);
        }

        return $instruction;
    }

    /**
     * Prepares all handoff agents by setting their orchestrator
     */
    protected function prepareHandoffs(): void
    {
        foreach ($this->handoffs as $handoff) {
            $agent = $handoff instanceof Handoff ? $handoff->agent : $handoff;
            $agent->setOrchestrator($this->orchestrator);
        }
    }

    /**
     * Gets all executable tools, including tools for handoffs and MCP tools
     *
     * @return array<Tool> The complete list of tools
     */
    public function executableTools(): array
    {
        return array_merge($this->tools, $this->buildHandoffTools(), $this->buildMcpTools());
    }

    /**
     * Builds tools for each handoff agent
     *
     * @return array<string, Tool> The handoff tools
     */
    protected function buildHandoffTools(): array
    {
        $tools = [];

        foreach ($this->handoffs as $handoff) {
            if ($handoff instanceof Agent) {
                // Convert Agent to Handoff instance
                $handoff = new Handoff($handoff);
            }

            $tools[$handoff->name()] = $handoff;
        }

        return $tools;
    }

    /**
     * Get the supported tools for each MCP connection
     *
     * @return array<string, Tool> The MCP tools
     */
    protected function buildMcpTools(): array
    {
        $mcpTools = [];
        foreach ($this->mcpConnections as $connection) {
            $mcpTools = array_merge($mcpTools, $connection->listTools());
        }

        return $mcpTools;
    }

    /**
     * Provides the default handoff instruction
     *
     * This explains the multi-agent system to the LLM and how handoffs work.
     *
     * @return string The default handoff instruction
     */
    protected function defaultHandoffInstruction(): string
    {
        return <<<PROMPT
# System context

You are part of a multi-agent system called the Agents SDK, designed to make agent coordination and execution easy. Agents uses two primary abstraction: **Agents** and **Handoffs**.
An agent encompasses instructions and tools and can hand off a conversation to another agent when appropriate.
Handoffs are achieved by calling a handoff function, generally named `transfer_to_<agent_name>`.
Transfers between agents are handled seamlessly in the background; do not mention or draw attention to these transfers in your conversation with the user.
PROMPT;
    }

    /**
     * Helper method to resolve a closure or return a string value
     *
     * @param string|Closure $value The value or closure to resolve
     * @return string The resolved string value
     */
    private function resolveClosure(string|Closure $value): string
    {
        if ($value instanceof Closure) {
            return ($value)($this->orchestrator->context);
        }

        return $value;
    }
}
