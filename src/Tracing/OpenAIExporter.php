<?php

namespace Swis\Agents\Tracing;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Swis\Agents\Interfaces\TracingExporterInterface;

/**
 * Exporter for sending trace data to OpenAI's trace API.
 * 
 * This class collects and formats trace data (spans and traces) and sends them
 * to OpenAI's traces API for visualization and analysis. It uses PSR-18 HTTP
 * client Discovery for different HTTP client implementations.
 */
class OpenAIExporter implements TracingExporterInterface {

    /**
     * HTTP client for making API requests
     */
    protected ClientInterface $client;
    
    /**
     * Base HTTP request template with authentication headers
     */
    protected RequestInterface $request;

    /**
     * Create a new OpenAI trace exporter.
     *
     * @param string|null $apiKey OpenAI API key (falls back to OPENAI_API_KEY env var)
     * @param string|null $organization OpenAI organization ID (falls back to env var)
     * @param string|null $project OpenAI project ID (falls back to env var)
     */
    public function __construct(?string $apiKey = null, ?string $organization = null, ?string $project = null)
    {
        $this->client = $this->buildClient();
        $this->request = $this->buildRequest($apiKey, $organization, $project);
    }

    /**
     * Export trace data to OpenAI's trace API.
     *
     * Takes an array of Trace and Span objects, formats them as JSON according to
     * the OpenAI trace API specification, and sends them to the API endpoint.
     *
     * @param array<Span|Trace> $items Collection of traces and spans to export
     * @return void
     */
    function export(array $items): void
    {
        $request = $this->request
            ->withHeader('Content-Type', 'application/json')
            ->withBody($this->buildBody($items));

        $response = $this->client->sendRequest($request);
        echo $response->getBody()->getContents();
    }

    /**
     * Create an HTTP client using auto-discovery.
     * 
     * Uses PSR-18 client discovery to find an available HTTP client
     * implementation in the project.
     *
     * @return ClientInterface
     */
    protected function buildClient(): ClientInterface
    {
        return Psr18ClientDiscovery::find();
    }

    /**
     * Build the base request with authentication headers.
     * 
     * Creates a PSR-7 request with the appropriate authentication headers
     * for the OpenAI API, including API key, organization, and project.
     *
     * @param string|null $apiKey OpenAI API key
     * @param string|null $organization OpenAI organization ID
     * @param string|null $project OpenAI project ID
     * @return RequestInterface
     */
    protected function buildRequest(?string $apiKey = null, ?string $organization = null, ?string $project = null): RequestInterface
    {
        $request = Psr17FactoryDiscovery::findRequestFactory()
            ->createRequest('post', env('OPENAI_TRACE_API_ENDPOINT') ?: 'https://api.openai.com/v1/traces/ingest')
            ->withHeader('OpenAI-Beta', 'traces=v1');

        $apiKey = $apiKey ?? env('OPENAI_API_KEY');
        $organization = $organization ?? env('AGENTS_SDK_DEFAULT_ORGANIZATION');
        $project = $project ?? env('AGENTS_SDK_DEFAULT_PROJECT');

        if (!empty($apiKey)) {
            $request = $request->withHeader('Authorization', 'Bearer ' . $apiKey);
        }

        if (isset($organization)) {
            $request = $request->withHeader('OpenAI-Organization', $organization);
        }

        if (isset($project)) {
            $request = $request->withHeader('OpenAI-Project', $project);
        }

        return $request;
    }

    /**
     * Build the request body with trace data.
     * 
     * Formats the collection of traces and spans as a JSON payload
     * for the OpenAI traces API.
     *
     * @param array $items Collection of traces and spans to include
     * @return StreamInterface PSR-7 stream containing the JSON payload
     */
    protected function buildBody(array $items): StreamInterface
    {
        return Psr17FactoryDiscovery::findStreamFactory()->createStream(json_encode([
            'data' => array_values($items),
        ]));
    }
}