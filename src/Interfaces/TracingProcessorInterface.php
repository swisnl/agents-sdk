<?php

namespace Swis\Agents\Interfaces;

use Swis\Agents\Tracing\Span;
use Swis\Agents\Tracing\Trace;

/**
 * Interface for processing and managing trace data.
 * 
 * Implementations of this interface handle the lifecycle of traces and spans,
 * providing methods to start and stop spans, track active spans, and maintain
 * the relationships between them.
 */
interface TracingProcessorInterface
{
    /**
     * Start a new trace for a workflow.
     *
     * @param string $workflowName Name of the workflow being traced
     * @param string|null $traceId Optional trace identifier
     * @param string|null $groupId Optional group identifier for correlation
     * @param array $metaData Optional metadata for the trace
     * @return void
     */
    public function start(string $workflowName, ?string $traceId = null, ?string $groupId = null, array $metaData = []): void;
    
    /**
     * Get the current active trace.
     *
     * @return Trace|null The current trace or null if no trace has been started
     */
    public function trace(): ?Trace;
    
    /**
     * Check if a trace has been started.
     *
     * @return bool True if a trace has been started, false otherwise
     */
    public function isStarted(): bool;
    
    /**
     * Start a new span and make it the currently active span.
     *
     * @param Span $span The span to start
     * @return Span The started span
     */
    public function startSpan(Span $span): Span;
    
    /**
     * Stop a specific span.
     *
     * @param Span $span The span to stop
     * @return Span The stopped span
     */
    public function stopSpan(Span $span): Span;
    
    /**
     * Stop the currently active span.
     *
     * @return Span|null The stopped span, or null if no current span
     */
    public function stopCurrent(): ?Span;
}