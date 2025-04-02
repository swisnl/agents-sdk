<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Agent\\:\\:buildRequestPayload\\(\\) should return array\\{model\\: string, temperature\\: float, max_completion_tokens\\: int\\|null, messages\\: array\\<Swis\\\\Agents\\\\Interfaces\\\\MessageInterface\\>, tools\\?\\: array\\{type\\: \'function\', function\\: array\\<string, mixed\\>\\}, stream_options\\?\\: array\\{include_usage\\: bool\\}\\} but returns array\\{model\\: string, temperature\\: float, max_completion_tokens\\: int\\|null, messages\\: array\\<Swis\\\\Agents\\\\Interfaces\\\\MessageInterface\\>, tools\\?\\: non\\-empty\\-array, stream_options\\?\\: array\\{include_usage\\: true\\}\\}\\.$#',
	'identifier' => 'return.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Agent\\:\\:buildToolsPayload\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Agent\\:\\:toolToPayload\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Agent.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Helpers\\\\ConversationSerializer\\:\\:deserialize\\(\\) has parameter \\$data with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ConversationSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Helpers\\\\ConversationSerializer\\:\\:serialize\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ConversationSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Helpers\\\\ConversationSerializer\\:\\:serializeFromOrchestrator\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ConversationSerializer.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property Swis\\\\Agents\\\\Tool\\\\Enum\\:\\:\\$values\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 2,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Helpers\\\\ToolHelper\\:\\:toolToDefinition\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Interfaces\\\\TracingProcessorInterface\\:\\:start\\(\\) has parameter \\$metaData with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Interfaces/TracingProcessorInterface.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'description\' on mixed\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'enum\' on mixed\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot access offset \'type\' on mixed\\.$#',
	'identifier' => 'offsetAccess.nonOffsetAccessible',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'properties\' on array\\{properties\\: array\\{type\\: string, 0\\: mixed\\}, required\\: array\\<string\\>, type\\: string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'required\' on array\\{properties\\: array\\{type\\: string, 0\\: mixed\\}, required\\: array\\<string\\>, type\\: string\\} in isset\\(\\) always exists and is not nullable\\.$#',
	'identifier' => 'isset.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$name of method Swis\\\\Agents\\\\DynamicTool\\:\\:withDynamicProperty\\(\\) expects string, int\\|string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#2 \\$type of method Swis\\\\Agents\\\\DynamicTool\\:\\:withDynamicProperty\\(\\) expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#3 \\$description of method Swis\\\\Agents\\\\DynamicTool\\:\\:withDynamicProperty\\(\\) expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#5 \\$enum of method Swis\\\\Agents\\\\DynamicTool\\:\\:withDynamicProperty\\(\\) expects array\\<string\\>\\|null, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Swis\\\\Agents\\\\Model\\\\ModelSettings\\:\\:\\$modelName \\(string\\) does not accept mixed\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Model/ModelSettings.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Orchestrator\\:\\:withContextFromData\\(\\) has parameter \\$data with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Orchestrator.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$apiKey of static method OpenAI\\:\\:client\\(\\) expects string, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Orchestrator/RunContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$organization of static method OpenAI\\:\\:client\\(\\) expects string\\|null, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Orchestrator/RunContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\$project of static method OpenAI\\:\\:client\\(\\) expects string\\|null, mixed given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Orchestrator/RunContext.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Swis\\\\Agents\\\\Response\\\\StreamedResponseWrapper\\:\\:\\$capturedToolCalls type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Response/StreamedResponseWrapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Swis\\\\Agents\\\\Response\\\\StreamedResponseWrapper\\:\\:\\$generated type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Response/StreamedResponseWrapper.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Swis\\\\Agents\\\\Response\\\\ToolCall\\:\\:\\$arguments \\(array\\) does not accept mixed\\.$#',
	'identifier' => 'assign.propertyType',
	'count' => 1,
	'path' => __DIR__ . '/src/Response/ToolCall.php',
];
$ignoreErrors[] = [
	'message' => '#^Property Swis\\\\Agents\\\\Response\\\\ToolCall\\:\\:\\$arguments type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Response/ToolCall.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Tracing\\\\OpenAIExporter\\:\\:buildBody\\(\\) has parameter \\$items with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Tracing/OpenAIExporter.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Tracing\\\\Processor\\:\\:start\\(\\) has parameter \\$metaData with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Tracing/Processor.php',
];
$ignoreErrors[] = [
	'message' => '#^Cannot call method format\\(\\) on DateTime\\|false\\.$#',
	'identifier' => 'method.nonObject',
	'count' => 2,
	'path' => __DIR__ . '/src/Tracing/Span.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Tracing\\\\Span\\:\\:__construct\\(\\) has parameter \\$spanData with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Tracing/Span.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Tracing\\\\Span\\:\\:jsonSerialize\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Tracing/Span.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Tracing\\\\Trace\\:\\:__construct\\(\\) has parameter \\$metaData with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Tracing/Trace.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Tracing\\\\Trace\\:\\:jsonSerialize\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Tracing/Trace.php',
];

return ['parameters' => ['ignoreErrors' => $ignoreErrors]];
