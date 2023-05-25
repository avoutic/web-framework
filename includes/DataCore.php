<?php

namespace WebFramework\Core;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Security\AuthenticationService;
use WebFramework\Security\BlacklistService;
use WebFramework\Security\ConfigService as SecureConfigService;
use WebFramework\Security\ProtectService;

abstract class DataCore
{
    protected static string $table_name;

    /**
     * @var array<string>
     */
    protected static array $base_fields;
    protected static bool $is_cacheable = false;

    /** @var array<string, null|bool|float|int|string> */
    private array $properties = [];

    protected Container $container;
    protected Cache $cache;
    protected Database $database;
    protected AssertService $assert_service;
    protected AuthenticationService $authentication_service;
    protected BlacklistService $blacklist_service;
    protected ConfigService $config_service;
    protected DebugService $debug_service;
    protected MessageService $message_service;
    protected ProtectService $protect_service;
    protected SecureConfigService $secure_config_service;

    public function __construct(
        public int $id,
        private bool $fill_complex = true,
    ) {
        $this->fill_dependencies();
        $this->fill_fields($this->fill_complex);
    }

    private function fill_dependencies(): void
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
    }

    public function __get(string $name): mixed
    {
        if (array_key_exists($name, $this->properties))
        {
            return $this->properties[$name];
        }

        $this->report_error('Undefined property via __get(): '.$name);

        return null;
    }

    public function __set(string $name, null|bool|float|int|string $value): void
    {
        if (property_exists($this, $name))
        {
            $this->report_error('Inaccessible property via __set(): '.$name);

            return;
        }

        $this->properties[$name] = $value;
    }

    /**
     * @return array<string>
     */
    public function __serialize(): array
    {
        return $this->get_base_fields();
    }

    /**
     * @param array<string> $data
     */
    public function __unserialize(array $data): void
    {
        $this->id = (int) $data['id'];
        $this->fill_dependencies();
        $this->fill_base_fields_from_obj($data);
    }

    public static function exists(int $id): bool
    {
        $container = ContainerWrapper::get();

        if (static::$is_cacheable)
        {
            $cache = $container->get(Cache::class);

            if ($cache->exists(static::get_cache_id($id)) === true)
            {
                return true;
            }
        }

        $result = $container->get(Database::class)->query('SELECT id FROM '.static::$table_name.
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

    public static function get_cache_id(int $id): string
    {
        return static::$table_name.'['.$id.']';
    }

    protected function update_in_cache(): void
    {
        if (static::$is_cacheable)
        {
            $this->cache->set(static::get_cache_id($this->id), $this);
        }
    }

    protected function delete_from_cache(): void
    {
        if (static::$is_cacheable)
        {
            $this->cache->invalidate(static::get_cache_id($this->id));
        }
    }

    /**
     * @return array<string>
     */
    public function get_base_fields(): array
    {
        $info = [
            'id' => $this->id,
        ];

        foreach (static::$base_fields as $name)
        {
            $info[$name] = $this->{$name};
        }

        return $info;
    }

    /**
     * @return array<mixed>
     */
    public function get_info(): array
    {
        return $this->get_base_fields();
    }

    /**
     * @return array<mixed>
     */
    public function get_admin_info(): array
    {
        return $this->get_info();
    }

    private function fill_fields(bool $fill_complex): void
    {
        $this->fill_base_fields_from_db();

        if ($fill_complex)
        {
            $this->fill_complex_fields();
        }
    }

    private function fill_base_fields_from_db(): void
    {
        $fields_fmt = implode('`, `', static::$base_fields);
        $table_name = static::$table_name;

        $query = <<<SQL
        SELECT `{$fields_fmt}`
        FROM {$table_name}
        WHERE id = ?
SQL;

        $params = [$this->id];

        $result = $this->query($query, $params);
        $this->verify($result !== false, "Failed to retrieve base fields for {$table_name}");
        $this->verify($result->RecordCount() == 1, "Failed to select single item for {$this->id} in {$table_name}");

        $row = $result->fields;

        foreach (static::$base_fields as $name)
        {
            $this->{$name} = $row[$name];
        }
    }

    /**
     * @param array<string> $fields
     */
    private function fill_base_fields_from_obj(array $fields): void
    {
        foreach (static::$base_fields as $name)
        {
            $this->{$name} = $fields[$name];
        }
    }

    protected function fill_complex_fields(): void
    {
    }

    public function get_field(string $field): string
    {
        $table_name = static::$table_name;

        $query = <<<SQL
        SELECT `{$field}`
        FROM {$table_name}
        WHERE id = ?
SQL;

        $params = [$this->id];

        $result = $this->query($query, $params);
        if ($result === false)
        {
            throw new \RuntimeException("Failed to retrieve {$field} for {$table_name}");
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

        $table_name = static::$table_name;
        $set_array = static::get_set_fmt($data);
        $params = $set_array['params'];

        $query = <<<SQL
        UPDATE {$table_name}
        SET {$set_array['query']}
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

        $this->update_in_cache();
    }

    public function update_field(string $field, null|bool|float|int|string $value): void
    {
        $table_name = static::$table_name;

        // Mysqli does not accept empty for false, so force to zero
        //
        if ($value === false)
        {
            $value = 0;
        }

        $query = <<<SQL
        UPDATE {$table_name}
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

        $this->update_in_cache();
    }

    public function decrease_field(string $field, int $value = 1, bool $minimum = false): void
    {
        $table_name = static::$table_name;

        $new_value_fmt = '';
        $params = [];

        if ($minimum)
        {
            $new_value_fmt = "GREATEST(?, `{$field}` - ?)";
            $params = [$minimum, $value];
        }
        else
        {
            $new_value_fmt = "`{$field}` - ?";
            $params = [$value];
        }

        $query = <<<SQL
        UPDATE {$table_name}
        SET `{$field}` = {$new_value_fmt}
        WHERE id = ?
SQL;

        $params[] = $this->id;

        $result = $this->query($query, $params);
        $class = static::class;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to decrease field of object ({$class})");
        }

        $this->{$field} = $this->get_field($field);

        $this->update_in_cache();
    }

    public function increase_field(string $field, int $value = 1): void
    {
        $table_name = static::$table_name;

        $query = <<<SQL
        UPDATE {$table_name}
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

        $this->{$field} = $this->get_field($field);

        $this->update_in_cache();
    }

    public function delete(): void
    {
        $table_name = static::$table_name;

        $this->delete_from_cache();

        $query = <<<SQL
        DELETE FROM {$table_name}
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
        $table_name = static::$table_name;
        $query = '';
        $params = [];

        if (count($data) == 0)
        {
            $query = <<<SQL
        INSERT INTO {$table_name}
        VALUES()
SQL;
        }
        else
        {
            $set_array = static::get_set_fmt($data);
            $params = $set_array['params'];

            $query = <<<SQL
        INSERT INTO {$table_name}
        SET {$set_array['query']}
SQL;
        }

        $container = ContainerWrapper::get();
        $result = $container->get(Database::class)->insert_query($query, $params);
        $class = static::class;
        if ($result === false)
        {
            throw new \RuntimeException("Failed to create object ({$class})");
        }

        $obj = static::get_object_by_id($result, true);
        if ($obj === false)
        {
            throw new \RuntimeException("Failed to retrieve created object ({$class})");
        }

        return $obj;
    }

    /**
     * @param array<string, null|bool|float|int|string> $filter
     */
    public static function count_objects(array $filter = []): int
    {
        $table_name = static::$table_name;

        $params = [];
        $where_fmt = '';

        if (count($filter))
        {
            $filter_array = static::get_filter_array($filter);
            $where_fmt = "WHERE {$filter_array['query']}";
            $params = $filter_array['params'];
        }

        $query = <<<SQL
        SELECT COUNT(id) AS cnt
        FROM {$table_name}
        {$where_fmt}
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
    public static function get_object_by_id(int $id, bool $checked_presence = false): false|DataCore
    {
        $container = ContainerWrapper::get();

        if (static::$is_cacheable)
        {
            $cache = $container->get(Cache::class);
            $obj = $cache->get(static::get_cache_id($id));

            // Cache hit
            //
            if ($obj !== false)
            {
                return $obj;
            }
        }

        $class = static::class;

        if ($checked_presence == false)
        {
            $table_name = static::$table_name;

            $query = <<<SQL
            SELECT id
            FROM {$table_name}
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
        $obj->update_in_cache();

        return $obj;
    }

    // Helper retrieval functions
    //
    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return false|static
     */
    public static function get_object(array $filter = []): false|DataCore
    {
        $table_name = static::$table_name;

        $params = [];
        $where_fmt = '';

        if (count($filter))
        {
            $filter_array = static::get_filter_array($filter);
            $where_fmt = "WHERE {$filter_array['query']}";
            $params = $filter_array['params'];
        }

        $query = <<<SQL
        SELECT id
        FROM {$table_name}
        {$where_fmt}
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

        return static::get_object_by_id($result->fields['id'], true);
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array<mixed>
     */
    public static function get_object_info(array $filter = []): false|array
    {
        return static::get_object_data('get_info', $filter);
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array<mixed>|false
     */
    public static function get_object_data(string $data_function, array $filter = []): false|array
    {
        $obj = static::get_object($filter);

        if ($obj === false)
        {
            return false;
        }

        return $obj->{$data_function}();
    }

    /**
     * @return array<mixed>|false
     */
    public static function get_object_info_by_id(int $id): false|array
    {
        return static::get_object_data_by_id('get_info', $id);
    }

    /**
     * @return array<mixed>|false
     */
    public static function get_object_data_by_id(string $data_function, int $id): false|array
    {
        $obj = static::get_object_by_id($id);

        if ($obj === false)
        {
            return false;
        }

        return $obj->{$data_function}();
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array<static>
     */
    public static function get_objects(int $offset = 0, int $results = 10, array $filter = [], string $order = ''): array
    {
        $table_name = static::$table_name;

        $params = [];
        $where_fmt = '';

        if (count($filter))
        {
            $filter_array = static::get_filter_array($filter);
            $where_fmt = "WHERE {$filter_array['query']}";
            $params = $filter_array['params'];
        }

        $order_fmt = (strlen($order)) ? "ORDER BY {$order}" : '';
        $limit_fmt = '';

        if ($results != -1)
        {
            $limit_fmt = 'LIMIT ?,?';
            $params[] = (int) $offset;
            $params[] = (int) $results;
        }

        $query = <<<SQL
        SELECT id
        FROM {$table_name}
        {$where_fmt}
        {$order_fmt}
        {$limit_fmt}
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
            $obj = static::get_object_by_id($row['id'], true);
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
    public static function get_objects_info(int $offset = 0, int $results = 10, array $filter = [], string $order = ''): array
    {
        return static::get_objects_data('get_info', $offset, $results, $filter, $order);
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array<mixed>
     */
    public static function get_objects_data(string $data_function, int $offset = 0, int $results = 10, array $filter = [], string $order = ''): array
    {
        $objs = static::get_objects($offset, $results, $filter, $order);

        $data = [];
        foreach ($objs as $obj)
        {
            $data[] = $obj->{$data_function}();
        }

        return $data;
    }

    /**
     * @param array<null|bool|float|int|string> $values
     *
     * @return array{query: string, params: array<bool|float|int|string>}
     */
    public static function get_set_fmt(array $values): array
    {
        $set_fmt = '';
        $params = [];
        $first = true;

        foreach ($values as $key => $value)
        {
            if (!$first)
            {
                $set_fmt .= ', ';
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
                $set_fmt .= "`{$key}` = NULL";
            }
            else
            {
                $set_fmt .= "`{$key}` = ?";
                $params[] = $value;
            }
        }

        return [
            'query' => $set_fmt,
            'params' => $params,
        ];
    }

    /**
     * @param array<null|bool|float|int|string> $filter
     *
     * @return array{query: string, params: array<bool|float|int|string>}
     */
    public static function get_filter_array(array $filter): array
    {
        $filter_fmt = '';
        $params = [];
        $first = true;

        foreach ($filter as $key => $value)
        {
            if (!$first)
            {
                $filter_fmt .= ' AND ';
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
                $filter_fmt .= "`{$key}` IS NULL";
            }
            else
            {
                $filter_fmt .= "`{$key}` = ?";
                $params[] = $value;
            }
        }

        return [
            'query' => $filter_fmt,
            'params' => $params,
        ];
    }

    public function to_string(): string
    {
        $vars = call_user_func('get_object_vars', $this);
        WFHelpers::scrub_state($vars);

        return $vars;
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
