<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpUnauthorizedException;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\BlacklistService;
use WebFramework\Security\ConfigService as SecureConfigService;
use WebFramework\Security\CsrfService;
use WebFramework\Security\ProtectService;

abstract class ActionCore
{
    /**
     * @var array<array<string>|string>
     */
    protected array $input = [];

    /**
     * @var array<array<string>|string>
     */
    protected array $raw_input = [];

    public function __construct(
        protected Cache $cache,
        protected Container $container,
        protected Database $database,
        protected AssertService $assert_service,
        protected AuthenticationService $authentication_service,
        protected BlacklistService $blacklist_service,
        protected ConfigService $config_service,
        protected CsrfService $csrf_service,
        protected DebugService $debug_service,
        protected MessageService $message_service,
        protected ProtectService $protect_service,
        protected SecureConfigService $secure_config_service,
        protected ValidatorService $validator_service,
        protected WFWebHandler $web_handler,
    ) {
        $this->init();
    }

    public function init(): void
    {
    }

    /**
     * @param array<string, string> $args
     */
    public function handle_permissions_and_inputs(Request $request, array $args): void
    {
        $action_permissions = static::get_permissions();

        $has_permissions = $this->authentication_service->user_has_permissions($action_permissions);

        if (!$has_permissions)
        {
            if ($this->authentication_service->is_authenticated())
            {
                throw new HttpForbiddenException($request);
            }

            throw new HttpUnauthorizedException($request);
        }

        $action_filter = static::get_filter();

        $request = $this->validator_service->filter_request($request, $action_filter);
        $this->set_inputs($request, $args);
    }

    /**
     * @param array<string, string> $route_inputs
     */
    public function set_inputs(Request $request, array $route_inputs): void
    {
        $this->raw_input = $request->getAttribute('raw_inputs', []);
        $this->input = $request->getAttribute('inputs', []);

        $this->raw_input = array_merge($this->raw_input, $route_inputs);
        $this->input = array_merge($this->input, $route_inputs);
    }

    /**
     * @return array<string>
     */
    public static function get_filter(): array
    {
        return [];
    }

    protected function get_input_var(string $name, bool $content_required = false): string
    {
        $this->verify(isset($this->input[$name]), 'Missing input variable: '.$name);
        $this->verify(is_string($this->input[$name]), 'Not a string');

        if ($content_required)
        {
            $this->verify(strlen($this->input[$name]), 'Missing input variable: '.$name);
        }

        return $this->input[$name];
    }

    /**
     * @return array<string>
     */
    protected function get_input_array(string $name): array
    {
        $this->verify(isset($this->input[$name]), 'Missing input variable: '.$name);
        $this->verify(is_array($this->input[$name]), 'Not an array');

        return $this->input[$name];
    }

    /**
     * @return array<array<string>|string>
     */
    protected function get_input_vars(): array
    {
        $fields = [];

        foreach (array_keys($this->get_filter()) as $key)
        {
            $fields[$key] = $this->input[$key];
        }

        return $fields;
    }

    protected function get_raw_input_var(string $name): string
    {
        $this->verify(isset($this->raw_input[$name]), 'Missing input variable: '.$name);
        $this->verify(is_string($this->input[$name]), 'Not a string');

        return $this->raw_input[$name];
    }

    /**
     * @return array<string>
     */
    protected function get_raw_input_array(string $name): array
    {
        $this->verify(isset($this->raw_input[$name]), 'Missing input variable: '.$name);
        $this->verify(is_array($this->input[$name]), 'Not an array');

        return $this->raw_input[$name];
    }

    protected function get_base_url(): string
    {
        return $this->config_service->get('base_url');
    }

    /**
     * @return never
     */
    protected function exit_send_error(int $code, string $title, string $type = 'generic', string $message = ''): void
    {
        $this->web_handler->exit_send_error($code, $title, $type, $message);
    }

    /**
     * @return never
     */
    protected function exit_send_400(string $type = 'generic'): void
    {
        $this->web_handler->exit_send_400($type);
    }

    /**
     * @return never
     */
    protected function exit_send_403(string $type = 'generic'): void
    {
        $this->web_handler->exit_send_403($type);
    }

    /**
     * @return never
     */
    protected function exit_send_404(string $type = 'generic'): void
    {
        $this->web_handler->exit_send_404($type);
    }

    protected function blacklist_404(bool|int $bool, string $reason, string $type = 'generic'): void
    {
        if ($bool)
        {
            return;
        }

        $this->add_blacklist_entry($reason);

        $this->exit_send_404($type);
    }

    /**
     * @return array<string>
     */
    public static function get_permissions(): array
    {
        return [];
    }

    public static function redirect_login_type(): string
    {
        return 'redirect';
    }

    public static function encode(mixed $input, bool $double_encode = true): string
    {
        $value = (is_string($input) || is_bool($input) || is_int($input) || is_float($input) || is_null($input)) && is_bool($double_encode);

        if (!$value)
        {
            throw new \InvalidArgumentException('Not valid for encoding');
        }

        $str = htmlentities((string) $input, ENT_QUOTES, 'UTF-8', $double_encode);
        if (!strlen($str))
        {
            $str = htmlentities((string) $input, ENT_QUOTES, 'ISO-8859-1', $double_encode);
        }

        return $str;
    }

    protected function get_app_dir(): string
    {
        return $this->config_service->get('app_dir');
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
            throw new \InvalidArgumentException('Tags not supported');
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
