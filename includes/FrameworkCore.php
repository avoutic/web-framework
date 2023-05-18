<?php

namespace WebFramework\Core;

use WebFramework\Core\Security\ConfigService;
use WebFramework\Core\Security\ProtectService;

class FrameworkCore
{
    protected Cache $cache;
    protected WF $framework;
    private ProtectService $protect_service;
    private ConfigService $secure_config_service;
    private Database $database;

    public function __construct()
    {
        $this->framework = WF::get_framework();
        $this->cache = $this->framework->get_cache();
        $this->database = $this->framework->get_db();
        $this->protect_service = $this->framework->get_protect_service();
        $this->secure_config_service = $this->framework->get_secure_config_service();
    }

    /**
     * @return array<string>
     */
    public function __serialize(): array
    {
        return [];
    }

    /**
     * @param array<string> $arr
     */
    public function __unserialize(array $arr): void
    {
        $this->framework = WF::get_framework();
        $this->cache = $this->framework->get_cache();
        $this->database = $this->framework->get_db();
        $this->protect_service = $this->framework->get_protect_service();
        $this->secure_config_service = $this->framework->get_secure_config_service();
    }

    protected function get_app_dir(): string
    {
        return $this->framework->internal_get_app_dir();
    }

    protected function get_config(string $path): mixed
    {
        return $this->framework->internal_get_config($path);
    }

    // Database related
    //
    protected function get_db(string $tag = ''): Database
    {
        return $this->framework->get_db($tag);
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

    // Input related
    //
    /**
     * @return array<mixed>
     */
    protected function get_input(): array
    {
        return $this->framework->get_input();
    }

    /**
     * @return array<mixed>
     */
    protected function get_raw_input(): array
    {
        return $this->framework->get_raw_input();
    }

    // Message related
    //
    /**
     * @return array<array{mtype: string, message: string, extra_message: string}>
     */
    protected function get_messages(): array
    {
        return $this->framework->get_messages();
    }

    protected function add_message(string $mtype, string $message, string $extra_message = ''): void
    {
        $this->framework->add_message($mtype, $message, $extra_message);
    }

    protected function get_message_for_url(string $mtype, string $message, string $extra_message = ''): string
    {
        $msg = ['mtype' => $mtype, 'message' => $message, 'extra_message' => $extra_message];

        return 'msg='.$this->protect_service->encode_and_auth_array($msg);
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

        $this->framework->internal_report_error($message, $stack);
    }

    protected function verify(bool|int $bool, string $message): void
    {
        $this->framework->internal_verify($bool, $message);
    }

    protected function blacklist_verify(bool|int $bool, string $reason, int $severity = 1): void
    {
        $this->framework->internal_blacklist_verify($bool, $reason, $severity);
    }

    // Security related
    //

    protected function get_auth_config(string $key_file): mixed
    {
        return $this->secure_config_service->get_auth_config($key_file);
    }

    protected function add_blacklist_entry(string $reason, int $severity = 1): void
    {
        $this->framework->add_blacklist_entry($reason, $severity);
    }

    protected function encode_and_auth_string(string $value): string
    {
        return $this->protect_service->encode_and_auth_string($value);
    }

    /**
     * @param array<mixed> $array
     */
    protected function encode_and_auth_array(array $array): string
    {
        return $this->protect_service->encode_and_auth_array($array);
    }

    protected function decode_and_verify_string(string $str): string|false
    {
        return $this->protect_service->decode_and_verify_string($str);
    }

    /**
     * @return array<mixed>|false
     */
    protected function decode_and_verify_array(string $str): array|false
    {
        return $this->protect_service->decode_and_verify_array($str);
    }

    // Authentication related
    //
    protected function authenticate(User $user): void
    {
        $this->framework->authenticate($user);
    }

    protected function deauthenticate(): void
    {
        $this->framework->deauthenticate();
    }

    protected function invalidate_sessions(int $user_id): void
    {
        $this->framework->invalidate_sessions($user_id);
    }

    protected function is_authenticated(): bool
    {
        return $this->framework->is_authenticated();
    }

    protected function get_authenticated_user(): User
    {
        return $this->framework->get_authenticated_user();
    }

    /**
     * @param array<string> $permissions
     */
    protected function user_has_permissions(array $permissions): bool
    {
        return $this->framework->user_has_permissions($permissions);
    }

    // Build info
    //
    /**
     * @return array{commit: null|string, timestamp: string}
     */
    protected function get_build_info(): array
    {
        return $this->framework->get_build_info();
    }
}
