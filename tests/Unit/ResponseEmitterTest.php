<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ResponseFactoryInterface as ResponseFactory;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\StreamInterface as Stream;
use Slim\Psr7\Factory\ResponseFactory as SlimResponseFactory;
use Slim\Psr7\Factory\StreamFactory;
use WebFramework\Config\ConfigService;
use WebFramework\Http\ResponseEmitter;
use WebFramework\Support\UrlBuilder;

/**
 * @internal
 *
 * @covers \WebFramework\Http\ResponseEmitter
 */
final class ResponseEmitterTest extends Unit
{
    private function createMockRequest(string $contentType = 'text/html'): Request
    {
        $request = new Request('GET', 'https://test.com/test-path');

        if ($contentType)
        {
            $request = $request->withHeader('Content-Type', $contentType);
        }

        return $request;
    }

    private function createMockResponse(): Response
    {
        $responseFactory = new SlimResponseFactory();

        return $responseFactory->createResponse(200);
    }

    private function createMockStream(string $content = ''): Stream
    {
        $streamFactory = new StreamFactory();

        return $streamFactory->createStream($content);
    }

    public function testMethodNotAllowedWithMissingConfigKey()
    {
        $request = $this->createMockRequest('text/html');
        $response = $this->createMockResponse();
        $stream = $this->createMockStream();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'container' => $this->makeEmpty(Container::class),
                'configService' => $this->makeEmpty(
                    ConfigService::class,
                    [
                        'get' => Expected::once(null),
                    ]
                ),
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(405, 'Method Not Allowed')->withBody($stream)),
                    ]
                ),
                'urlBuilder' => $this->makeEmpty(UrlBuilder::class),
            ]
        );

        $result = $responseEmitter->methodNotAllowed($request);

        verify($result->getStatusCode())->equals(405);
    }

    public function testMethodNotAllowedJsonResponse()
    {
        $request = $this->createMockRequest('application/json');
        $response = $this->createMockResponse();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(405, 'Method Not Allowed')),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->methodNotAllowed($request);

        verify($result->getStatusCode())->equals(405);
        verify($result->getHeaderLine('Content-type'))->equals('application/json');
    }

    public function testBlacklistedJsonResponse()
    {
        $request = $this->createMockRequest('application/json');
        $response = $this->createMockResponse();
        $stream = $this->createMockStream();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(403, 'Forbidden')->withBody($stream)),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->blacklisted($request);

        verify($result->getStatusCode())->equals(403);
        verify($result->getHeaderLine('Content-type'))->equals('application/json');
    }

    public function testBlacklistedHtmlResponseWithoutCustomHandler()
    {
        $request = $this->createMockRequest('text/html');
        $response = $this->createMockResponse();
        $stream = $this->createMockStream();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'configService' => $this->makeEmpty(
                    ConfigService::class,
                    [
                        'get' => Expected::once(null),
                    ]
                ),
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(403, 'Forbidden')->withBody($stream)),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->blacklisted($request);
        verify($result->getStatusCode())->equals(403);
    }

    public function testForbiddenJsonResponse()
    {
        $request = $this->createMockRequest('application/json');
        $response = $this->createMockResponse();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(403, 'Forbidden')),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->forbidden($request);

        verify($result->getStatusCode())->equals(403);
        verify($result->getHeaderLine('Content-type'))->equals('application/json');
    }

    public function testNotFoundJsonResponse()
    {
        $request = $this->createMockRequest('application/json');
        $response = $this->createMockResponse();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(404, 'Not Found')),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->notFound($request);

        verify($result->getStatusCode())->equals(404);
        verify($result->getHeaderLine('Content-type'))->equals('application/json');
    }

    public function testErrorJsonResponse()
    {
        $request = $this->createMockRequest('application/json');
        $response = $this->createMockResponse();
        $stream = $this->createMockStream();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(500, 'Internal error')->withBody($stream)),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->error($request, 'Test Error', 'Error details');

        verify($result->getStatusCode())->equals(500);
        verify($result->getHeaderLine('Content-type'))->equals('application/json');
    }

    public function testErrorWithCustomHttpCode()
    {
        $request = $this->createMockRequest('text/html');
        $response = $this->createMockResponse();
        $stream = $this->createMockStream();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'configService' => $this->makeEmpty(
                    ConfigService::class,
                    [
                        'get' => Expected::once(null),
                    ]
                ),
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(422, 'Unprocessable Entity')->withBody($stream)),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->error($request, 'Validation Error', 'Invalid input', 422, 'Unprocessable Entity');
        verify($result->getStatusCode())->equals(422);
    }

    public function testRedirect()
    {
        $response = $this->createMockResponse();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(302)),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->redirect('https://example.com', 302);

        verify($result->getHeaderLine('Location'))->equals('https://example.com');
    }

    public function testRedirectWithCustomStatusCode()
    {
        $response = $this->createMockResponse();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(301)),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->redirect('https://example.com', 301);

        verify($result->getHeaderLine('Location'))->equals('https://example.com');
    }

    public function testBuildRedirect()
    {
        $response = $this->createMockResponse();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'urlBuilder' => $this->makeEmpty(
                    UrlBuilder::class,
                    [
                        'buildUrl' => Expected::once('https://built.example.com'),
                    ]
                ),
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(302)),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->buildRedirect('/template', ['key' => 'value'], 'info', 'Test message', 'Extra');
        verify($result->getHeaderLine('Location'))->equals('https://built.example.com');
    }

    public function testBuildQueryRedirect()
    {
        $response = $this->createMockResponse();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'urlBuilder' => $this->makeEmpty(
                    UrlBuilder::class,
                    [
                        'buildQueryUrl' => Expected::once('https://built.example.com?param=value'),
                    ]
                ),
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(302)),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->buildQueryRedirect(
            '/template',
            ['key' => 'value'],
            ['param' => 'value'],
            'info',
            'Test message',
            'Extra'
        );
        verify($result->getHeaderLine('Location'))->equals('https://built.example.com?param=value');
    }

    public function testUnauthorizedJsonResponse()
    {
        $request = $this->createMockRequest('application/json');
        $response = $this->createMockResponse();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(401, 'Unauthorized')),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->unauthorized($request);

        verify($result->getStatusCode())->equals(401);
        verify($result->getHeaderLine('Content-type'))->equals('application/json');
    }

    public function testUnauthorizedHtmlResponseRedirect()
    {
        $request = new Request('GET', 'https://test.com/protected-path?param=value');
        $request = $request->withHeader('Content-Type', 'text/html');

        $response = $this->createMockResponse();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'configService' => $this->makeEmpty(
                    ConfigService::class,
                    [
                        'get' => Expected::once('/login'),
                    ]
                ),
                'urlBuilder' => $this->makeEmpty(
                    UrlBuilder::class,
                    [
                        'buildQueryUrl' => Expected::once('https://example.com/login?return_page=/protected-path&return_query=param=value'),
                    ]
                ),
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::atLeastOnce($response->withStatus(302)),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->unauthorized($request);
        verify($result->getHeaderLine('Location'))->equals('https://example.com/login?return_page=/protected-path&return_query=param=value');
    }

    /**
     * @param array<string, string> $headers
     * @param array<string>         $jsonPaths
     */
    private function wantsJsonFor(array $headers, string $url = 'https://test.com/page', array $jsonPaths = []): bool
    {
        $request = new Request('GET', $url, $headers);

        $responseEmitter = new ResponseEmitter(
            $this->makeEmpty(Container::class),
            $this->makeEmpty(ConfigService::class),
            $this->makeEmpty(ResponseFactory::class),
            $this->makeEmpty(UrlBuilder::class),
            STDOUT,
            $jsonPaths,
        );

        return $responseEmitter->wantsJson($request);
    }

    public function testWantsJsonFollowsAcceptHeader()
    {
        verify($this->wantsJsonFor(['Accept' => 'application/json']))->true();
        verify($this->wantsJsonFor(['Accept' => 'application/json, text/plain, */*']))->true();
        verify($this->wantsJsonFor(['Accept' => 'application/problem+json']))->true();
        verify($this->wantsJsonFor(['Accept' => 'APPLICATION/JSON']))->true();
    }

    public function testWantsJsonIsFalseForBrowsersAndWildcards()
    {
        verify($this->wantsJsonFor(['Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8']))->false();
        verify($this->wantsJsonFor(['Accept' => '*/*']))->false();
        verify($this->wantsJsonFor([]))->false();
    }

    public function testWantsJsonRespectsPreferenceBetweenHtmlAndJson()
    {
        verify($this->wantsJsonFor(['Accept' => 'text/html, application/json;q=0.9']))->false();
        verify($this->wantsJsonFor(['Accept' => 'application/json, text/html;q=0.9']))->true();
        verify($this->wantsJsonFor(['Accept' => 'text/html, application/json']))->false();
        verify($this->wantsJsonFor(['Accept' => 'application/json, text/html']))->true();
        verify($this->wantsJsonFor(['Accept' => 'application/json;q=0, text/html']))->false();
    }

    public function testWantsJsonFallsBackToContentType()
    {
        verify($this->wantsJsonFor(['Content-Type' => 'application/json']))->true();
        verify($this->wantsJsonFor(['Content-Type' => 'application/json', 'Accept' => '*/*']))->true();
        verify($this->wantsJsonFor(['Content-Type' => 'text/html']))->false();
    }

    public function testWantsJsonForConfiguredPaths()
    {
        $browser = ['Accept' => 'text/html,application/xhtml+xml'];

        verify($this->wantsJsonFor($browser, 'https://test.com/api/users', ['/api/']))->true();
        verify($this->wantsJsonFor([], 'https://test.com/api/users', ['/api/']))->true();
        verify($this->wantsJsonFor([], 'https://test.com/apiary', ['/api/']))->false();
        verify($this->wantsJsonFor([], 'https://test.com/page', ['/api/', '/hooks/']))->false();
        verify($this->wantsJsonFor([], 'https://test.com/hooks/stripe', ['/api/', '/hooks/']))->true();
    }

    public function testUnauthorizedIsJsonForGetWithAcceptHeader()
    {
        $request = new Request('GET', 'https://test.com/api/me', ['Accept' => 'application/json']);
        $response = $this->createMockResponse();

        $responseEmitter = $this->make(
            ResponseEmitter::class,
            [
                'responseFactory' => $this->makeEmpty(
                    ResponseFactory::class,
                    [
                        'createResponse' => Expected::once($response->withStatus(401, 'Unauthorized')),
                    ]
                ),
                'urlBuilder' => $this->makeEmpty(
                    UrlBuilder::class,
                    [
                        'buildQueryUrl' => Expected::never(),
                    ]
                ),
            ]
        );

        $result = $responseEmitter->unauthorized($request);

        verify($result->getStatusCode())->equals(401);
        verify($result->getHeaderLine('Content-type'))->equals('application/json');
    }
}
