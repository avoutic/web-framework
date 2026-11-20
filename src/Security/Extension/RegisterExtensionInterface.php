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
 * Interface for extending registration flow behavior.
 */
interface RegisterExtensionInterface
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

    /**
     * Get additional data to be passed after verification.
     *
     * The data is stored as JSON with the user until first verification, so it must be
     * JSON serializable.
     *
     * @param Request $request The current request
     *
     * @return array<mixed> Additional data
     */
    public function getAfterVerifyData(Request $request): array;

    /**
     * Called after user creation.
     *
     * @param Request $request The current request
     * @param User    $user    The user to post create
     */
    public function postCreate(Request $request, User $user): void;

    /**
     * Called exactly once per user, when the user is verified for the first time.
     *
     * The data from getAfterVerifyData() is stored with the user at registration and
     * passed here, whether the first verification happens through the registration flow
     * or through the login flow (the user never completed registration verification).
     *
     * It is not called for register codes redeemed by an already verified user, and it
     * is called before the login is completed, so also when the login is refused by
     * LoginExtensionInterface::preAuthenticate().
     *
     * @param User         $user              The user that was verified
     * @param array<mixed> $afterVerifyParams The data from getAfterVerifyData()
     */
    public function postVerify(User $user, array $afterVerifyParams): void;
}
