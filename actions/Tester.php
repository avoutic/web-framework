<?php

namespace WebFramework\Actions;

use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Http\Response;
use Slim\Http\ServerRequest as Request;
use WebFramework\Core\DebugService;
use WebFramework\Core\LatteRenderService;
use WebFramework\Exception\BlacklistException;

class Tester
{
    public function __construct(
        private DebugService $debugService,
        private LatteRenderService $renderer,
    ) {}

    /**
     * @param array<string, string> $routeArgs
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
