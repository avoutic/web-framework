<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Diagnostics\DebugService;
use WebFramework\Exception\BlacklistException;
use WebFramework\Presentation\LatteRenderService;

/**
 * Class Tester.
 *
 * This action is used for testing various error and exception scenarios.
 */
class Tester
{
    /**
     * Tester constructor.
     *
     * @param DebugService       $debugService The debug service
     * @param LatteRenderService $renderer     The Latte render service
     */
    public function __construct(
        private DebugService $debugService,
        private LatteRenderService $renderer,
    ) {}

    /**
     * Handle the test request.
     *
     * @param Request               $request   The current request
     * @param Response              $response  The response object
     * @param array<string, string> $routeArgs Route arguments
     *
     * @return ResponseInterface The response
     *
     * @throws HttpNotFoundException     If a 404 error is requested
     * @throws HttpForbiddenException    If a 403 error is requested
     * @throws HttpUnauthorizedException If a 401 error is requested
     * @throws BlacklistException        If a blacklist error is requested
     * @throws \RuntimeException         If a general exception is requested
     */
    public function __invoke(Request $request, Response $response, $routeArgs): ResponseInterface
    {
        $action = $request->getParam('action');

        if ($action === '404')
        {
            throw new HttpNotFoundException($request);
        }

        if ($action === '403')
        {
            throw new HttpForbiddenException($request);
        }

        if ($action === '401')
        {
            // Remove query string from Uri or risk infinite loop when logged in, returning here
            //
            $uri = $request->getUri()->withQuery('');
            $request = $request->withUri($uri);

            throw new HttpUnauthorizedException($request);
        }

        if ($action === 'blacklist')
        {
            throw new BlacklistException($request);
        }

        if ($action === 'exception')
        {
            throw new \RuntimeException('Triggered error');
        }

        if ($action === 'report_error')
        {
            $this->debugService->reportError('Reported error');
        }

        if ($action === 'warning')
        {
            trigger_error('Triggered warning', E_USER_WARNING);
        }

        if ($action === 'error')
        {
            trigger_error('Triggered error', E_USER_ERROR);
        }

        if ($action === 'php_error')
        {
            trigger_error('Triggered PHP error', E_ERROR);
        }

        return $this->renderer->render($request, $response, 'Tester.latte', []);
    }
}
