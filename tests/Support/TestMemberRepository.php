<?php

namespace Tests\Support;

use WebFramework\Repository\UserRepository;

/**
 * The user repository of the framework, giving out the user class of the application.
 */
class TestMemberRepository extends UserRepository
{
    protected static string $entityClass = TestMember::class;
}
