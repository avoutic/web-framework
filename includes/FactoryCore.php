<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\BlacklistService;
use WebFramework\Security\ConfigService as SecureConfigService;
use WebFramework\Security\ProtectService;

class FactoryCore
{
    protected Cache $cache;
    protected Container $container;
    protected Database $database;
    protected AssertService $assert_service;
    protected AuthenticationService $authentication_service;
    protected BlacklistService $blacklist_service;
    protected ConfigService $config_service;
    protected DebugService $debug_service;
    protected MessageService $message_service;
    protected ProtectService $protect_service;
    protected SecureConfigService $secure_config_service;

    public function __construct()
    {
        $container = ContainerWrapper::get();
        $this->container = $container;
        $this->database = $container->get(Database::class);
        $this->cache = $container->get(Cache::class);
        $this->assert_service = $container->get(AssertService::class);
        $this->authentication_service = $container->get(AuthenticationService::class);
        $this->blacklist_service = $container->get(BlacklistService::class);
        $this->config_service = $container->get(ConfigService::class);
        $this->debug_service = $container->get(DebugService::class);
        $this->message_service = $container->get(MessageService::class);
        $this->protect_service = $container->get(ProtectService::class);
        $this->secure_config_service = $container->get(SecureConfigService::class);

        $this->init();
    }

    public function init(): void
    {
    }

    protected function get_app_dir(): string
    {
        return $this->container->get('app_dir');
    }

    protected function get_config(string $path): mixed
    {
        return $this->config_service->get($path);
    }

    // Database related
    //
    protected function get_db(string $tag = ''): Database
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
    protected function insert_query(string $query, array $params): false|int
    {
        return $this->database->insert_query($query, $params);
    }

    protected function start_transaction(): void
    {
        $this->database->start_transaction();
    }

    protected function commit_transaction(): void
    {
        $this->database->commit_transaction();
    }

    // Message related
    //
    /**
     * @return array<array{mtype: string, message: string, extra_message: string}>
     */
    protected function get_messages(): array
    {
        return $this->message_service->get_messages();
    }

    protected function add_message(string $mtype, string $message, string $extra_message = ''): void
    {
        $this->message_service->add($mtype, $message, $extra_message);
    }

    protected function get_message_for_url(string $mtype, string $message, string $extra_message = ''): string
    {
        return $this->message_service->get_for_url($mtype, $message, $extra_message);
    }

    // Assert related
    //
    /**
     * @param array<mixed> $stack
     */
    protected function report_error(string $message, array $stack = null): void
    {
        if ($stack === null)
        {
            $stack = debug_backtrace(0);
        }

        $this->assert_service->report_error($message, $stack);
    }

    protected function verify(bool|int $bool, string $message): void
    {
        $this->assert_service->verify($bool, $message);
    }

    protected function blacklist_verify(bool|int $bool, string $reason, int $severity = 1): void
    {
        if ($bool)
        {
            return;
        }

        $this->blacklist_service->add_entry($_SERVER['REMOTE_ADDR'], $this->get_authenticated('user_id'), $reason, $severity);
    }

    // Security related
    //

    protected function get_auth_config(string $key_file): mixed
    {
        return $this->secure_config_service->get_auth_config($key_file);
    }

    protected function add_blacklist_entry(string $reason, int $severity = 1): void
    {
        $this->blacklist_service->add_entry($_SERVER['REMOTE_ADDR'], $this->get_authenticated('user_id'), $reason, $severity);
    }

    protected function encode_and_auth_string(string $value): string
    {
        return $this->protect_service->pack_string($value);
    }

    /**
     * @param array<mixed> $array
     */
    protected function encode_and_auth_array(array $array): string
    {
        return $this->protect_service->pack_array($array);
    }

    protected function decode_and_verify_string(string $str): string|false
    {
        return $this->protect_service->unpack_string($str);
    }

    /**
     * @return array<mixed>|false
     */
    protected function decode_and_verify_array(string $str): array|false
    {
        return $this->protect_service->unpack_array($str);
    }

    // Authentication related
    //
    protected function authenticate(User $user): void
    {
        $this->authentication_service->authenticate($user);
    }

    protected function deauthenticate(): void
    {
        $this->authentication_service->deauthenticate();
    }

    protected function invalidate_sessions(int $user_id): void
    {
        $this->authentication_service->invalidate_sessions($user_id);
    }

    protected function is_authenticated(): bool
    {
        return $this->authentication_service->is_authenticated();
    }

    protected function get_authenticated_user(): User
    {
        return $this->authentication_service->get_authenticated_user();
    }

    protected function get_authenticated(string $type): mixed
    {
        $user = $this->get_authenticated_user();
        if ($type === 'user')
        {
            return $user;
        }

        if ($type === 'user_id')
        {
            return $user->id;
        }

        throw new \RuntimeException('Cannot return requested value');
    }

    /**
     * @param array<string> $permissions
     */
    protected function user_has_permissions(array $permissions): bool
    {
        return $this->authentication_service->user_has_permissions($permissions);
    }

    // Build info
    //
    /**
     * @return array{commit: null|string, timestamp: string}
     */
    protected function get_build_info(): array
    {
        return $this->debug_service->get_build_info();
    }
}
