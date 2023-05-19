<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;

class ResponseEmitter
{
    public function __construct(
        private ConfigService $config_service,
        private ObjectFunctionCaller $object_function_caller,
        private ResponseFactory $response_factory,
    ) {
    }

    public function blacklisted(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $this->response_factory->createResponse(403, 'Forbidden');

        if ($request->getAttribute('is_json') === true)
        {
            $response = $response->withHeader('Content-type', 'application/json');

            $data = json_encode([
                'failure' => 'blacklisted',
            ]);

            $response->getBody()->write($data ?: '');

            return $response;
        }

        $mapping = $this->config_service->get('error_handlers.blacklisted');

        return $this->object_function_caller->execute($this->config_service->get('actions.app_namespace').$mapping, 'html_main', $request, $response);
    }

    public function error(ServerRequestInterface $request, ResponseInterface $response, string $title, string $details = '', int $http_code = 500, string $reason_phrase = 'Internal error'): ResponseInterface
    {
        $response = $this->response_factory->createResponse($http_code, $reason_phrase);

        if ($request->getAttribute('is_json') === true)
        {
            $response = $response->withHeader('Content-type', 'application/json');

            $data = json_encode(
                [
                    'success' => false,
                    'title' => $title,
                    'details' => $details,
                ]
            );

            $response->getBody()->write($data ?: '');

            return $response;
        }

        $mapping = $this->config_service->get('error_handlers.500');

        return $this->object_function_caller->execute($this->config_service->get('actions.app_namespace').$mapping, 'html_main', $request, $response);
    }

    public function forbidden(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $this->response_factory->createResponse(403, 'Forbidden');

        if ($request->getAttribute('is_json') === true)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $mapping = $this->config_service->get('error_handlers.403');

        return $this->object_function_caller->execute($this->config_service->get('actions.app_namespace').$mapping, 'html_main', $request, $response);
    }

    public function not_found(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $this->response_factory->createResponse(404, 'Not Found');

        if ($request->getAttribute('is_json') === true)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $mapping = $this->config_service->get('error_handlers.404');

        return $this->object_function_caller->execute($this->config_service->get('actions.app_namespace').$mapping, 'html_main', $request, $response);
    }

    public function redirect(string $url, int $redirect_type): ResponseInterface
    {
        $response = $this->response_factory->createResponse($redirect_type);

        return $response->withHeader('Location', $url);
    }

    public function unauthorized(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $response = $this->response_factory->createResponse(401, 'Unauthorized');

        if ($request->getAttribute('is_json') === true)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $mapping = $this->config_service->get('error_handlers.401');

        return $this->object_function_caller->execute($this->config_service->get('actions.app_namespace').$mapping, 'html_main', $request, $response);
    }
}
