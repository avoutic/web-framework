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
 * Interface for extending login flow behavior.
 */
interface LoginExtensionInterface
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
     * Perform custom validation before password validation.
     * This is useful for example to check if a CAPTCHA is required/valid.
     *
     * @param Request $request The current request
     *
     * @return bool True if the validation passes, false otherwise
     */
    public function preValidate(Request $request): bool;

    /**
     * Perform custom value checks before allowing authentication.
     *
     * @param Request $request The current request
     * @param User    $user    The user to check
     *
     * @return bool True if the checks pass, false otherwise
     */
    public function customValueCheck(Request $request, User $user): bool;

    /**
     * Called right before a session is created for the user, on every login path
     * (password login, login verification, registration verification).
     *
     * @param Request $request The current request
     * @param User    $user    The user about to be logged in
     *
     * @return bool True to allow the login, false to refuse it
     */
    public function preAuthenticate(Request $request, User $user): bool;

    /**
     * Decide if a user is exempt from verifying again when the verification has expired.
     *
     * This is asked at login, for a user whose last verification is older than the validity
     * period. Use it for accounts that cannot verify again, such as a service account without
     * a mailbox. It does not apply to a user that was never verified.
     *
     * @param Request $request The current request
     * @param User    $user    The user whose verification has expired
     *
     * @return bool True to let the user log in without verifying again
     */
    public function isExemptFromVerificationExpiry(Request $request, User $user): bool;
}
