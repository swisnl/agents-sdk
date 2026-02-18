<?php

namespace Swis\Agents\Mcp;

use Closure;
use Psr\Cache\CacheItemPoolInterface;
use Swis\Agents\Exceptions\HandleToolException;
use Swis\Agents\Interfaces\McpConnectionInterface;
use Swis\Agents\Tool;
use Swis\McpClient\Client;
use Swis\McpClient\Requests\BaseRequest;
use Swis\McpClient\Requests\CallToolRequest;
use Swis\McpClient\Requests\ListToolsRequest;
use Swis\McpClient\Results\CallToolResult;
use Swis\McpClient\Results\JsonRpcError;
use Swis\McpClient\Schema\Content\TextContent;
use Throwable;

/**
 * Represents a connection to an MCP server.
 *
 * This class manages the connection with an MCP server and provides
 * access to its tools and functionality.
 */
class McpConnection implements McpConnectionInterface
{
    /**
     * @var array<string> Allowed tool names for this MCP connection
     */
    protected array $allowedToolNames = [];

    /**
     * @var array<McpTool>|null Cached tools from the MCP server
     */
    protected ?array $tools = null;

    /**
     * @var string The cache key for storing MCP tools
     */
    protected string $cacheKey = 'mcp_tools';

    /**
     * @var int Cache lifetime in seconds (default: 1 hour)
     */
    protected int $cacheTtl = 3600;

    /**
     * The metadata that will be sent with each MCP request.
     *
     * @var array<string, mixed>|Closure
     */
    protected array|Closure $meta = [];

    /**
     * Constructor
     *
     * @param Client $client The MCP client
     * @param string $name Connection name for identification
     * @param CacheItemPoolInterface|null $cache PSR-6 cache implementation
     */
    public function __construct(
        protected Client $client,
        protected string $name,
        protected ?CacheItemPoolInterface $cache = null
    ) {
        $this->cacheKey = 'mcp_tools_' . md5($this->name);
    }

    /**
     * Create a new MCP connection for a given Streamable HTTP endpoint
     *
     * @param string $endpoint
     * @param array<string, string> $headers
     * @return self
     */
    public static function forStreamableHttp(string $endpoint, array $headers = []): self
    {
        $client = Client::withStreamableHttp(
            endpoint: $endpoint,
            headers: $headers,
        );

        $connection = new self($client, 'MCP server');
        $connection->withCacheKey('mcp_tools_' . md5($endpoint));

        return $connection;
    }

    /**
     * Create a new MCP connection for a given SSE endpoint
     *
     * @param string $endpoint
     * @param array<string, string> $headers
     * @return self
     */
    public static function forSse(string $endpoint, array $headers = []): self
    {
        $client = Client::withSse(
            endpoint: $endpoint,
            headers: $headers,
        );

        $connection = new self($client, 'MCP server');
        $connection->withCacheKey('mcp_tools_' . md5($endpoint));

        return $connection;
    }

    /**
     * Create a new MCP connection for a given process command
     *
     * @param string $processCommand The command to start the process
     * @param int $autoRestartAmount Amount of times to allow auto-restart the process when the process terminates unexpectedly
     * @return array{0: self, 1: resource}
     */
    public static function forProcess(string $processCommand, int $autoRestartAmount = 0): array
    {
        [$client, $process] = Client::withProcess(
            command: $processCommand,
            autoRestartAmount: $autoRestartAmount
        );

        $connection = new self($client, 'MCP server');
        $connection->withCacheKey('mcp_tools_' . md5($processCommand));

        return [$connection, $process];
    }

    /**
     * Only allow specific tools to be used from this MCP connection.
     *
     * @param string ...$toolNames List of tool names to allow
     * @return $this
     */
    public function withTools(string ...$toolNames): self
    {
        $this->allowedToolNames = array_merge($this->allowedToolNames, $toolNames);

        return $this;
    }

    /**
     * Get the connection name
     *
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get the MCP client
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
    }

    /**
     * Set a PSR-6 cache implementation
     *
     * @param CacheItemPoolInterface $cache PSR-6 cache implementation
     * @return $this
     */
    public function withCache(CacheItemPoolInterface $cache): self
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * Set the cache key
     *
     * @param string $cacheKey The cache key to use for storing tools
     * @return $this
     */
    public function withCacheKey(string $cacheKey): self
    {
        $this->cacheKey = $cacheKey;

        return $this;
    }

    /**
     * Set the cache TTL (time to live)
     *
     * @param int $cacheTtl Cache lifetime in seconds
     * @return $this
     */
    public function withCacheTtl(int $cacheTtl): self
    {
        $this->cacheTtl = $cacheTtl;

        return $this;
    }

