<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface RenderService
{
    /**
     * @param array<string, mixed> $params
     */
    public function render(Request $request, Response $response, string $templateFile, array $params): Response;

    /**
     * @param array<string, mixed> $params
     */
    public function renderToString(Request $request, string $templateFile, array $params): string;
}
