<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Entity\User;
use WebFramework\Security\DefaultAccountStatusChecker;
use WebFramework\Security\NullAccountStatusChecker;

/**
 * @internal
 *
 * @covers \WebFramework\Security\DefaultAccountStatusChecker
 * @covers \WebFramework\Security\NullAccountStatusChecker
 */
final class AccountStatusCheckerTest extends Unit
{
    public function testDefaultCheckerTreatsEmptyPasswordHashAsDisabled()
    {
        $user = $this->makeEmpty(User::class, ['getSolidPassword' => '']);

        verify((new DefaultAccountStatusChecker())->isEnabled($user))->false();
    }

    public function testDefaultCheckerTreatsUserWithPasswordHashAsEnabled()
    {
        $user = $this->makeEmpty(User::class, ['getSolidPassword' => 'sha256:1000:salt:hash']);

        verify((new DefaultAccountStatusChecker())->isEnabled($user))->true();
    }

    public function testNullCheckerAlwaysEnabled()
    {
        $user = $this->makeEmpty(User::class, ['getSolidPassword' => '']);

        verify((new NullAccountStatusChecker())->isEnabled($user))->true();
    }
}