    /**
     * Sets the metadata that will be sent with each MCP request
     *
     * @param array<string, mixed>|Closure $meta The metadata
     * @return $this
     */
    public function withMeta(array|Closure $meta): self
    {
        $this->meta = $meta;

        return $this;
    }

    /**
     * Connect to the MCP server
     */
    public function connect(): void
    {
        if ($this->client->isConnected()) {
            return;
        }

        $this->client->connect();
    }

    /**
     * List all tools available from this MCP connection
     *
     * @param bool $refresh Whether to refresh the cached tools
     * @return array<McpTool> Array of Tools
     */
    public function listTools(bool $refresh = false): array
    {
        // Use in-memory cache if available and not refreshing
        if (! $refresh && $this->hasInMemoryCache()) {
            return $this->tools ?? [];
        }

        // Try to get tools from persistent cache if available and not refreshing
        if (! $refresh && $this->hasPersistentCache()) {
            $cachedTools = $this->getToolsFromCache();

            if ($cachedTools !== null) {
                $this->tools = $cachedTools;

                return $this->tools;
            }
        }

        // Fetch tools from MCP server
        $this->tools = $this->fetchTools();

        // Store in persistent cache if available
        if ($this->hasPersistentCache()) {
            $this->storeToolsInCache($this->tools);
        }

        return $this->tools ?? [];
    }

    /**
     * Check if tools are cached in memory
     *
     * @return bool
     */
    protected function hasInMemoryCache(): bool
    {
        return isset($this->tools);
    }

    /**
     * Check if persistent cache is available
     *
     * @return bool
     */
    protected function hasPersistentCache(): bool
    {
        return $this->cache !== null;
    }

    /**
     * Get tools from persistent cache
     *
     * @return array<McpTool>|null Array of Tools or null if not found
     */
    protected function getToolsFromCache(): ?array
    {
        if ($this->cache === null) {
            return null;
        }

        $cacheItem = $this->cache->getItem($this->cacheKey);

        if ($cacheItem->isHit()) {
            $cachedTools = $cacheItem->get();

            if (! is_array($cachedTools)) {
                return null;
            }

            /** @var array<McpTool> $cachedTools */
            return $cachedTools;
        }

        return null;
    }

    /**
     * Store tools in persistent cache
     *
     * @param array<McpTool> $tools Array of Tools to cache
     * @return void
     */
    protected function storeToolsInCache(array $tools): void
    {
        if ($this->cache === null) {
            return;
        }

        $cacheItem = $this->cache->getItem($this->cacheKey);
        $cacheItem->set($tools);
        $cacheItem->expiresAfter($this->cacheTtl);
        $this->cache->save($cacheItem);
    }

    /**
     * Execute the tool
     *
     * Calls the MCP tool with the provided arguments
     *
     * @return string The result of the tool call
     * @throws HandleToolException if the tool call fails
     */
    public function callTool(Tool $tool): string
    {
        assert($tool instanceof McpTool);

        try {
            $request = new CallToolRequest(
                name: $tool->name(),
                arguments: $tool->getDynamicPropertyValues()
            );
            $this->addMetadata($request);

            $result = $this->client->callTool($request);

            if ($result instanceof JsonRpcError) {
                throw new HandleToolException($result->getMessage());
            }

            // Extract text content from the result
            return $this->extractTextContent($result);
        } catch (Throwable $e) {
            throw new HandleToolException("Failed to call MCP tool: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Extract text content from a CallToolResult
     *
     * @param CallToolResult $result The call tool result
     * @return string The extracted text content
     */
    protected function extractTextContent(CallToolResult $result): string
    {
        $content = '';
        foreach ($result->getContent() as $item) {
            if ($item instanceof TextContent) {
                $content .= $item->getText();
            }
        }

        return $content;
    }

    /**
     * Fetches the tools from the MCP server
     *
     * @return array<McpTool>
     */
    protected function fetchTools(): array
    {
        $request = new ListToolsRequest();
        $this->addMetadata($request);

        $response = $this->client->listTools($request);

        if ($response instanceof JsonRpcError) {
            throw new \RuntimeException("Error fetching tools: {$response->getMessage()}");
        }

        $tools = $response->getTools();
        if (! empty($this->allowedToolNames)) {
            $tools = array_filter($tools, fn ($tool) => in_array($tool->getName(), $this->allowedToolNames));
        }

        return McpToolFactory::createTools($this, $tools);

    }

    /**
     * Evaluate metadata and add to request
     *
     * @param BaseRequest $request
     * @return void
     */
    protected function addMetadata(BaseRequest $request): void
    {
        if ($this->meta instanceof Closure) {
            $metadata = ($this->meta)($request);
        } else {
            $metadata = $this->meta;
        }

        $request->withMeta($metadata);
    }

    /**
     * Disconnect from the MCP server
     */
    public function disconnect(): void
    {
        $this->client->disconnect();
    }
}
