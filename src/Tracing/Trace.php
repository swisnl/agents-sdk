<?php

namespace Swis\Agents\Tracing;

use Illuminate\Support\Str;
use JsonSerializable;

/**
 * Represents a complete trace of an agent workflow execution.
 *
 * A trace is the top-level container for spans that record the activities
 * within an agent-based workflow. It provides context for all spans that
 * belong to the same execution flow.
 */
class Trace implements JsonSerializable
{
    public string $id;

    /**
     * Create a new trace instance.
     *
     * @param string $name Name of the workflow being traced
     * @param string|null $id Unique identifier for the trace (auto-generated if not provided)
     * @param string|null $groupId Optional group identifier for correlating related traces
     * @param array $metaData Additional contextual information for the trace
     */
    public function __construct(
        public string  $name,
        ?string $id = null,
        public ?string $groupId = null,
        public array   $metaData = [],
    ) {
        $this->id = $id ?? sprintf('trace_%s', Str::uuid());
    }

    /**
     * Convert trace to array format suitable for JSON serialization.
     *
     * This method formats the trace data according to the OpenAI
     * traces API specification.
     *
     * @return array
     */
    public function jsonSerialize(): array
    {
        return [
            'object' => 'trace',
            'id' => $this->id,
            'workflow_name' => $this->name,
            'group_id' => $this->groupId,
            'metadata' => $this->metaData ?: null,
        ];
    }
}
