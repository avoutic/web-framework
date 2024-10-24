<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Factory\ResponseFactory;

/**
 * Class ResponseEmitter.
 *
 * Handles the emission of HTTP responses and various error responses.
 */
class ResponseEmitter
{
    /**
     * ResponseEmitter constructor.
     *
     * @param Container       $container       The dependency injection container
     * @param ConfigService   $configService   The configuration service
     * @param ResponseFactory $responseFactory The PSR-7 response factory
     * @param UrlBuilder      $urlBuilder      The URL builder service
     */
    public function __construct(
        private Container $container,
        private ConfigService $configService,
        private ResponseFactory $responseFactory,
        private UrlBuilder $urlBuilder,
    ) {}

    /**
     * Emit the response to the client.
     *
     * @param Response $response The response to emit
     */
    public function emit(Response $response): void
    {
        // Emit status line
        header(sprintf(
            'HTTP/%s %s %s',
            $response->getProtocolVersion(),
            $response->getStatusCode(),
            $response->getReasonPhrase()
        ), true, $response->getStatusCode());

        // Emit headers
        foreach ($response->getHeaders() as $name => $values)
        {
            foreach ($values as $value)
            {
                header(sprintf('%s: %s', $name, $value), false);
            }
        }

        // Emit body
        echo $response->getBody();

        exit();
    }

    /**
     * Generate a blacklisted response.
     *
     * @param Request $request The current request
     *
     * @return Response The blacklisted response
     */
    public function blacklisted(Request $request): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');
        $isJson = (str_contains($contentType, 'application/json'));

        $response = $this->responseFactory->createResponse(403, 'Forbidden');

        if ($isJson)
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

    /**
     * Generate an error response.
     *
     * @param Request $request      The current request
     * @param string  $title        The error title
     * @param string  $details      The error details
     * @param int     $httpCode     The HTTP status code
     * @param string  $reasonPhrase The reason phrase for the status code
     *
     * @return Response The error response
     */
    public function error(Request $request, string $title, string $details = '', int $httpCode = 500, string $reasonPhrase = 'Internal error'): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');
        $isJson = (str_contains($contentType, 'application/json'));

        $response = $this->responseFactory->createResponse($httpCode, $reasonPhrase);

        if ($isJson)
        {
            $response = $response->withHeader('Content-type', 'application/json');

            $data = json_encode([
                'success' => false,
                'title' => $title,
                'details' => $details,
            ]);

            $response->getBody()->write($data ?: '');

            return $response;
        }

        $class = $this->configService->get('error_handlers.500');

        $errorHandler = $this->container->get($class);

        return $errorHandler($request, $response);
    }

    /**
     * Generate a forbidden response.
     *
     * @param Request $request The current request
     *
     * @return Response The forbidden response
     */
    public function forbidden(Request $request): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');
        $isJson = (str_contains($contentType, 'application/json'));

        $response = $this->responseFactory->createResponse(403, 'Forbidden');

        if ($isJson)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $class = $this->configService->get('error_handlers.403');

        $errorHandler = $this->container->get($class);

        return $errorHandler($request, $response);
    }

    /**
     * Generate a not found response.
     *
     * @param Request $request The current request
     *
     * @return Response The not found response
     */
    public function notFound(Request $request): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');
        $isJson = (str_contains($contentType, 'application/json'));

        $response = $this->responseFactory->createResponse(404, 'Not Found');

        if ($isJson)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        $class = $this->configService->get('error_handlers.404');

        $errorHandler = $this->container->get($class);

        return $errorHandler($request, $response);
    }

    /**
     * Generate a redirect response.
     *
     * @param string $url          The URL to redirect to
     * @param int    $redirectType The HTTP redirect status code
     *
     * @return Response The redirect response
     */
    public function redirect(string $url, int $redirectType = 302): Response
    {
        $response = $this->responseFactory->createResponse($redirectType);

        return $response->withHeader('Location', $url);
    }

    /**
     * Build and generate a redirect response using a URL template.
     *
     * @param string                    $template     The URL template
     * @param array<string, int|string> $values       The values to fill in the template
     * @param null|string               $messageType  The type of message to include in the URL
     * @param null|string               $message      The message to include in the URL
     * @param null|string               $extraMessage An extra message to include in the URL
     *
     * @return Response The redirect response
     */
    public function buildRedirect(string $template, array $values = [], ?string $messageType = null, ?string $message = null, ?string $extraMessage = null): Response
    {
        $url = $this->urlBuilder->buildUrl($template, $values, $messageType, $message, $extraMessage);

        return $this->redirect($url);
    }

    /**
     * Build and generate a redirect response using a URL template and query parameters.
     *
     * @param string                                              $template        The URL template
     * @param array<string, int|string>                           $values          The values to fill in the template
     * @param array<int|string, array<int|string, string>|string> $queryParameters The query parameters to include in the URL
     * @param null|string                                         $messageType     The type of message to include in the URL
     * @param null|string                                         $message         The message to include in the URL
     * @param null|string                                         $extraMessage    An extra message to include in the URL
     *
     * @return Response The redirect response
     */
    public function buildQueryRedirect(string $template, array $values = [], array $queryParameters = [], ?string $messageType = null, ?string $message = null, ?string $extraMessage = null): Response
    {
        $url = $this->urlBuilder->buildQueryUrl($template, $values, $queryParameters, $messageType, $message, $extraMessage);

        return $this->redirect($url);
    }

    /**
     * Generate an unauthorized response.
     *
     * @param Request $request The current request
     *
     * @return Response The unauthorized response
     */
    public function unauthorized(Request $request): Response
    {
        $contentType = $request->getHeaderLine('Content-Type');
        $isJson = (str_contains($contentType, 'application/json'));

        $response = $this->responseFactory->createResponse(401, 'Unauthorized');

        if ($isJson)
        {
            return $response->withHeader('Content-type', 'application/json');
        }

        return $this->buildQueryRedirect(
            $this->configService->get('actions.login.location'),
            [],
            [
                'return_page' => $request->getUri()->getPath(),
                'return_query' => $request->getUri()->getQuery(),
            ],
            'info',
            'authenticator.auth_required_message',
        );
    }
}
