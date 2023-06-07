<?php

namespace WebFramework\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use WebFramework\Core\AssertService;
use WebFramework\Core\LatteRenderService;
use WebFramework\Core\ValidatorService;
use WebFramework\Exception\BlacklistException;

class Tester
{
    public function __construct(
        protected AssertService $assertService,
        protected LatteRenderService $renderer,
        protected ValidatorService $validatorService,
    ) {
    }

    /**
     * @param array<string, string> $routeArgs
     */
    public function __invoke(Request $request, Response $response, $routeArgs): Response
    {
        $inputs = $this->validatorService->getFilteredParams(
            $request,
            [
                'action' => '\w+',
            ]
        );

        $params = [
            'title' => 'Tester',
        ];

        if ($inputs['action'] === '404')
        {
            throw new HttpNotFoundException($request);
        }

        if ($inputs['action'] === '403')
        {
            throw new HttpForbiddenException($request);
        }

        if ($inputs['action'] === '401')
        {
            throw new HttpUnauthorizedException($request);
        }

        if ($inputs['action'] === 'blacklist')
        {
            throw new BlacklistException($request);
        }

        if ($inputs['action'] === 'exception')
        {
            throw new \RuntimeException('Triggered error');
        }

        if ($inputs['action'] === 'report_error')
        {
            $this->assertService->reportError('Reported error');
        }

        if ($inputs['action'] === 'warning')
        {
            trigger_error('Triggered warning', E_USER_WARNING);
        }

        if ($inputs['action'] === 'error')
        {
            trigger_error('Triggered error', E_USER_ERROR);
        }

        if ($inputs['action'] === 'php_error')
        {
            trigger_error('Triggered PHP error', E_ERROR);
        }

        return $this->renderer->render($request, $response, 'tester.latte', $params);
    }
}