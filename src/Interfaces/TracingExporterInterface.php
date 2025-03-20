<?php

namespace Swis\Agents\Interfaces;

use Swis\Agents\Tracing\Span;
use Swis\Agents\Tracing\Trace;

/**
 * Interface for exporting trace data to external systems.
 * 
 * Implementations of this interface handle sending trace data to
 * tracing backends like OpenAI's trace API or other observability
 * platforms for visualization and analysis.
 */
interface TracingExporterInterface
{
    /**
     * Export collected trace data to an external system.
     * 
     * @param array<Span|Trace> $items Collection of traces and spans to export
     * @return void
     */
    public function export(array $items): void;
}