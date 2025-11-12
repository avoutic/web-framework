<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Entity\User;
use WebFramework\Repository\UserRepository;
use WebFramework\Security\CheckPasswordService;
use WebFramework\Security\PasswordHashService;

/**
 * @internal
 *
 * @covers \WebFramework\Security\CheckPasswordService
 */
final class CheckPasswordServiceTest extends Unit
{
    public function testCheckPasswordWithCorrectPassword()
    {
        $user = $this->makeEmpty(User::class, [
            'getSolidPassword' => Expected::once('sha256:1000:salt123:hash123'),
            'setSolidPassword' => Expected::never(),
            'setLastLogin' => Expected::once(),
            'incrementFailedLogin' => Expected::never(),
        ]);

        $passwordHashService = $this->makeEmpty(PasswordHashService::class, [
            'checkPassword' => Expected::once(true),
            'shouldMigrate' => Expected::once(false),
        ]);

        $userRepository = $this->makeEmpty(UserRepository::class, [
            'save' => Expected::once(),
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class);

        $service = new CheckPasswordService($logger, $passwordHashService, $userRepository);

        verify($service->checkPassword($user, 'password123'))->true();
    }

    public function testCheckPasswordWithIncorrectPassword()
    {
        $user = $this->makeEmpty(User::class, [
            'getSolidPassword' => Expected::once('sha256:1000:salt123:hash123'),
            'incrementFailedLogin' => Expected::once(),
        ]);

        $passwordHashService = $this->makeEmpty(PasswordHashService::class, [
            'checkPassword' => Expected::once(false),
        ]);

        $userRepository = $this->makeEmpty(UserRepository::class, [
            'save' => Expected::once(),
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class);

        $service = new CheckPasswordService($logger, $passwordHashService, $userRepository);

        verify($service->checkPassword($user, 'wrongpassword'))->false();
    }

    public function testCheckPasswordWithPasswordMigration()
    {
        $newHash = 'sha256:1000:newsalt:newhash';
        $user = $this->makeEmpty(User::class, [
            'getSolidPassword' => Expected::once('bootstrap:oldhash'),
            'setSolidPassword' => Expected::once(function ($generatedHash) use ($newHash) {
                verify($generatedHash)->equals($newHash);
            }),
            'setLastLogin' => Expected::once(),
        ]);

        $passwordHashService = $this->makeEmpty(PasswordHashService::class, [
            'checkPassword' => Expected::once(true),
            'shouldMigrate' => Expected::once(true),
            'generateHash' => Expected::once($newHash),
        ]);

        $userRepository = $this->makeEmpty(UserRepository::class, [
            'save' => Expected::once(),
        ]);

        $logger = $this->makeEmpty(LoggerInterface::class);

        $service = new CheckPasswordService($logger, $passwordHashService, $userRepository);

        verify($service->checkPassword($user, 'password123'))->true();
    }
}
