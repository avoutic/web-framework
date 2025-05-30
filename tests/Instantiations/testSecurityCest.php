<?php

namespace Tests\Instantiations;

use Tests\Support\InstantiationTester;
use Tests\Support\TaskRunnerTrait;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\BlacklistService;
use WebFramework\Security\ChangeEmailService;
use WebFramework\Security\ChangePasswordService;
use WebFramework\Security\CheckPasswordService;
use WebFramework\Security\ConfigService as SecurityConfigService;
use WebFramework\Security\CsrfService;
use WebFramework\Security\DatabaseAuthenticationService;
use WebFramework\Security\DatabaseBlacklistService;
use WebFramework\Security\LoginService;
use WebFramework\Security\NullAuthenticationService;
use WebFramework\Security\NullBlacklistService;
use WebFramework\Security\OpensslRandomProvider;
use WebFramework\Security\PasswordHashService;
use WebFramework\Security\ProtectService;
use WebFramework\Security\RandomProvider;
use WebFramework\Security\RegisterService;
use WebFramework\Security\ResetPasswordService;
use WebFramework\Security\SecurityIteratorService;
use WebFramework\Security\UserCodeService;
use WebFramework\Security\UserRightService;
use WebFramework\Security\UserVerificationService;

class testSecurityCest
{
    use TaskRunnerTrait;

    private array $configFiles = [
        '/config/base_config.php',
        '?/config/config_local.php',
        '/tests/config_instantiations.php',
    ];

    private array $classes = [
        AuthenticationService::class,
        BlacklistService::class,
        ChangeEmailService::class,
        ChangePasswordService::class,
        CheckPasswordService::class,
        SecurityConfigService::class,
        CsrfService::class,
        DatabaseAuthenticationService::class,
        DatabaseBlacklistService::class,
        LoginService::class,
        NullAuthenticationService::class,
        NullBlacklistService::class,
        OpensslRandomProvider::class,
        PasswordHashService::class,
        ProtectService::class,
        RandomProvider::class,
        RegisterService::class,
        ResetPasswordService::class,
        SecurityIteratorService::class,
        UserCodeService::class,
        UserRightService::class,
        UserVerificationService::class,
    ];

    public function _before(InstantiationTester $I)
    {
        $this->instantiateTaskRunner($this->configFiles);
    }

    // tests
    public function instantiations(InstantiationTester $I)
    {
        foreach ($this->classes as $class)
        {
            $this->get($class);
        }
    }
}
