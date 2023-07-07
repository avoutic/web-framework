<?php

namespace WebFramework\Middleware;

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
            return $this->responseEmitter->forbidden($request);
        }
        catch (HttpNotFoundException $e)
        {
            return $this->responseEmitter->notFound($request);
        }
        catch (HttpUnauthorizedException $e)
        {
            return $this->responseEmitter->unauthorized($request);
        }
        catch (BlacklistException $e)
        {
            return $this->responseEmitter->blacklisted($request);
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

            return $this->responseEmitter->error($request, 'Error');
        }
    }
}
