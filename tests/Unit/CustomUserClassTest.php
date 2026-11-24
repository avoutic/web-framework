<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Psr\Container\ContainerInterface as Container;
use Tests\Support\TestMember;
use Tests\Support\TestMemberRepository;
use WebFramework\Database\Database;
use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;

/**
 * @internal
 *
 * @covers \WebFramework\Repository\UserRepository
 */
final class CustomUserClassTest extends Unit
{
    public function testRepositorySubclassGivesOutTheUserClassOfTheApplication()
    {
        $repository = new TestMemberRepository($this->makeEmpty(Container::class), $this->makeEmpty(Database::class));

        // It is what the framework asks for, so it can be bound in its place
        verify($repository)->instanceOf(UserRepository::class);

        $user = $repository->instantiateEntityFromData([
            'id' => '5',
            'username' => 'john',
            'email' => 'john@example.com',
            'solid_password' => 'hash',
            'terms_accepted' => '1',
            'registered' => '100',
            'verified' => '1',
            'verified_at' => '200',
            'last_login' => '300',
            'failed_login' => '0',
            'display_name' => 'John D.',
        ]);

        verify($user)->instanceOf(TestMember::class);
        verify($user)->instanceOf(User::class);
        verify($user->getUsername())->equals('john');
        verify($user->getDisplayName())->equals('John D.');
        verify($user->isVerified())->true();
    }
}
