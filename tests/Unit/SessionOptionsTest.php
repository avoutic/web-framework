<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Security\SessionOptions;

/**
 * @internal
 *
 * @covers \WebFramework\Security\SessionOptions
 */
final class SessionOptionsTest extends Unit
{
    public function testBaseOptionsComeFromTheApplicationSettings()
    {
        $options = (new SessionOptions())->build('myapp', 900, 'example.com', 'https', []);

        verify($options)->same([
            'name' => 'myapp',
            'lifetime' => 900,
            'path' => '/',
            'domain' => 'example.com',
            'secure' => true,
            'httponly' => true,
        ]);
    }

    public function testCookieIsNotSecureOverHttp()
    {
        $options = (new SessionOptions())->build('myapp', 900, 'example.com', 'http', []);

        verify($options['secure'])->false();
    }

    public function testConfiguredOptionsAreAddedAndOverrideTheBase()
    {
        $options = (new SessionOptions())->build('myapp', 900, 'example.com', 'https', [
            'cookie_samesite' => 'Lax',
            'save_path' => '/var/lib/sessions',
            'path' => '/app',
        ]);

        verify($options['cookie_samesite'])->equals('Lax');
        verify($options['save_path'])->equals('/var/lib/sessions');
        verify($options['path'])->equals('/app');
        verify($options['name'])->equals('myapp');
    }

    public function testNullRemovesAnOptionAndEmptyStringIsKept()
    {
        $options = (new SessionOptions())->build('myapp', 900, 'example.com', 'https', [
            'cookie_samesite' => null,
            'save_path' => '',
        ]);

        verify($options)->arrayHasNotKey('cookie_samesite');
        verify($options['save_path'])->same('');
    }

    public function testDefaultConfigurationSetsSameSiteLax()
    {
        $config = require __DIR__.'/../../config/base_config.php';

        verify($config['session']['options'])->same(['cookie_samesite' => 'Lax']);
    }
}
