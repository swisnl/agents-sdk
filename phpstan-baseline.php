<?php declare(strict_types = 1);

$ignoreErrors = [];
$ignoreErrors[] = [
	'message' => '#^Method Swis\\\\Agents\\\\Agent\\:\\:buildRequestPayload\\(\\) return type has no value type specified in iterable type array\\.$#',
	'identifier' => 'missingType.iterableValue',
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
	'message' => '#^Method Swis\\\\Agents\\\\Agent\\:\\:invokeStreamed\\(\\) has parameter \\$payload with no value type specified in iterable type array\\.$#',
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
	'message' => '#^Access to an undefined property object\\:\\:\\$description\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$methodName\\.$#',
	'identifier' => 'property.notFound',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^Access to an undefined property object\\:\\:\\$values\\.$#',
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
	'message' => '#^PHPDoc tag @var with type ReflectionAttribute\\<Swis\\\\Agents\\\\Tool\\\\DerivedEnum\\> is not subtype of native type null\\.$#',
	'identifier' => 'varTag.nativeType',
	'count' => 1,
	'path' => __DIR__ . '/src/Helpers/ToolHelper.php',
];
$ignoreErrors[] = [
	'message' => '#^PHPDoc tag @var with type ReflectionAttribute\\<Swis\\\\Agents\\\\Tool\\\\Enum\\> is not subtype of native type null\\.$#',
	'identifier' => 'varTag.nativeType',
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
