<?php

use Swis\Agents\Agent;
use Swis\Agents\AgentObserver;
use Swis\Agents\Orchestrator;
use Swis\Agents\Orchestrator\RunContext;
use Swis\Agents\Response\ToolCall;
use Swis\Agents\Tool;
use Swis\Agents\Interfaces\AgentInterface;
use Swis\Agents\Transporters\ChatCompletionTransporter;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function Laravel\Prompts\info;
use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\textarea;
use function Laravel\Prompts\warning;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

#[AsCommand(name: 'agents:run')]
class RunAgentCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $apiKey = getenv('OPENAI_API_KEY') ?? password('Please provide your OpenAI API key');
        $agent = select('Which agent would you like to run?', $this->listAgents());
        $stream = select('Would you like to stream the conversation?', ['Stream', 'Direct']) === 'Stream';
        $transporter = select('What transporter do you want to use?', ['Responses', 'Chat Completions']);

        require_once __DIR__ . '/Agents/' . $agent;

        $agentClass = basename($agent, '.php');
        /** @var Agent $agent */
        $agent = (new $agentClass())();

        if ($transporter === 'Chat Completions') {
            $agent->withTransporter(new ChatCompletionTransporter());
        }

        $orchestrator = new Orchestrator($agentClass);
        $this->agentObserver($orchestrator);

        /* @phpstan-ignore-next-line */
        while (true) {
            $message = textarea(label: 'Message', required: true);
            $orchestrator->withUserInstruction($message);

            if ($stream) {
                $response = $orchestrator->runStreamed($agent, function ($payload) {
                    echo $payload;
                });
            }
            else {
                $response = $orchestrator->run($agent);
                echo $response;
            }

            // You can continue the conversation with the last agent.
            // Usually, you would want the start agent to reassign the conversation to the correct agent.
            // $agent = $response->owner() ?? $agent;

            echo "\n\n";
        }
    }

    private function listAgents(): array
    {
        $agentFolder = __DIR__ . '/Agents';
        return array_values(array_diff(scandir($agentFolder), ['.', '..']));
    }

    private function agentObserver(Orchestrator $orchestrator): void
    {
        $orchestrator->withAgentObserver(new class extends AgentObserver {

            public function beforeHandoff(AgentInterface $agent, AgentInterface $handoffToAgent, RunContext $context): void
            {
                warning(sprintf('Handoff to: %s', $handoffToAgent->name()));
            }

            public function onToolCall(AgentInterface $agent, Tool $tool, ToolCall $toolCall, RunContext $context): void
            {
                info(sprintf('Tool: %s(%s)', $toolCall->tool, $toolCall->argumentsPayload));
            }

        });
    }
}

$application = new Application();

$application->add(new RunAgentCommand());
$application->setDefaultCommand('agents:run');
$application->run();
