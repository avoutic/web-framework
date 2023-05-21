<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ResponseFactory;
use WebFramework\Core\ObjectFunctionCaller;
use WebFramework\Core\ResponseEmitter;
use WebFramework\Core\RouteService;
use WebFramework\Exception\HttpForbiddenException;
use WebFramework\Exception\HttpNotFoundException;
use WebFramework\Exception\HttpUnauthorizedException;

class RoutingMiddleware implements RequestHandlerInterface
{
    public function __construct(
        private ObjectFunctionCaller $object_function_caller,
        private ResponseEmitter $response_emitter,
        private ResponseFactory $response_factory,
        private RouteService $route_service,
        private string $action_app_namespace,
    ) {
    }

    public function handle(Request $request): Response
    {
        $redirect = $this->route_service->get_redirect($request);
        if ($redirect !== false)
        {
            return $this->response_emitter->redirect($redirect['url'], $redirect['redirect_type']);
        }

        $response = $this->response_factory->createResponse(200, 'OK');
        $action = $this->route_service->get_action($request);
        if ($action !== false)
        {
            $request = $request->withAttribute('route_inputs', $action['args']);

            try
            {
                return $this->object_function_caller->execute($this->action_app_namespace.$action['class'], $action['function'], $request, $response);
            }
            catch (HttpForbiddenException $e)
            {
                return $this->response_emitter->forbidden($request, $response);
            }
            catch (HttpNotFoundException $e)
            {
                return $this->response_emitter->not_found($request, $response);
            }
            catch (HttpUnauthorizedException $e)
            {
                return $this->response_emitter->unauthorized($request, $response);
            }
        }

        return $this->response_emitter->not_found($request, $response);
    }
}
