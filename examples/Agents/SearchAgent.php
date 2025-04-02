<?php

use Swis\Agents\Agent;
use Swis\Agents\Tool;
use Swis\Agents\Tool\Enum;
use Swis\Agents\Tool\Required;
use Swis\Agents\Tool\ToolParameter;

class SearchAgent
{
    public function __invoke(): Agent
    {
        return new Agent(
            name: 'Search Agent',
            description: 'This agent searches documents to give grounded answers.',
            instruction: 'You answer the users question but only if you can find supported documents. Always use the search tool to find relevant documents. If you can\'t find any documents, tell the user you don\'t know the answer.',
            tools: [
                new SearchTool(),
            ]
        );
    }

}

class SearchTool extends Tool
{
    #[ToolParameter('The search phrase.'), Required]
    public string $searchPhrase;

    #[ToolParameter('The filters to apply to this search.', objectClass: SearchFilter::class)]
    public array $filters = [];

    protected ?string $toolDescription = 'Search for documents';

    public function __invoke(): ?string
    {
        return json_encode([
            'filters' => array_map(fn($filter) => $filter->parameter . ' ' . $filter->operator . ' ' . $filter->value, $this->filters),
            'documents' => [
                ['title' => 'Document 1', 'author' => 'John Doe', 'date' => '2025-01-01', 'content' => 'The capital of France is Paris.'],
                ['title' => 'Document 2', 'author' => 'Jane Doe', 'date' => '2025-01-02', 'content' => 'SWIS is a digital agency in Leiden, The Netherlands.'],
                ['title' => 'Document 3', 'author' => 'John Doe', 'date' => '2025-01-03', 'content' => 'The Eiffel Tower is in Paris.'],
            ]
        ]);
    }
}

class SearchFilter
{
    #[ToolParameter('The parameter to filter.'), Required]
    #[Enum(['author', 'date', 'title'])]
    public string $parameter;

    #[ToolParameter('The value of the filter.'), Required]
    public string $value;

    #[ToolParameter('The operator.')]
    #[Enum(['=', '>', '<', '>=', '<=', '!='])]
    public string $operator = '=';
}
