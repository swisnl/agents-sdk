<?php

namespace Swis\Agents;

use Illuminate\Support\Str;
use Swis\Agents\Interfaces\AgentInterface;

/**
 * Handoff class enables transferring control between agents.
 * 
 * A Handoff wraps an agent as a Tool so that it can be invoked by another agent.
 * When called, it transfers control to the wrapped agent, allowing specialized agents
 * to handle specific types of requests in a multi-agent system.
 */
class Handoff extends Tool
{
    /**
     * Creates a new Handoff instance
     * 
     * @param AgentInterface $agent The agent to hand off to
     * @param string|null $toolName Custom name for the handoff tool (defaults to transfer_to_X)
     * @param string|null $toolDescription Custom description for the handoff tool
     */
    public function __construct(
        public AgentInterface $agent,
        protected ?string $toolName = null,
        protected ?string $toolDescription = null
    )
    {

    }

    /**
     * Gets the name of this handoff tool
     * 
     * @return string Either the custom name or a generated one based on the agent name
     */
    public function name(): string
    {
        return $this->toolName ?? $this->defaultToolName();
    }

    /**
     * Generates a default tool name following the format "transfer_to_agent_name"
     * 
     * @return string The generated tool name
     */
    protected function defaultToolName(): string
    {
        return sprintf('transfer_to_%s', Str::snake($this->agent->name()));
    }

    /**
     * Gets the description of this handoff tool
     * 
     * @return string Either the custom description or a generated one
     */
    public function description(): string
    {
        return $this->toolDescription ?? $this->defaultToolDescription();
    }

    /**
     * Generates a default tool description explaining the handoff
     * 
     * @return string The generated tool description
     */
    protected function defaultToolDescription(): string
    {
        return sprintf('Handoff to the %s Agent to handle the request.', trim(Str::replaceEnd('Agent', '', $this->agent->name())));
    }

    /**
     * Executes the handoff by invoking the wrapped agent
     * 
     * This transfers control to the target agent, which will use its own
     * tools and instructions to handle the conversation from this point forward.
     * 
     * @return string|null Always returns null as the handoff itself generates no content
     */
    public function __invoke(): ?string
    {
        $this->agent->invoke();

        return null;
    }

}
