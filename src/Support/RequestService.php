<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Support;

use Psr\Http\Message\ServerRequestInterface as Request;
use WebFramework\Entity\User;

/**
 * RequestService can be used to access request data in a non-middleware context.
 *
 * Requires you to inject the RequestServiceMiddleware in your application.
 *
 * Requires the IpMiddleware, AuthenticationMiddleware, and CsrfValidationMiddleware to run first.
 */
class RequestService
{
    private Request $request;
    private string $remoteIp;
    private bool $csrfPassed;
    private bool $isAuthenticated;
    private ?User $authenticatedUser;

    public function __construct(
    ) {}

    public function setRequest(Request $request): void
    {
        $this->request = $request;

        $this->remoteIp = $request->getAttribute('ip');
        $this->csrfPassed = $request->getAttribute('passed_csrf');
        $this->isAuthenticated = $request->getAttribute('is_authenticated');
        $this->authenticatedUser = $request->getAttribute('authenticated_user');
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getRemoteIp(): string
    {
        return $this->remoteIp;
    }

    public function csrfPassed(): bool
    {
        return $this->csrfPassed;
    }

    public function isAuthenticated(): bool
    {
        return $this->isAuthenticated;
    }

    public function getAuthenticatedUser(): User
    {
        if ($this->authenticatedUser === null)
        {
            throw new \RuntimeException('User is not authenticated');
        }

        return $this->authenticatedUser;
    }
}
