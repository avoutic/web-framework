<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Interface RenderService.
 *
 * Defines the contract for render service implementations in the WebFramework.
 * This interface is used for rendering templates and returning responses.
 */
interface RenderService
{
    /**
     * Render a template and write the output to the response body.
     *
     * @param Request              $request      The current request object
     * @param Response             $response     The response object to write to
     * @param string               $templateFile The name of the template file to render
     * @param array<string, mixed> $params       An associative array of parameters to pass to the template
     *
     * @return Response The modified response object with the rendered content
     */
    public function render(Request $request, Response $response, string $templateFile, array $params): Response;

    /**
     * Render a template and return the output as a string.
     *
     * @param Request              $request      The current request object
     * @param string               $templateFile The name of the template file to render
     * @param array<string, mixed> $params       An associative array of parameters to pass to the template
     *
     * @return string The rendered content as a string
     */
    public function renderToString(Request $request, string $templateFile, array $params): string;
}
