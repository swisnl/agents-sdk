<?php

namespace Swis\Agents\Tracing;

use DateTime;
use Illuminate\Support\Str;
use JsonSerializable;

/**
 * Represents a single unit of work within a trace.
 * 
 * Spans record the execution details of individual operations within an agent workflow,
 * such as tool calls, message generation, or agent handoffs. Each span has timing
 * information, can be nested within parent spans, and contains execution data
 * specific to its operation type.
 */
class Span implements JsonSerializable
{
    /**
     * Start time of the span in microseconds
     */
    private float $startTime;
    
    /**
     * End time of the span in microseconds
     */
    private float $stopTime;
    
    /**
     * Optional error message if the span operation failed
     */
    private ?string $error = null;

    /**
     * Create a new span instance.
     *
     * @param string $traceId ID of the trace this span belongs to
     * @param string|null $parentId ID of the parent span (or null if this is a root span)
     * @param string|null $id Unique identifier for the span (auto-generated if not provided)
     * @param array $spanData Operation-specific data for the span (differs based on span type)
     */
    public function __construct(
        public string  $traceId,
        public ?string $parentId = null,
        public ?string $id = null,
        public array   $spanData = [],
    )
    {
        $this->id = $this->id ?? sprintf('span_%s', Str::uuid());
    }

    /**
     * Manually set the start time of the span.
     * 
     * Useful for creating spans after the operation has already started.
     *
     * @param float $startTime Timestamp in microseconds
     * @return Span Self for method chaining
     */
    public function withStartTime(float $startTime): Span
    {
        $this->startTime = $startTime;

        return $this;
    }

    /**
     * Get the start time of the span.
     *
     * @return float|null Start time in microseconds, or null if not yet started
     */
    public function startTime(): ?float
    {
        return $this->startTime ?? null;
    }

    /**
     * Manually set the stop time of the span.
     *
     * @param float $stopTime Timestamp in microseconds
     * @return Span Self for method chaining
     */
    public function withStopTime(float $stopTime): Span
    {
        $this->stopTime = $stopTime;

        return $this;
    }

    /**
     * Get the stop time of the span.
     *
     * @return float|null Stop time in microseconds, or null if not yet stopped
     */
    public function stopTime(): ?float
    {
        return $this->stopTime ?? null;
    }

    /**
     * Mark the span as an error with an error message.
     *
     * @param string|null $error Error message describing what went wrong
     * @return Span Self for method chaining
     */
    public function withError(?string $error): Span
    {
        $this->error = $error;

        return $this;
    }

    /**
     * Start the span timer if not already started.
     *
     * @return void
     */
    public function start(): void
    {
        if (isset($this->startTime)) {
            return;
        }

        $this->startTime = microtime(true);
    }

    /**
     * Stop the span timer if not already stopped.
     *
     * @return void
     */
    public function stop(): void
    {
        if (isset($this->stopTime)) {
            return;
        }

        $this->stopTime = microtime(true);
    }

    /**
     * Convert span to array format suitable for JSON serialization.
     * 
     * This method formats the span data according to the OpenAI
     * traces API specification, automatically stopping the span
     * if it hasn't been stopped yet.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        $this->stop();

        $data = [
            'object' => 'trace.span',
            'id' => $this->id,
            'trace_id' => $this->traceId,
            'parent_id' => $this->parentId,
            'started_at' => $this->startTime ? DateTime::createFromFormat('U.u', sprintf('%.6F', $this->startTime))->format('Y-m-d\TH:i:s.u\Z') : null,
            'ended_at' => $this->stopTime ? DateTime::createFromFormat('U.u', sprintf('%.6F', $this->stopTime))->format('Y-m-d\TH:i:s.u\Z') : null,
            'span_data' => $this->spanData ?: null,
        ];

        if ($this->error) {
            $data['error'] = ['message' => $this->error];
        }

        return $data;
    }
}