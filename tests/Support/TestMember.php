<?php

namespace Tests\Support;

use WebFramework\Entity\User;

/**
 * A user class of an application: the user of the framework with a field of its own.
 */
class TestMember extends User
{
    protected static array $baseFields = ['display_name'];

    protected string $displayName = '';

    public function getDisplayName(): string
    {
        return $this->displayName;
    }
}
