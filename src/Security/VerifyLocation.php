<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Security;

use WebFramework\Config\ConfigService;

/**
 * Class VerifyLocation.
 *
 * Determines the location of the page where a verification code is entered. Each kind of
 * verification (login, register, reset_password and change_email) can have its own location.
 * Without one, the shared location is used.
 */
class VerifyLocation
{
    /**
     * VerifyLocation constructor.
     *
     * @param ConfigService $configService The configuration service
     */
    public function __construct(
        private ConfigService $configService,
    ) {}

    /**
     * Get the location of the verify page for an action.
     *
     * @param string $action The action of the verification code (login, register, reset_password or change_email)
     *
     * @return string The location of the verify page
     *
     * @uses config actions.verify.location
     * @uses config actions.verify.locations
     */
    public function getFor(string $action): string
    {
        $locations = $this->configService->get('actions.verify.locations');

        if (is_array($locations) && isset($locations[$action]) && is_string($locations[$action]) && $locations[$action] !== '')
        {
            return $locations[$action];
        }

        return $this->configService->get('actions.verify.location');
    }
}
