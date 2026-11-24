<?php

namespace Tests\Unit\Middleware;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use GuzzleHttp\Psr7\ServerRequest as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Middleware\IpMiddleware;

/**
 * @internal
 *
 * @covers \WebFramework\Middleware\IpMiddleware
 */
final class IpMiddlewareTest extends Unit
{
    public function testIpMiddleware()
    {
        $middleware = $this->make(
            IpMiddleware::class,
        );

        $request = $this->construct(
            Request::class,
            [
                'POST',
                'http://example.com',
                ['Content-Type' => 'application/json'],
                '{"name": "John", "age": 30}',
                '1.1',
                [
                    'REMOTE_ADDR' => '127.0.0.1',
                ],
            ]
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('ip'))->equals('127.0.0.1');

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    public function testIpMiddlewareWithApp()
    {
        $middleware = $this->make(
            IpMiddleware::class,
        );

        $request = $this->construct(
            Request::class,
            [
                'POST',
                'http://example.com',
            ]
        );

        $handler = $this->makeEmpty(
            RequestHandlerInterface::class,
            [
                'handle' => Expected::once(function (Request $request) {
                    verify($request->getAttribute('ip'))->equals('app');

                    return $this->makeEmpty(Response::class);
                }),
            ]
        );

        $middleware->process($request, $handler);
    }

    /**
     * @param array<string>         $trustedProxies
     * @param array<string, string> $headers
     */
    private function resolveIp(array $trustedProxies, string $remoteAddr, array $headers = []): string
    {
        $middleware = new IpMiddleware($trustedProxies);
        $request = new Request('GET', 'http://example.com', $headers, null, '1.1', ['REMOTE_ADDR' => $remoteAddr]);

        $ip = null;

        $handler = new class($this->makeEmpty(Response::class), $ip) implements RequestHandlerInterface {
            public function __construct(private Response $response, private mixed &$ip) {}

            public function handle(ServerRequestInterface $request): Response
            {
                $this->ip = $request->getAttribute('ip');

                return $this->response;
            }
        };

        $middleware->process($request, $handler);

        return $ip;
    }

    public function testForwardedHeaderIsIgnoredWithoutTrustedProxies()
    {
        verify($this->resolveIp([], '203.0.113.7', ['X-Forwarded-For' => '198.51.100.1']))->equals('203.0.113.7');
    }

    public function testForwardedHeaderIsIgnoredWhenTheRequestDoesNotComeFromATrustedProxy()
    {
        // A visitor that sends the header itself, to pose as someone else
        verify($this->resolveIp(['10.0.0.1'], '203.0.113.7', ['X-Forwarded-For' => '198.51.100.1']))->equals('203.0.113.7');
    }

    public function testClientAddressIsTakenFromATrustedProxy()
    {
        verify($this->resolveIp(['10.0.0.1'], '10.0.0.1', ['X-Forwarded-For' => '198.51.100.1']))->equals('198.51.100.1');
        verify($this->resolveIp(['172.16.0.0/12'], '172.18.0.3', ['X-Forwarded-For' => '198.51.100.1']))->equals('198.51.100.1');
    }

    public function testRightMostAddressThatIsNotAProxyIsTheClient()
    {
        // The left-most address is whatever the visitor sent, the proxies add to the right
        verify($this->resolveIp(
            ['10.0.0.0/8'],
            '10.0.0.1',
            ['X-Forwarded-For' => '1.2.3.4, 198.51.100.1, 10.0.0.2'],
        ))->equals('198.51.100.1');
    }

    public function testFallsBackToTheProxyAddressWithoutAUsableHeader()
    {
        verify($this->resolveIp(['10.0.0.1'], '10.0.0.1'))->equals('10.0.0.1');
        verify($this->resolveIp(['10.0.0.1'], '10.0.0.1', ['X-Forwarded-For' => 'not an address']))->equals('10.0.0.1');
        verify($this->resolveIp(['10.0.0.0/8'], '10.0.0.1', ['X-Forwarded-For' => '10.0.0.5, 10.0.0.2']))->equals('10.0.0.5');
    }

    public function testInvalidEntryStopsTheWalkThroughTheHeader()
    {
        // Everything to the left of something that is not an address is not trusted either
        verify($this->resolveIp(
            ['10.0.0.0/8'],
            '10.0.0.1',
            ['X-Forwarded-For' => '198.51.100.1, garbage, 10.0.0.2'],
        ))->equals('10.0.0.2');
    }

    public function testIpv6ProxiesAndClients()
    {
        verify($this->resolveIp(['fd00::/8'], 'fd00::1', ['X-Forwarded-For' => '2001:db8::7']))->equals('2001:db8::7');
        verify($this->resolveIp(['::1'], '::1', ['X-Forwarded-For' => '198.51.100.1']))->equals('198.51.100.1');
        verify($this->resolveIp(['fd00::/8'], '2001:db8::1', ['X-Forwarded-For' => '198.51.100.1']))->equals('2001:db8::1');
    }
}
