<?php

namespace Swis\Agents\Tracing;

use Swis\Agents\Interfaces\TracingExporterInterface;
use Swis\Agents\Interfaces\TracingProcessorInterface;
use Swis\Agents\Orchestrator\RunContext;

/**
 * Central processor for managing traces and spans in the agent system.
 * 
 * The Processor manages the lifecycle of traces and spans, maintaining the
 * current active span, the hierarchical relationship between spans, and
 * exporting trace data to a configured exporter when execution completes.
 */
class Processor implements TracingProcessorInterface
{
    /**
     * The active trace being recorded
     */
    protected Trace $trace;

    /**
     * Collection of all spans in the current trace
     * 
     * @var array<Span>
     */
    protected array $spans = [];
    
    /**
     * The currently active span
     */
    protected ?Span $currentSpan = null;
    
    /**
     * Timestamp of the previous span's end time, used for sequential span timing
     */
    protected ?float $previousSpanTime = null;

    /**
     * Create a new trace processor.
     * 
     * Sets up automatic exporting of trace data when the program shuts down.
     *
     * @param TracingExporterInterface $exporter The exporter to send trace data to
     * @param RunContext $context Execution context to register observers with
     */
    public function __construct(protected TracingExporterInterface $exporter, protected RunContext $context)
    {
        register_shutdown_function(fn() => $this->exporter->export([$this->trace, ...$this->spans]));
    }

    /**
     * Start a new trace for a workflow.
     * 
     * Creates a new trace and registers a TraceAgentObserver to capture
     * agent lifecycle events.
     *
     * @param string $workflowName Name of the workflow being traced
     * @param string|null $traceId Optional trace identifier
     * @param string|null $groupId Optional group identifier for correlation
     * @param array $metaData Optional metadata for the trace
     * @return void
     */
    public function start(string $workflowName, ?string $traceId = null, ?string $groupId = null, array $metaData = []): void
    {
        $this->trace = new Trace($workflowName, $traceId, $groupId, $metaData);
        $this->spans = [];

        $this->context
            ->removeAgentObserver(TraceAgentObserver::class)
            ->withAgentObserver(new TraceAgentObserver($this));
    }

    /**
     * Get the current active trace.
     *
     * @return Trace|null The current trace or null if no trace has been started
     */
    public function trace(): ?Trace
    {
        return $this->trace;
    }

    /**
     * Check if a trace has been started.
     *
     * @return bool True if a trace has been started, false otherwise
     */
    public function isStarted(): bool
    {
        return isset($this->trace);
    }

    /**
     * Start a new span and make it the currently active span.
     *
     * @param Span $span The span to start
     * @return Span The started span
     */
    public function startSpan(Span $span): Span
    {
        $this->spans[$span->id] = $span;
        $span->start();

        $this->currentSpan = $span;

        return $span;
    }

    /**
     * Start a new span that begins exactly when the previous span ended.
     * 
     * This creates a continuous timeline without gaps between operations.
     *
     * @param Span $span The span to start
     * @return Span The started span
     */
    public function startSpanAfterPrevious(Span $span): Span
    {
        $span = $this->startSpan($span);

        if ($this->previousSpanTime) {
            $span->withStartTime($this->previousSpanTime);
        }

        return $span;
    }

    /**
     * Stop a specific span and set its parent as the new current span.
     *
     * @param Span $span The span to stop
     * @return Span The stopped span
     */
    public function stopSpan(Span $span): Span
    {
        $span->stop();
        $this->currentSpan = $this->spans[$span->parentId] ?? null;

        return $span;
    }

    /**
     * Stop the currently active span and record its end time.
     *
     * @return Span|null The stopped span, or null if no current span
     */
    public function stopCurrent(): ?Span
    {
        if (!$this->currentSpan) {
            return null;
        }

        $span = $this->stopSpan($this->currentSpan);
        $this->previousSpanTime = $span->stopTime();

        return $span;
    }
}