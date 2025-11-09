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
 * Interface for extending reset password flow behavior.
 */
interface ResetPasswordExtensionInterface
{
    /**
     * Get custom parameters for the template.
     *
     * @param Request $request The current request
     *
     * @return array<string, mixed> Custom parameters
     */
    public function getCustomParams(Request $request): array;

    /**
     * Perform custom value checks.
     *
     * @param Request $request The current request
     *
     * @return bool True if the checks pass, false otherwise
     */
    public function customValueCheck(Request $request): bool;
}
