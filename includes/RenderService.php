<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

interface RenderService
{
    /**
     * @param array<string, mixed> $params
     */
    public function render(Request $request, Response $response, string $template_file, array $params): Response;

    /**
     * @param array<string, mixed> $params
     */
    public function render_to_string(Request $request, string $template_file, array $params): string;
}
