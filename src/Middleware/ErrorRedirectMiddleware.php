<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Middleware;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use WebFramework\Core\DebugService;
use WebFramework\Core\ReportFunction;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Exception\BlacklistException;
use WebFramework\Exception\RedirectException;

/**
 * Middleware to handle errors and exceptions.
 */
class ErrorRedirectMiddleware implements MiddlewareInterface
{
    /**
     * @param Container       $container       The DI container
     * @param DebugService    $debugService    The debug service
     * @param ReportFunction  $reportFunction  The report function
     * @param ResponseEmitter $responseEmitter The response emitter
     */
    public function __construct(
        private Container $container,
        private DebugService $debugService,
        private ReportFunction $reportFunction,
        private ResponseEmitter $responseEmitter,
    ) {}

    /**
     * Process an incoming server request.
     *
     * @param Request                 $request The request
     * @param RequestHandlerInterface $next    The handler
     */
    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        try
        {
            return $next->handle($request);
        }
        catch (RedirectException $e)
        {
            return $this->responseEmitter->redirect($e->getUrl());
        }
        catch (HttpForbiddenException $e)
        {
            return $this->responseEmitter->forbidden($e->getRequest());
        }
        catch (HttpNotFoundException $e)
        {
            return $this->responseEmitter->notFound($e->getRequest());
        }
        catch (HttpUnauthorizedException $e)
        {
            return $this->responseEmitter->unauthorized($e->getRequest());
        }
        catch (BlacklistException $e)
        {
            return $this->responseEmitter->blacklisted($e->getRequest());
        }
        catch (\Throwable $e)
        {
            $errorReport = $this->debugService->getThrowableReport($e, $request);

            $request = $request->withAttribute('error_report', $errorReport);

            try
            {
                $this->reportFunction->report($e->getMessage(), 'unhandled_exception', $errorReport);
            }
            catch (\Throwable $e)
            {
                // Cannot send error. No sense in reporting that as well.
                //
            }

            $message = ($this->container->get('debug')) ? $errorReport['message'] : '';

            return $this->responseEmitter->error($request, 'Error', $message);
        }
    }
}
