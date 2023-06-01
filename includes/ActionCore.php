<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpForbiddenException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Psr7\Factory\ServerRequestFactory;
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
    protected array $rawInput = [];

    public function __construct(
        protected Cache $cache,
        protected Container $container,
        protected Database $database,
        protected AssertService $assertService,
        protected AuthenticationService $authenticationService,
        protected BlacklistService $blacklistService,
        protected ConfigService $configService,
        protected CsrfService $csrfService,
        protected DebugService $debugService,
        protected MessageService $messageService,
        protected ProtectService $protectService,
        protected SecureConfigService $secureConfigService,
        protected ValidatorService $validatorService,
    ) {
        $this->init();
    }

    public function init(): void
    {
    }

    /**
     * @param array<string, string> $args
     */
    public function handlePermissionsAndInputs(Request $request, array $args): void
    {
        $actionPermissions = static::getPermissions();

        $hasPermissions = $this->authenticationService->userHasPermissions($actionPermissions);

        if (!$hasPermissions)
        {
            if ($this->authenticationService->isAuthenticated())
            {
                throw new HttpForbiddenException($request);
            }

            throw new HttpUnauthorizedException($request);
        }

        $actionFilter = static::getFilter();

        $request = $this->validatorService->filterRequest($request, $actionFilter);
        $this->setInputs($request, $args);
    }

    /**
     * @param array<string, string> $routeInputs
     */
    public function setInputs(Request $request, array $routeInputs): void
    {
        $this->rawInput = $request->getAttribute('raw_inputs', []);
        $this->input = $request->getAttribute('inputs', []);

        $this->rawInput = array_merge($this->rawInput, $routeInputs);
        $this->input = array_merge($this->input, $routeInputs);
    }

    /**
     * @return array<string>
     */
    public static function getFilter(): array
    {
        return [];
    }

    protected function getInputVar(string $name, bool $contentRequired = false): string
    {
        $this->verify(isset($this->input[$name]), 'Missing input variable: '.$name);
        $this->verify(is_string($this->input[$name]), 'Not a string');

        if ($contentRequired)
        {
            $this->verify(strlen($this->input[$name]), 'Missing input variable: '.$name);
        }

        return $this->input[$name];
    }

    /**
     * @return array<string>
     */
    protected function getInputArray(string $name): array
    {
        $this->verify(isset($this->input[$name]), 'Missing input variable: '.$name);
        $this->verify(is_array($this->input[$name]), 'Not an array');

        return $this->input[$name];
    }

    /**
     * @return array<array<string>|string>
     */
    protected function getInputVars(): array
    {
        $fields = [];

        foreach (array_keys($this->getFilter()) as $key)
        {
            $fields[$key] = $this->input[$key];
        }

        return $fields;
    }

    protected function getRawInputVar(string $name): string
    {
        $this->verify(isset($this->rawInput[$name]), 'Missing input variable: '.$name);
        $this->verify(is_string($this->input[$name]), 'Not a string');

        return $this->rawInput[$name];
    }

    /**
     * @return array<string>
     */
    protected function getRawInputArray(string $name): array
    {
        $this->verify(isset($this->rawInput[$name]), 'Missing input variable: '.$name);
        $this->verify(is_array($this->input[$name]), 'Not an array');

        return $this->rawInput[$name];
    }

    protected function getBaseUrl(): string
    {
        return $this->configService->get('base_url');
    }

    /**
     * @return never
     */
    protected function exitSendError(int $code, string $title, string $type = 'generic', string $message = ''): void
    {
        throw new \RuntimeException($message);
    }

    /**
     * @return never
     */
    protected function exitSend400(string $type = 'generic'): void
    {
        $request = ServerRequestFactory::createFromGlobals();

        throw new HttpBadRequestException($request);
    }

    /**
     * @return never
     */
    protected function exitSend403(string $type = 'generic'): void
    {
        $request = ServerRequestFactory::createFromGlobals();

        throw new HttpForbiddenException($request);
    }

    /**
     * @return never
     */
    protected function exitSend404(string $type = 'generic'): void
    {
        $request = ServerRequestFactory::createFromGlobals();

        throw new HttpNotFoundException($request);
    }

    protected function blacklist404(bool|int $bool, string $reason, string $type = 'generic'): void
    {
        if ($bool)
        {
            return;
        }

        $this->addBlacklistEntry($reason);

        $this->exitSend404($type);
    }

    /**
     * @return array<string>
     */
    public static function getPermissions(): array
    {
        return [];
    }

    public static function redirectLoginType(): string
    {
        return 'redirect';
    }

    public static function encode(mixed $input, bool $doubleEncode = true): string
    {
        $value = (is_string($input) || is_bool($input) || is_int($input) || is_float($input) || is_null($input)) && is_bool($doubleEncode);

        if (!$value)
        {
            throw new \InvalidArgumentException('Not valid for encoding');
        }

        $str = htmlentities((string) $input, ENT_QUOTES, 'UTF-8', $doubleEncode);
        if (!strlen($str))
        {
            $str = htmlentities((string) $input, ENT_QUOTES, 'ISO-8859-1', $doubleEncode);
        }

        return $str;
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
            return $user->id;
        }

        if ($type === 'username')
        {
            return $user->username;
        }

        throw new \RuntimeException('Cannot return requested value: '.$type);
    }

    /**
     * @param array<string> $permissions
     */
    protected function userHasPermissions(array $permissions): bool
    {
        return $this->authenticationService->userHasPermissions($permissions);
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
