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

/**
 * Null implementation of ResetPasswordExtensionInterface.
 *
 * This is the default implementation that performs no custom operations.
 */
class NullResetPasswordExtension implements ResetPasswordExtensionInterface
{
    public function getCustomParams(Request $request): array
    {
        return [];
    }

    public function customValueCheck(Request $request): bool
    {
        return true;
    }
}
