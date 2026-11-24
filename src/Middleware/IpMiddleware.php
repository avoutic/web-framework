<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to determine the client IP address and add it to the request attributes.
 *
 * Adds the 'ip' attribute to the request. This is the address the request came from, or,
 * when that is a trusted proxy, the address of the client according to that proxy.
 */
class IpMiddleware implements MiddlewareInterface
{
    /**
     * IpMiddleware constructor.
     *
     * @param array<string> $trustedProxies The addresses and CIDR ranges of the proxies that are
     *                                      allowed to tell the address of the client
     */
    public function __construct(array $trustedProxies = [])
    {
        $this->trustedProxies = $trustedProxies;
    }

    /** @var array<string> */
    private array $trustedProxies = [];

    /**
     * Process an incoming server request.
     *
     * @param Request                 $request The request
     * @param RequestHandlerInterface $handler The handler
     */
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $serverParams = $request->getServerParams();

        $ip = (isset($serverParams['REMOTE_ADDR'])) ? $serverParams['REMOTE_ADDR'] : 'app';

        // Only a trusted proxy is believed when it says who the client is. Anyone can
        // send the header, so from any other address it means nothing
        //
        if ($this->isTrustedProxy($ip))
        {
            $ip = $this->getClientIp($request->getHeaderLine('X-Forwarded-For'), $ip);
        }

        $request = $request->withAttribute('ip', $ip);

        return $handler->handle($request);
    }

    /**
     * Get the address of the client from a X-Forwarded-For header.
     *
     * Every proxy adds the address it got the request from to the right. So walk from
     * right to left, and the first address that is not a trusted proxy is the client.
     * Whatever is further to the left was sent by that client, and is not trusted.
     *
     * @param string $forwardedFor The value of the header
     * @param string $proxyIp      The address of the proxy the request came from
     */
    private function getClientIp(string $forwardedFor, string $proxyIp): string
    {
        $clientIp = $proxyIp;

        foreach (array_reverse(explode(',', $forwardedFor)) as $address)
        {
            $address = trim($address);

            if (filter_var($address, FILTER_VALIDATE_IP) === false)
            {
                break;
            }

            $clientIp = $address;

            if (!$this->isTrustedProxy($address))
            {
                break;
            }
        }

        return $clientIp;
    }

    /**
     * Check if an address is one of the trusted proxies.
     */
    private function isTrustedProxy(string $ip): bool
    {
        $packedIp = @inet_pton($ip);

        if ($packedIp === false)
        {
            return false;
        }

        foreach ($this->trustedProxies as $proxy)
        {
            $parts = explode('/', $proxy, 2);
            $packedProxy = @inet_pton($parts[0]);

            // An IPv4 address is never in an IPv6 range, and the other way around
            //
            if ($packedProxy === false || strlen($packedProxy) !== strlen($packedIp))
            {
                continue;
            }

            $maxBits = strlen($packedProxy) * 8;
            $bits = isset($parts[1]) ? (int) $parts[1] : $maxBits;

            if (isset($parts[1]) && (!ctype_digit($parts[1]) || $bits > $maxBits))
            {
                continue;
            }

            if ($this->matchesPrefix($packedIp, $packedProxy, $bits))
            {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the first bits of two packed addresses are the same.
     */
    private function matchesPrefix(string $packedIp, string $packedRange, int $bits): bool
    {
        $fullBytes = intdiv($bits, 8);

        if (substr($packedIp, 0, $fullBytes) !== substr($packedRange, 0, $fullBytes))
        {
            return false;
        }

        $remainingBits = $bits % 8;

        if ($remainingBits === 0)
        {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;

        return (ord($packedIp[$fullBytes]) & $mask) === (ord($packedRange[$fullBytes]) & $mask);
    }
}
