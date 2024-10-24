<?php

namespace WebFramework\Validation;

/**
 * Class UsernameValidator.
 *
 * This class extends CustomValidator to provide username validation functionality.
 */
class UsernameValidator extends CustomValidator
{
    /**
     * UsernameValidator constructor.
     *
     * @param string $name The name of the field to validate (default: 'username')
     */
    public function __construct(
        string $name = 'username',
    ) {
        parent::__construct($name);

        $this->filter(FORMAT_USERNAME)->maxLength(255);
    }
}
