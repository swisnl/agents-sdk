<?php

namespace Swis\Agents\Orchestrator;

use Closure;
use Swis\Agents\AgentObserver;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Response\Payload;

/**
 * StreamedAgentObserver class for handling streaming responses.
 *
 * This class is responsible for:
 * - Receiving streaming response tokens
 */
class StreamedAgentObserver extends AgentObserver
{
    /**
     * The callback to invoke for each response token
     */
    protected Closure $responseCallback;

    /**
     * Set the callback to handle streaming response tokens
     *
     * @param Closure $callback The callback function that receives each token
     * @return self
     */
    public function withResponseCallback(Closure $callback): self
    {
        $this->responseCallback = $callback;

        return $this;
    }

    /**
     * Handle an intermediate response token during streaming
     *
     * @param AgentInterface $agent The agent that generated the response
     * @param Payload $payload The response payload token
     * @param RunContext $context The run context
     * @return void
     */
    public function onResponseInterval(AgentInterface $agent, Payload $payload, RunContext $context): void
    {
        if (! isset($this->responseCallback)) {
            return;
        }

        // Invoke the callback with the payload and context
        ($this->responseCallback)($payload, $context);
    }
}
