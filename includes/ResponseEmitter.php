<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Factory\ResponseFactory;

class ResponseEmitter
{
    public function __construct(
        private Container $container,
        private ConfigService $configService,
        private MessageService $messageService,
        private ResponseFactory $responseFactory,
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

    public function blacklisted(Request $request): Response
    {
        $response = $this->responseFactory->createResponse(403, 'Forbidden');

        if ($request->getAttribute('is_json') === true)
        {
            $response = $response->withHeader('Content-type', 'application/json');

            $data = json_encode([
                'failure' => 'blacklisted',
            ]);

            $response->getBody()->write($data ?: '');

            return $response;
        }

        $class = $this->configService->get('error_handlers.blacklisted');

        $errorHandler = $this->container->get($class);

        return $errorHandler($request, $response);
    }

    public function error(Request $request, string $title, string $details = '', int $httpCode = 500, string $reasonPhrase = 'Internal error'): Response
    {
        $response = $this->responseFactory->createResponse($httpCode, $reasonPhrase);

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

        $class = $this->configService->get('error_handlers.500');

        $errorHandler = $this->container->get($class);

        return $errorHandler($request, $response);
    }

    public function forbidden(Request $request): Response
    {
        $response = $this->responseFactory->createResponse(403, 'Forbidden');

        if ($request->getAttribute('is_json') === true)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $class = $this->configService->get('error_handlers.403');

        $errorHandler = $this->container->get($class);

        return $errorHandler($request, $response);
    }

    public function notFound(Request $request): Response
    {
        $response = $this->responseFactory->createResponse(404, 'Not Found');

        if ($request->getAttribute('is_json') === true)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $class = $this->configService->get('error_handlers.404');

        $errorHandler = $this->container->get($class);

        return $errorHandler($request, $response);
    }

    public function redirect(string $url, int $redirectType = 302): Response
    {
        $response = $this->responseFactory->createResponse($redirectType);

        return $response->withHeader('Location', $url);
    }

    public function unauthorized(Request $request): Response
    {
        $response = $this->responseFactory->createResponse(401, 'Unauthorized');

        if ($request->getAttribute('is_json') === true)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $message = $this->messageService->getForUrl(
            'info',
            $this->configService->get('authenticator.auth_required_message'),
        );

        return $this->redirect(
            $this->configService->get('base_url').$this->configService->get('actions.login.location').
            '?return_page='.urlencode($request->getUri()->getPath()).
            '&return_query='.urlencode($request->getUri()->getQuery()).
            '&'.$message,
            302
        );
    }
}
