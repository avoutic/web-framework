<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use WebFramework\Core\DebugService;
use WebFramework\Core\ReportFunction;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Exception\BlacklistException;
use WebFramework\Exception\RedirectException;
use WebFramework\Middleware\ErrorRedirectMiddleware;
use WebFramework\Support\ErrorReport;

/**
 * @internal
 *
 * @coversNothing
 */
final class ErrorRedirectMiddlewareTest extends Unit
{
    public function testNormalFlow()
    {
        $response = $this->makeEmpty(Response::class);

        $middleware = $this->make(
            ErrorRedirectMiddleware::class,
        );

        $request = $this->make(Request::class);
        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once($response),
            ]
        );

        verify($middleware->process($request, $handler))->equals($response);
    }

    public function testRedirectException()
    {
        $middleware = $this->make(
            ErrorRedirectMiddleware::class,
            [
                'responseEmitter' => $this->makeEmpty(
                    ResponseEmitter::class,
                    [
                        'redirect' => Expected::once(
                            $this->makeEmpty(Response::class)
                        ),
                    ]
                ),
            ]
        );

        $request = $this->make(Request::class);
        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function () {
                    throw new RedirectException('http://example.com');
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testForbiddenException()
    {
        $middleware = $this->make(
            ErrorRedirectMiddleware::class,
            [
                'responseEmitter' => $this->makeEmpty(
                    ResponseEmitter::class,
                    [
                        'forbidden' => Expected::once(
                            $this->makeEmpty(Response::class)
                        ),
                    ]
                ),
            ]
        );

        $request = $this->make(Request::class);
        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function () use ($request) {
                    throw new HttpForbiddenException($request);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testNotFoundException()
    {
        $middleware = $this->make(
            ErrorRedirectMiddleware::class,
            [
                'responseEmitter' => $this->makeEmpty(
                    ResponseEmitter::class,
                    [
                        'notFound' => Expected::once(
                            $this->makeEmpty(Response::class)
                        ),
                    ]
                ),
            ]
        );

        $request = $this->make(Request::class);
        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function () use ($request) {
                    throw new HttpNotFoundException($request);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testUnauthorizedException()
    {
        $middleware = $this->make(
            ErrorRedirectMiddleware::class,
            [
                'responseEmitter' => $this->makeEmpty(
                    ResponseEmitter::class,
                    [
                        'unauthorized' => Expected::once(
                            $this->makeEmpty(Response::class)
                        ),
                    ]
                ),
            ]
        );

        $request = $this->make(Request::class);
        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function () use ($request) {
                    throw new HttpUnauthorizedException($request);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testBlacklistException()
    {
        $middleware = $this->make(
            ErrorRedirectMiddleware::class,
            [
                'responseEmitter' => $this->makeEmpty(
                    ResponseEmitter::class,
                    [
                        'blacklisted' => Expected::once(
                            $this->makeEmpty(Response::class)
                        ),
                    ]
                ),
            ]
        );

        $request = $this->make(Request::class);
        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function () use ($request) {
                    throw new BlacklistException($request, 'Blacklisted');
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testGenericExceptionWithDebugEnabled()
    {
        $errorReport = $this->makeEmpty(ErrorReport::class);

        $middleware = $this->make(
            ErrorRedirectMiddleware::class,
            [
                'container' => $this->makeEmpty(
                    Container::class,
                    [
                        'get' => Expected::once(true),
                    ]
                ),
                'debugService' => $this->makeEmpty(
                    DebugService::class,
                    [
                        'getThrowableReport' => Expected::once($errorReport),
                    ]
                ),
                'logger' => $this->makeEmpty(
                    LoggerInterface::class,
                    [
                        'error' => Expected::once(),
                    ]
                ),
                'exceptionLogger' => $this->makeEmpty(
                    LoggerInterface::class,
                    [
                        'error' => Expected::once(),
                    ]
                ),
                'reportFunction' => $this->makeEmpty(
                    ReportFunction::class,
                    [
                        'report' => Expected::once(),
                    ]
                ),
                'responseEmitter' => $this->makeEmpty(
                    ResponseEmitter::class,
                    [
                        'error' => Expected::once(
                            $this->makeEmpty(Response::class)
                        ),
                    ]
                ),
            ]
        );

        $request = $this->make(Request::class);
        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function () {
                    throw new \Exception('Test error');
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testGenericExceptionWithDebugDisabled()
    {
        $errorReport = $this->makeEmpty(ErrorReport::class);

        $middleware = $this->make(
            ErrorRedirectMiddleware::class,
            [
                'container' => $this->makeEmpty(
                    Container::class,
                    [
                        'get' => Expected::once(false),
                    ]
                ),
                'debugService' => $this->makeEmpty(
                    DebugService::class,
                    [
                        'getThrowableReport' => Expected::once($errorReport),
                    ]
                ),
                'logger' => $this->makeEmpty(
                    LoggerInterface::class,
                    [
                        'error' => Expected::once(),
                    ]
                ),
                'exceptionLogger' => $this->makeEmpty(
                    LoggerInterface::class,
                    [
                        'error' => Expected::once(),
                    ]
                ),
                'reportFunction' => $this->makeEmpty(
                    ReportFunction::class,
                    [
                        'report' => Expected::once(),
                    ]
                ),
                'responseEmitter' => $this->makeEmpty(
                    ResponseEmitter::class,
                    [
                        'error' => Expected::once(
                            $this->makeEmpty(Response::class)
                        ),
                    ]
                ),
            ]
        );

        $request = $this->make(Request::class);
        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function () {
                    throw new \Exception('Test error');
                }),
            ]
        );

        $middleware->process($request, $handler);
    }
}
