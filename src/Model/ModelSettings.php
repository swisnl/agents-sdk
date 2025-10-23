<?php

namespace Swis\Agents\Model;

use Swis\Agents\Helpers\EnvHelper;

class ModelSettings
{
    /**
     * @param string $modelName
     * @param float $temperature
     * @param int|null $maxTokens
     * @param array<string, mixed>|null $extraOptions
     */
    public function __construct(
        public string $modelName = '',
        public float $temperature = 1.0,
        public ?int $maxTokens = null,
        public ?array $extraOptions = null,
    ) {
        if (empty($this->modelName)) {
            $this->modelName = EnvHelper::get('AGENTS_SDK_DEFAULT_MODEL') ?: 'gpt-4o';
        }
    }
}
