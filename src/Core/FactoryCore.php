<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Entity\User;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\BlacklistService;
use WebFramework\Security\ConfigService as SecureConfigService;
use WebFramework\Security\ProtectService;

class FactoryCore
{
    protected Cache $cache;
    protected Container $container;
    protected Database $database;
    protected AssertService $assertService;
    protected AuthenticationService $authenticationService;
    protected BlacklistService $blacklistService;
    protected ConfigService $configService;
    protected DebugService $debugService;
    protected MessageService $messageService;
    protected ProtectService $protectService;
    protected SecureConfigService $secureConfigService;
    protected UserRightService $userRightService;

    public function __construct()
    {
        $container = ContainerWrapper::get();
        $this->container = $container;
        $this->database = $container->get(Database::class);
        $this->cache = $container->get(Cache::class);
        $this->assertService = $container->get(AssertService::class);
        $this->authenticationService = $container->get(AuthenticationService::class);
        $this->blacklistService = $container->get(BlacklistService::class);
        $this->configService = $container->get(ConfigService::class);
        $this->debugService = $container->get(DebugService::class);
        $this->messageService = $container->get(MessageService::class);
        $this->protectService = $container->get(ProtectService::class);
        $this->secureConfigService = $container->get(SecureConfigService::class);
        $this->userRightService = $container->get(UserRightService::class);

        $this->init();
    }

    public function init(): void
    {
    }

    protected function getAppDir(): string
    {
        return $this->container->get('app_dir');
    }

    protected function getConfig(string $path): mixed
    {
        return $this->configService->get($path);
    }

    // Database related
    //
    protected function getDb(string $tag = ''): Database
    {
        if (strlen($tag))
        {
            throw new \InvalidArgumentException('No support for tags');
        }

        return $this->database;
    }

    /**
     * @param array<null|bool|float|int|string> $params
     */
    protected function query(string $query, array $params): mixed
    {
        return $this->database->query($query, $params);
    }

    /**
     * @param array<null|bool|float|int|string> $params
     */
    protected function insertQuery(string $query, array $params): false|int
    {
        return $this->database->insertQuery($query, $params);
    }

    protected function startTransaction(): void
    {
        $this->database->startTransaction();
    }

    protected function commitTransaction(): void
    {
        $this->database->commitTransaction();
    }

    // Message related
    //
    /**
     * @return array<array{mtype: string, message: string, extra_message: string}>
     */
    protected function getMessages(): array
    {
        return $this->messageService->getMessages();
    }

    protected function addMessage(string $mtype, string $message, string $extraMessage = ''): void
    {
        $this->messageService->add($mtype, $message, $extraMessage);
    }

    protected function getMessageForUrl(string $mtype, string $message, string $extraMessage = ''): string
    {
        return $this->messageService->getForUrl($mtype, $message, $extraMessage);
    }

    // Assert related
    //
    /**
     * @param array<mixed> $stack
     */
    protected function reportError(string $message, array $stack = null): void
    {
        if ($stack === null)
        {
            $stack = debug_backtrace(0);
        }

        $this->assertService->reportError($message, $stack);
    }

    protected function verify(bool|int $bool, string $message): void
    {
        $this->assertService->verify($bool, $message);
    }

    protected function blacklistVerify(bool|int $bool, string $reason, int $severity = 1): void
    {
        if ($bool)
        {
            return;
        }

        $user_id = null;

        if ($this->isAuthenticated())
        {
            $user_id = $this->getAuthenticated('user_id');
        }

        $this->blacklistService->addEntry($_SERVER['REMOTE_ADDR'], $user_id, $reason, $severity);
    }

    // Security related
    //

    protected function getAuthConfig(string $keyFile): mixed
    {
        return $this->secureConfigService->getAuthConfig($keyFile);
    }

    protected function addBlacklistEntry(string $reason, int $severity = 1): void
    {
        $user_id = null;

        if ($this->isAuthenticated())
        {
            $user_id = $this->getAuthenticated('user_id');
        }

        $this->blacklistService->addEntry($_SERVER['REMOTE_ADDR'], $user_id, $reason, $severity);
    }

    protected function encodeAndAuthString(string $value): string
    {
        return $this->protectService->packString($value);
    }

    /**
     * @param array<mixed> $array
     */
    protected function encodeAndAuthArray(array $array): string
    {
        return $this->protectService->packArray($array);
    }

    protected function decodeAndVerifyString(string $str): string|false
    {
        return $this->protectService->unpackString($str);
    }

    /**
     * @return array<mixed>|false
     */
    protected function decodeAndVerifyArray(string $str): array|false
    {
        return $this->protectService->unpackArray($str);
    }

    // Authentication related
    //
    protected function authenticate(User $user): void
    {
        $this->authenticationService->authenticate($user);
    }

    protected function deauthenticate(): void
    {
        $this->authenticationService->deauthenticate();
    }

    protected function invalidateSessions(int $userId): void
    {
        $this->authenticationService->invalidateSessions($userId);
    }

    protected function isAuthenticated(): bool
    {
        return $this->authenticationService->isAuthenticated();
    }

    protected function getAuthenticatedUser(): User
    {
        return $this->authenticationService->getAuthenticatedUser();
    }

    protected function getAuthenticated(string $type): mixed
    {
        $user = $this->getAuthenticatedUser();
        if ($type === 'user')
        {
            return $user;
        }

        if ($type === 'user_id')
        {
            return $user->getId();
        }

        throw new \RuntimeException('Cannot return requested value');
    }

    /**
     * @param array<string> $permissions
     */
    protected function userHasPermissions(array $permissions): bool
    {
        if (count($permissions) == 0)
        {
            return true;
        }

        if (!$this->isAuthenticated())
        {
            return false;
        }

        $user = $this->getAuthenticatedUser();

        foreach ($permissions as $permission)
        {
            if ($permission == 'logged_in')
            {
                continue;
            }

            if (!$this->userRightService->hasRight($user, $permission))
            {
                return false;
            }
        }

        return true;
    }

    // Build info
    //
    /**
     * @return array{commit: null|string, timestamp: string}
     */
    protected function getBuildInfo(): array
    {
        return $this->debugService->getBuildInfo();
    }
}
