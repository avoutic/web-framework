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
 * Interface for extending change email flow behavior.
 */
interface ChangeEmailExtensionInterface
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
     * @param User    $user    The user to check
     *
     * @return bool True if the checks pass, false otherwise
     */
    public function customValueCheck(Request $request, User $user): bool;
}
