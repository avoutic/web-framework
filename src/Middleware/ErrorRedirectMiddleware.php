<?php

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

class ErrorRedirectMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Container $container,
        private DebugService $debugService,
        private ReportFunction $reportFunction,
        private ResponseEmitter $responseEmitter,
    ) {
    }

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
