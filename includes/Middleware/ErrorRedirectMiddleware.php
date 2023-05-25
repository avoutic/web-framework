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

class ErrorRedirectMiddleware implements MiddlewareInterface
{
    public function __construct(
        private DebugService $debug_service,
        private ReportFunction $report_function,
        private ResponseEmitter $response_emitter,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $next): Response
    {
        try
        {
            return $next->handle($request);
        }
        catch (HttpForbiddenException $e)
        {
            return $this->response_emitter->forbidden($request);
        }
        catch (HttpNotFoundException $e)
        {
            return $this->response_emitter->not_found($request);
        }
        catch (HttpUnauthorizedException $e)
        {
            return $this->response_emitter->unauthorized($request);
        }
        catch (BlacklistException $e)
        {
            return $this->response_emitter->blacklisted($request);
        }
        catch (\Throwable $e)
        {
            $error_report = $this->debug_service->get_throwable_report($e, $request);

            $request = $request->withAttribute('error_report', $error_report);

            $this->report_function->report($e->getMessage(), 'unhandled_exception', $error_report);

            return $this->response_emitter->error($request, 'Error');
        }
    }
}
