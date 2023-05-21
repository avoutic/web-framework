<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Factory\ResponseFactory;

class ResponseEmitter
{
    public function __construct(
        private ConfigService $config_service,
        private MessageService $message_service,
        private ObjectFunctionCaller $object_function_caller,
        private ResponseFactory $response_factory,
    ) {
    }

    public function emit(Response $response): void
    {
        // Emit status line
        //
        header(sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ), true, $response->getStatusCode());

        // Emit headers
        //
        foreach ($response->getHeaders() as $name => $values)
        {
            foreach ($values as $value)
            {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        // Emit body
        //
        echo $response->getBody();

        exit();
    }

    public function blacklisted(Request $request, Response $response): Response
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

    public function error(Request $request, Response $response, string $title, string $details = '', int $http_code = 500, string $reason_phrase = 'Internal error'): Response
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

    public function forbidden(Request $request, Response $response): Response
    {
        $response = $this->response_factory->createResponse(403, 'Forbidden');

        if ($request->getAttribute('is_json') === true)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $mapping = $this->config_service->get('error_handlers.403');

        return $this->object_function_caller->execute($this->config_service->get('actions.app_namespace').$mapping, 'html_main', $request, $response);
    }

    public function not_found(Request $request, Response $response): Response
    {
        $response = $this->response_factory->createResponse(404, 'Not Found');

        if ($request->getAttribute('is_json') === true)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $mapping = $this->config_service->get('error_handlers.404');

        return $this->object_function_caller->execute($this->config_service->get('actions.app_namespace').$mapping, 'html_main', $request, $response);
    }

    public function redirect(string $url, int $redirect_type): Response
    {
        $response = $this->response_factory->createResponse($redirect_type);

        return $response->withHeader('Location', $url);
    }

    public function unauthorized(Request $request, Response $response): Response
    {
        $response = $this->response_factory->createResponse(401, 'Unauthorized');

        if ($request->getAttribute('is_json') === true)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $message = $this->message_service->get_for_url(
            'info',
            $this->config_service->get('authenticator.auth_required_message'),
        );

        return $this->redirect(
            $this->config_service->get('base_url').$this->config_service->get('actions.login.location').
            '?return_page='.urlencode($request->getUri()->getPath()).
            '&return_query='.urlencode($request->getUri()->getQuery()).
            '&'.$message,
            302
        );
    }
}
