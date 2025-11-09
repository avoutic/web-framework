<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security\Extension;

use Slim\Http\ServerRequest as Request;
use WebFramework\Entity\User;

/**
 * Null implementation of RegisterExtensionInterface.
 *
 * This is the default implementation that performs no custom operations.
 */
class NullRegisterExtension implements RegisterExtensionInterface
{
    public function getAfterVerifyData(Request $request): array
    {
        return [];
    }

    public function getCustomParams(Request $request): array
    {
        return [];
    }

    public function customValueCheck(Request $request): bool
    {
        return true;
    }

    public function postCreate(Request $request, User $user): void
    {
        // No-op
    }

    public function postVerify(User $user, array $afterVerifyParams): void
    {
        // No-op
    }
}
