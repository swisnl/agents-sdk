<?php

namespace Swis\Agents\Model;

class ModelSettings
{
    public function __construct(
        public string $modelName = '',
        public float $temperature = .7,
        public ?int $maxTokens = null,
    ) {
        if (empty($this->modelName)) {
            $this->modelName = env('AGENTS_SDK_DEFAULT_MODEL') ?: 'gpt-4o';
        }
    }
}
