<?php

namespace Swis\Agents\Interfaces;

use Swis\Agents\Agent;
use Swis\Agents\Orchestrator\RunContext;

/**
 * Contract for classes that handle communication with the OpenAI API.
 */
interface Transporter
{
    /**
     * Execute the API call for the given agent and context.
     *
     * @param Agent $agent The agent making the request.
     * @param RunContext $context The current run context.
     */
    public function invoke(Agent $agent, RunContext $context): void;
}
