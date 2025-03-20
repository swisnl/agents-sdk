<?php

namespace Swis\Agents\Tests\Fixtures;

use Psr\Http\Message\RequestInterface;
use Swis\Http\Fixture\ResponseBuilder as BaseResponseBuilder;

class ResponseBuilder extends BaseResponseBuilder
{
    protected int $i = 0;

    protected function getQueryFromRequest(RequestInterface $request, string $replacement = '-'): string
    {
        $this->i++;

        return parent::getQueryFromRequest($request, $replacement) . $this->i;
    }
}
