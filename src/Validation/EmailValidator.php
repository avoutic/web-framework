<?php

namespace WebFramework\Validation;

/**
 * Class EmailValidator.
 *
 * This class extends CustomValidator to provide email validation functionality.
 */
class EmailValidator extends CustomValidator
{
    /**
     * EmailValidator constructor.
     *
     * @param string $name The name of the field to validate (default: 'email')
     */
    public function __construct(
        string $name = 'email',
    ) {
        parent::__construct($name);

        $this->filter(FORMAT_EMAIL)->maxLength(255);
    }
}
