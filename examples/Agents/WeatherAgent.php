<?php

use Swis\Agents\Agent;
use Swis\Agents\Tool;
use Swis\Agents\Tool\Required;
use Swis\Agents\Tool\ToolParameter;

class WeatherAgent
{
    public function __invoke(): Agent
    {
        return new Agent(
            name: 'Weather Agent',
            description: 'This agent provides weather information.',
            instruction: 'You help the user with their question about the weather. Only provide the current weather except if explicitly asked otherwise. Current date and time: ' . date('D j F - H:i'),
            tools: [
                new FetchWeatherInformationTool(),
            ]
        );
    }

}

class FetchWeatherInformationTool extends Tool
{
    #[ToolParameter('The name of the city. Make sure the name is in English.'), Required]
    public string $city;

    protected ?string $toolDescription = 'Gets the current weather and forecast by city.';

    public function __invoke(): ?string
    {
        return file_get_contents(sprintf('https://wttr.in/%s?AqTFd', $this->city));
    }
}