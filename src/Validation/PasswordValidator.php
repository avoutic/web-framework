<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Validation;

/**
 * Class PasswordValidator.
 *
 * This class extends CustomValidator to provide password validation functionality.
 */
class PasswordValidator extends CustomValidator
{
    /**
     * PasswordValidator constructor.
     *
     * @param string $name The name of the field to validate (default: 'password')
     */
    public function __construct(
        string $name = 'password',
    ) {
        parent::__construct($name);

        $this->required();
    }
}
