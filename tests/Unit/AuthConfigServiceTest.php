<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WebFramework\Core\RuntimeEnvironment;
use WebFramework\Exception\MissingAuthConfigException;
use WebFramework\SanityCheck\RequiredAuth;
use WebFramework\Security\ConfigService;

/**
 * @internal
 *
 * @covers \WebFramework\SanityCheck\RequiredAuth
 * @covers \WebFramework\Security\ConfigService
 */
final class AuthConfigServiceTest extends Unit
{
    private string $appDir;

    protected function _before(): void
    {
        $this->appDir = codecept_output_dir().'auth_config_app';

        foreach (['/secrets', '/config/auth'] as $dir)
        {
            if (!is_dir($this->appDir.$dir))
            {
                mkdir($this->appDir.$dir, 0o777, true);
            }
        }

        file_put_contents($this->appDir.'/secrets/redis.php', "<?php return ['hostname' => 'localhost'];");
        file_put_contents($this->appDir.'/secrets/postmark.php', "<?php return 'api-key';");
        file_put_contents($this->appDir.'/secrets/empty.php', "<?php return '';");
        file_put_contents($this->appDir.'/secrets/hostile.php', "<?php \$name = 'other'; \$filename = '/nowhere'; return ['key' => 'value'];");
    }

    protected function _after(): void
    {
        foreach ((array) glob($this->appDir.'/secrets/*.php') as $file)
        {
            unlink((string) $file);
        }

        rmdir($this->appDir.'/secrets');
        rmdir($this->appDir.'/config/auth');
        rmdir($this->appDir.'/config');
        rmdir($this->appDir);
    }

    private function buildService(): ConfigService
    {
        return new ConfigService(
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(RuntimeEnvironment::class, ['getAppDir' => $this->appDir]),
            '/secrets',
        );
    }

    public function testGetAuthConfig()
    {
        $service = $this->buildService();

        verify($service->getAuthConfig('redis'))->same(['hostname' => 'localhost']);
        verify($service->getAuthConfig('postmark'))->same('api-key');
    }

    public function testHasAuthConfig()
    {
        $service = $this->buildService();

        verify($service->hasAuthConfig('redis'))->true();
        verify($service->hasAuthConfig('slack'))->false();
    }

    public function testMissingFileThrowsATypedException()
    {
        $service = $this->buildService();

        verify(function () use ($service) {
            $service->getAuthConfig('slack');
        })->callableThrows(MissingAuthConfigException::class, 'Auth Config slack does not exist');

        // Still what existing code catches
        verify(new MissingAuthConfigException('slack'))->instanceOf(\RuntimeException::class);
    }

    public function testGetAuthConfigOrDefault()
    {
        $service = $this->buildService();

        verify($service->getAuthConfigOrDefault('redis', []))->same(['hostname' => 'localhost']);
        verify($service->getAuthConfigOrDefault('slack', null))->null();
        verify($service->getAuthConfigOrDefault('slack', ['webhook' => '']))->same(['webhook' => '']);
    }

    public function testDefaultIsOnlyForAMissingFileNotForAnInvalidOne()
    {
        $service = $this->buildService();

        verify(function () use ($service) {
            $service->getAuthConfigOrDefault('empty', 'fallback');
        })->callableThrows(\RuntimeException::class, 'Auth Config empty invalid');
    }

    public function testAuthFileCannotChangeTheVariablesOfTheLoader()
    {
        verify($this->buildService()->getAuthConfig('hostile'))->same(['key' => 'value']);
    }

    public function testRequiredAuthLooksInTheConfiguredDirectory()
    {
        $check = new RequiredAuth(
            $this->makeEmpty(LoggerInterface::class),
            $this->makeEmpty(RuntimeEnvironment::class, ['getAppDir' => $this->appDir]),
            '/secrets',
        );

        // The files are in /secrets, and /config/auth is empty
        $check->setConfig(['redis.php', 'postmark.php']);
        verify($check->performChecks())->true();

        $check->setConfig(['redis.php', 'slack.php']);
        verify($check->performChecks())->false();
    }
}
