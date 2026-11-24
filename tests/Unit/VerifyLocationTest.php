<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Config\ConfigService;
use WebFramework\Security\VerifyLocation;

/**
 * @internal
 *
 * @covers \WebFramework\Security\VerifyLocation
 */
final class VerifyLocationTest extends Unit
{
    /**
     * @param array<string, null|string> $locations
     */
    private function build(array $locations): VerifyLocation
    {
        return new VerifyLocation($this->makeEmpty(ConfigService::class, [
            'get' => fn (string $key) => match ($key)
            {
                'actions.verify.location' => '/verify',
                'actions.verify.locations' => $locations,
            },
        ]));
    }

    public function testFallsBackToTheSharedLocation()
    {
        $verifyLocation = $this->build(['login' => null, 'register' => null, 'reset_password' => null, 'change_email' => null]);

        verify($verifyLocation->getFor('register'))->equals('/verify');
        verify($verifyLocation->getFor('reset_password'))->equals('/verify');
    }

    public function testUsesTheLocationOfTheAction()
    {
        $verifyLocation = $this->build(['login' => null, 'register' => '/getting-started', 'reset_password' => null, 'change_email' => null]);

        verify($verifyLocation->getFor('register'))->equals('/getting-started');
        verify($verifyLocation->getFor('login'))->equals('/verify');
        verify($verifyLocation->getFor('change_email'))->equals('/verify');
    }

    public function testEmptyAndUnknownActionsUseTheSharedLocation()
    {
        $verifyLocation = $this->build(['register' => '']);

        verify($verifyLocation->getFor('register'))->equals('/verify');
        verify($verifyLocation->getFor('something_else'))->equals('/verify');
    }

    public function testDefaultConfigurationHasAllActionsUnset()
    {
        $config = require __DIR__.'/../../config/base_config.php';

        verify($config['actions']['verify']['locations'])->same([
            'login' => null,
            'register' => null,
            'reset_password' => null,
            'change_email' => null,
        ]);
    }
}
