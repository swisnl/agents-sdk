<?php declare(strict_types = 1);

$ignoreErrors = [];
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
	'message' => '#^If condition is always true\\.$#',
	'identifier' => 'if.alwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Left side of && is always true\\.$#',
	'identifier' => 'booleanAnd.leftAlwaysTrue',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Helpers\\\\ToolHelper\\:\\:toolToDefinition\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$objectOrClass of class ReflectionClass constructor expects class\\-string\\<T of object\\>\\|T of object, string given\\.$#',
	'identifier' => 'argument.type',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter \\#1 \\$properties of static method Swis\\\\Agents\\\\Helpers\\\\ToolHelper\\:\\:processObjectProperties\\(\\) expects array\\<array\\<string, mixed\\>\\>, array\\<string, string\\> given\\.$#',
	'identifier' => 'argument.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Parameter &\\$properties by\\-ref type of method Swis\\\\Agents\\\\Helpers\\\\ToolHelper\\:\\:processObjectProperties\\(\\) expects array\\<array\\<string, mixed\\>\\>, array\\<array\\<mixed\\>\\> given\\.$#',
	'identifier' => 'parameterByRef.type',
	'count' => 2,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Interfaces\\\\TracingProcessorInterface\\:\\:start\\(\\) has parameter \\$metaData with no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
	'count' => 1,
	'path' => __DIR__ . '/src/Interfaces/TracingProcessorInterface.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function assert\\(\\) with false will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function assert\\(\\) with true will always evaluate to true\\.$#',
	'identifier' => 'function.alreadyNarrowedType',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Call to function is_array\\(\\) with string will always evaluate to false\\.$#',
	'identifier' => 'function.impossibleType',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'description\' on \\*NEVER\\* on left side of \\?\\? always exists and is not nullable\\.$#',
	'identifier' => 'nullCoalesce.offset',
	'count' => 1,
	'path' => __DIR__ . '/src/Mcp/McpTool.php',
];
$ignoreErrors[] = [
	'message' => '#^Offset \'enum\' on \\*NEVER\\* on left side of \\?\\? always exists and is not nullable\\.$#',
	'identifier' => 'nullCoalesce.offset',
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
	'message' => '#^Offset \'type\' on \\*NEVER\\* on left side of \\?\\? always exists and is not nullable\\.$#',
	'identifier' => 'nullCoalesce.offset',
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
