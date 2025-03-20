<?php

namespace Swis\Agents\Tests\Integration;

use OpenAI\Client as OpenAIClient;
use OpenAI\Factory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Swis\Http\Fixture\Client;
use Swis\Agents\Orchestrator;
use Swis\Agents\Tests\Fixtures\ResponseBuilder;

abstract class BaseOrchestratorTestCase extends TestCase
{
    protected OpenAIClient $client;
    protected Orchestrator $orchestrator;

    protected function setUp(): void
    {
        parent::setUp();

        $responseBuilder = new ResponseBuilder(__DIR__ . '/../Fixtures/'.class_basename($this));
        $httpClient = new Client($responseBuilder);

        $this->client = (new Factory())
            ->withHttpClient($httpClient)
            ->withApiKey('test-api-key')
            ->withStreamHandler(fn(RequestInterface $request) => $httpClient->sendRequest($request))
            ->make();

        $this->orchestrator = (new Orchestrator('Workflow'))
            ->withClient($this->client)
            ->disableTracing();
    }
}