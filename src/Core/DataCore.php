<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Entity\User;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\BlacklistService;
use WebFramework\Security\ConfigService as SecureConfigService;
use WebFramework\Security\ProtectService;

abstract class DataCore
{
    protected static string $tableName;

    /**
     * @var array<string>
     */
    protected static array $baseFields;
    protected static bool $isCacheable = false;

    /** @var array<string, null|bool|float|int|string> */
    private array $properties = [];

    protected Container $container;
    protected Cache $cache;
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

    public function __construct(
        public int $id,
        private bool $fillComplex = true,
    ) {
        $this->fillDependencies();
        $this->fillFields($this->fillComplex);
    }

    private function fillDependencies(): void
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
    }

    // Convert camelCase to snake_case
    private function camelToSnake(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }

    // Convert snake_case to camelCase
    private function snakeToCamel(string $input): string
    {
        return lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $input))));
    }

    public function __get(string $name): mixed
    {
        $name = $this->camelToSnake($name);

        if (array_key_exists($name, $this->properties))
        {
            return $this->properties[$name];
        }

        $this->reportError('Undefined property via __get(): '.$name);

        return null;
    }

    public function __set(string $name, null|bool|float|int|string $value): void
    {
        $name = $this->camelToSnake($name);

        if (property_exists($this, $name))
        {
            $this->reportError('Inaccessible property via __set(): '.$name);

            return;
        }

        $this->properties[$name] = $value;
    }

    /**
     * @return array<string>
     */
    public function __serialize(): array
    {
        return $this->getBaseFields();
    }

    /**
     * @param array<string> $data
     */
    public function __unserialize(array $data): void
    {
        $this->id = (int) $data['id'];
        $this->fillDependencies();
        $this->fillBaseFieldsFromObj($data);
    }

    public static function exists(int $id): bool
    {
        $container = ContainerWrapper::get();

        if (static::$isCacheable)
        {
            $cache = $container->get(Cache::class);

            if ($cache->exists(static::getCacheId($id)) === true)
            {
                return true;
            }
        }

        $result = $container->get(Database::class)->query('SELECT id FROM '.static::$tableName.
                                   ' WHERE id = ?', [$id]);

        if ($result === false)
        {
            return false;
        }

        if ($result->RecordCount() != 1)
        {
            return false;
        }

        return true;
    }

    public static function getCacheId(int $id): string
    {
        return static::$tableName.'['.$id.']';
    }

    protected function updateInCache(): void
    {
        if (static::$isCacheable)
        {
            $this->cache->set(static::getCacheId($this->id), $this);
        }
    }

    protected function deleteFromCache(): void
    {
        if (static::$isCacheable)
        {
            $this->cache->invalidate(static::getCacheId($this->id));
        }
    }

    /**
     * @return array<string>
     */
    public function getBaseFields(): array
    {
        $info = [
            'id' => $this->id,
        ];

        foreach (static::$baseFields as $name)
        {
            $info[$name] = $this->{$name};
        }

        return $info;
    }

    /**
     * @return array<mixed>
     */
    public function getInfo(): array
    {
        return $this->getBaseFields();
    }

    /**
     * @return array<mixed>
     */
    public function getAdminInfo(): array
    {
        return $this->getInfo();
    }

    private function fillFields(bool $fillComplex): void
    {
        $this->fillBaseFieldsFromDb();

        if ($fillComplex)
        {
            $this->fillComplexFields();
        }
    }

    private function fillBaseFieldsFromDb(): void
    {
        $fieldsFmt = implode('`, `', static::$baseFields);
        $tableName = static::$tableName;

        $query = <<<SQL
        SELECT `{$fieldsFmt}`
        FROM {$tableName}
        WHERE id = ?
SQL;

        $params = [$this->id];

        $result = $this->query($query, $params);
        $this->verify($result !== false, "Failed to retrieve base fields for {$tableName}");
        $this->verify($result->RecordCount() == 1, "Failed to select single item for {$this->id} in {$tableName}");

        $row = $result->fields;

        foreach (static::$baseFields as $name)
        {
            $this->{$name} = $row[$name];
        }
    }

    /**
     * @param array<string> $fields
     */
    private function fillBaseFieldsFromObj(array $fields): void
    {
        foreach (static::$baseFields as $name)
        {
            $this->{$name} = $fields[$name];
        }
    }

    protected function fillComplexFields(): void
    {
    }

    public function getField(string $field): string
    {
        $tableName = static::$tableName;

        $query = <<<SQL
        SELECT `{$field}`
        FROM {$tableName}
        WHERE id = ?
SQL;

        $params = [$this->id];

        $result = $this->query($query, $params);
        if ($result === false)
        {
            throw new \RuntimeException("Failed to retrieve {$field} for {$tableName}");
        }

        return $result->fields[$field];
    }

    /**
     * @param array<null|bool|float|int|string> $data
     */
    public function update(array $data): void
    {
        if (count($data) == 0)
        {
            return;
        }

        $tableName = static::$tableName;
        $setArray = static::getSetFmt($data);
        $params = $setArray['params'];

        $query = <<<SQL
        UPDATE {$tableName}
        SET {$setArray['query']}
        WHERE id = ?
SQL;

        $params[] = $this->id;

        $result = $this->query($query, $params);
        $class = static::class;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to update object ({$class})");
        }

        foreach ($data as $key => $value)
        {
            $this->{$key} = $value;
        }

        $this->updateInCache();
    }

    public function updateField(string $field, null|bool|float|int|string $value): void
    {
        $tableName = static::$tableName;

        // Mysqli does not accept empty for false, so force to zero
        //
        if ($value === false)
        {
            $value = 0;
        }

        $query = <<<SQL
        UPDATE {$tableName}
        SET `{$field}` = ?
        WHERE id = ?
SQL;

        $params = [$value, $this->id];

        $result = $this->query($query, $params);
        $class = static::class;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to update object ({$class})");
        }

        $this->{$field} = $value;

        $this->updateInCache();
    }

    public function decreaseField(string $field, int $value = 1, bool $minimum = false): void
    {
        $tableName = static::$tableName;

        $newValueFmt = '';
        $params = [];

        if ($minimum)
        {
            $newValueFmt = "GREATEST(?, `{$field}` - ?)";
            $params = [$minimum, $value];
        }
        else
        {
            $newValueFmt = "`{$field}` - ?";
            $params = [$value];
        }

        $query = <<<SQL
        UPDATE {$tableName}
        SET `{$field}` = {$newValueFmt}
        WHERE id = ?
SQL;

        $params[] = $this->id;

        $result = $this->query($query, $params);
        $class = static::class;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to decrease field of object ({$class})");
        }

        $this->{$field} = $this->getField($field);

        $this->updateInCache();
    }

    public function increaseField(string $field, int $value = 1): void
    {
        $tableName = static::$tableName;

        $query = <<<SQL
        UPDATE {$tableName}
        SET `{$field}` = `{$field}` + ?
        WHERE id = ?
SQL;

        $params = [$value, $this->id];

        $result = $this->query($query, $params);
        $class = static::class;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to increase field of object ({$class})");
        }

        $this->{$field} = $this->getField($field);

        $this->updateInCache();
    }

    public function delete(): void
    {
        $tableName = static::$tableName;

        $this->deleteFromCache();

        $query = <<<SQL
        DELETE FROM {$tableName}
        WHERE id = ?
SQL;

        $params = [$this->id];

        $result = $this->query($query, $params);
        if ($result === false)
        {
            throw new \RuntimeException('Failed to delete item');
        }
    }

    /**
     * @param array<null|bool|float|int|string> $data
     *
     * @return static
     */
    public static function create(array $data): self
    {
        $tableName = static::$tableName;
        $query = '';
        $params = [];

        if (count($data) == 0)
        {
            $query = <<<SQL
        INSERT INTO {$tableName}
        VALUES()
SQL;
        }
        else
        {
            $setArray = static::getSetFmt($data);
            $params = $setArray['params'];

            $query = <<<SQL
        INSERT INTO {$tableName}
        SET {$setArray['query']}
SQL;
        }

        $container = ContainerWrapper::get();
        $result = $container->get(Database::class)->insertQuery($query, $params);
        $class = static::class;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to create object ({$class})");
        }

        $obj = static::getObjectById($result, true);
        if ($obj === false)
        {
            throw new \RuntimeException("Failed to retrieve created object ({$class})");
        }

        return $obj;
    }

    /**
     * @param array<string, null|bool|float|int|string> $filter
     */
    public static function countObjects(array $filter = []): int
    {
        $tableName = static::$tableName;

        $params = [];
        $whereFmt = '';

        if (count($filter))
        {
            $filterArray = static::getFilterArray($filter);
            $whereFmt = "WHERE {$filterArray['query']}";
            $params = $filterArray['params'];
        }

        $query = <<<SQL
        SELECT COUNT(id) AS cnt
        FROM {$tableName}
        {$whereFmt}
SQL;

        $container = ContainerWrapper::get();
        $result = $container->get(Database::class)->query($query, $params);
        $class = static::class;

        if ($result === false)
        {
            throw new \RuntimeException("Failed to retrieve object ({$class})");
        }
        if ($result->RecordCount() != 1)
        {
            throw new \RuntimeException("Failed to count objects ({$class})");
        }

        return $result->fields['cnt'];
    }

    // This is the base retrieval function that all object functions should use
    // Cache checking is done here
    //
    /**
     * @return false|static
     */
    public static function getObjectById(int $id, bool $checkedPresence = false): false|DataCore
    {
        $container = ContainerWrapper::get();

        if (static::$isCacheable)
        {
            $cache = $container->get(Cache::class);
            $obj = $cache->get(static::getCacheId($id));

            // Cache hit
            //
            if ($obj !== false)
            {
                return $obj;
            }
        }

        $class = static::class;

        if ($checkedPresence == false)
        {
            $tableName = static::$tableName;

            $query = <<<SQL
            SELECT id
            FROM {$tableName}
            WHERE id = ?
SQL;

            $params = [$id];

            $result = $container->get(Database::class)->query($query, $params);

            if ($result === false)
            {
                throw new \RuntimeException("Failed to retrieve object ({$class})");
            }
            if ($result->RecordCount() > 1)
            {
                throw new \RuntimeException("Non-unique object request ({$class})");
            }

            if ($result->RecordCount() == 0)
            {
                return false;
            }
        }

        $obj = new $class($id);

        // Cache miss
        //
        $obj->updateInCache();

        return $obj;
    }

    // Helper retrieval functions
    //
    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return false|static
     */
    public static function getObject(array $filter = []): false|DataCore
    {
        $tableName = static::$tableName;

        $params = [];
        $whereFmt = '';

        if (count($filter))
        {
            $filterArray = static::getFilterArray($filter);
            $whereFmt = "WHERE {$filterArray['query']}";
            $params = $filterArray['params'];
        }

        $query = <<<SQL
        SELECT id
        FROM {$tableName}
        {$whereFmt}
SQL;

        $container = ContainerWrapper::get();
        $result = $container->get(Database::class)->query($query, $params);
        $class = static::class;

        if ($result === false)
        {
            throw new \RuntimeException("Failed to retrieve object ({$class})");
        }
        if ($result->RecordCount() > 1)
        {
            throw new \RuntimeException("Non-unique object request ({$class})");
        }

        if ($result->RecordCount() == 0)
        {
            return false;
        }

        return static::getObjectById($result->fields['id'], true);
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array<mixed>
     */
    public static function getObjectInfo(array $filter = []): false|array
    {
        return static::getObjectData('getInfo', $filter);
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array<mixed>|false
     */
    public static function getObjectData(string $dataFunction, array $filter = []): false|array
    {
        $obj = static::getObject($filter);

        if ($obj === false)
        {
            return false;
        }

        return $obj->{$dataFunction}();
    }

    /**
     * @return array<mixed>|false
     */
    public static function getObjectInfoById(int $id): false|array
    {
        return static::getObjectDataById('getInfo', $id);
    }

    /**
     * @return array<mixed>|false
     */
    public static function getObjectDataById(string $dataFunction, int $id): false|array
    {
        $obj = static::getObjectById($id);

        if ($obj === false)
        {
            return false;
        }

        return $obj->{$dataFunction}();
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array<static>
     */
    public static function getObjects(int $offset = 0, int $results = 10, array $filter = [], string $order = ''): array
    {
        $tableName = static::$tableName;

        $params = [];
        $whereFmt = '';

        if (count($filter))
        {
            $filterArray = static::getFilterArray($filter);
            $whereFmt = "WHERE {$filterArray['query']}";
            $params = $filterArray['params'];
        }

        $orderFmt = (strlen($order)) ? "ORDER BY {$order}" : '';
        $limitFmt = '';

        if ($results != -1)
        {
            $limitFmt = 'LIMIT ?,?';
            $params[] = (int) $offset;
            $params[] = (int) $results;
        }

        $query = <<<SQL
        SELECT id
        FROM {$tableName}
        {$whereFmt}
        {$orderFmt}
        {$limitFmt}
SQL;

        $container = ContainerWrapper::get();
        $result = $container->get(Database::class)->query($query, $params);
        $class = static::class;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to retrieve objects ({$class})");
        }

        $info = [];
        foreach ($result as $k => $row)
        {
            $obj = static::getObjectById($row['id'], true);
            if ($obj === false)
            {
                throw new \RuntimeException("Failed to retrieve {$class}");
            }

            $info[$row['id']] = $obj;
        }

        return $info;
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array<mixed>
     */
    public static function getObjectsInfo(int $offset = 0, int $results = 10, array $filter = [], string $order = ''): array
    {
        return static::getObjectsData('getInfo', $offset, $results, $filter, $order);
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array<mixed>
     */
    public static function getObjectsData(string $dataFunction, int $offset = 0, int $results = 10, array $filter = [], string $order = ''): array
    {
        $objs = static::getObjects($offset, $results, $filter, $order);

        $data = [];
        foreach ($objs as $obj)
        {
            $data[] = $obj->{$dataFunction}();
        }

        return $data;
    }

    /**
     * @param array<null|bool|float|int|string> $values
     *
     * @return array{query: string, params: array<bool|float|int|string>}
     */
    public static function getSetFmt(array $values): array
    {
        $setFmt = '';
        $params = [];
        $first = true;

        foreach ($values as $key => $value)
        {
            if (!$first)
            {
                $setFmt .= ', ';
            }
            else
            {
                $first = false;
            }

            // Mysqli does not accept empty for false, so force to zero
            //
            if ($value === false)
            {
                $value = 0;
            }

            if ($value === null)
            {
                $setFmt .= "`{$key}` = NULL";
            }
            else
            {
                $setFmt .= "`{$key}` = ?";
                $params[] = $value;
            }
        }

        return [
            'query' => $setFmt,
            'params' => $params,
        ];
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array{query: string, params: array<bool|float|int|string>}
     */
    public static function getFilterArray(array $filter): array
    {
        $filterFmt = '';
        $params = [];
        $first = true;

        foreach ($filter as $key => $value)
        {
            if (!$first)
            {
                $filterFmt .= ' AND ';
            }
            else
            {
                $first = false;
            }

            // Mysqli does not accept empty for false, so force to zero
            //
            if ($value === false)
            {
                $value = 0;
            }

            if ($value === null)
            {
                $filterFmt .= "`{$key}` IS NULL";
            }
            else
            {
                $filterFmt .= "`{$key}` = ?";
                $params[] = $value;
            }
        }

        return [
            'query' => $filterFmt,
            'params' => $params,
        ];
    }

    public function toString(): string
    {
        $vars = call_user_func('get_object_vars', $this);
        WFHelpers::scrubState($vars);

        return $vars;
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

        if ($type === 'username')
        {
            return $user->getUsername();
        }

        throw new \RuntimeException('Cannot return requested value: '.$type);
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
