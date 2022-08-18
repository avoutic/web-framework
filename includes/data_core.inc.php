<?php
abstract class DataCore extends FrameworkCore
{
    public $id;

    static protected $table_name;
    static protected $base_fields;
    static protected $is_cacheable = false;

    function __construct($id, $fill_complex = true)
    {
        parent::__construct();

        $this->id = $id;
        $this->fill_fields($fill_complex);
    }

    function __serialize()
    {
        return $this->get_base_fields();
    }

    function __unserialize($data)
    {
        parent::__unserialize($data);

        $this->id = $data['id'];
        $this->fill_base_fields_from_obj($data);
    }

    static function exists($id)
    {
        if (static::$is_cacheable)
        {
            $cache = WF::get_static_cache();

            if ($cache->exists(static::get_cache_id($id)) === true)
                return true;
        }

        $result = WF::get_main_db()->query('SELECT id FROM '.static::$table_name.
                                   ' WHERE id = ?', array($id));

        if ($result === false)
            return false;

        if ($result->RecordCount() != 1)
            return false;

        return true;
    }

    static function get_cache_id($id)
    {
       return static::$table_name.'['.$id.']';
    }

    function update_in_cache()
    {
        if (static::$is_cacheable)
            $this->cache->set(static::get_cache_id($this->id), $this);
    }

    function delete_from_cache()
    {
        if (static::$is_cacheable)
            $this->cache->invalidate(static::get_cache_id($this->id));
    }

    function get_base_fields()
    {
        $info = array(
            'id' => $this->id,
        );

        foreach (static::$base_fields as $name)
            $info[$name] = $this->$name;

        return $info;
    }

    function get_info()
    {
        return $this->get_base_fields();
    }

    function get_admin_info()
    {
        return $this->get_info();
    }

    function fill_fields($fill_complex)
    {
        $this->fill_base_fields_from_db();

        if ($fill_complex)
            $this->fill_complex_fields();
    }

    private function fill_base_fields_from_db()
    {
        $fields_fmt = implode('`, `', static::$base_fields);
        $table_name = static::$table_name;

        $query = <<<SQL
        SELECT `{$fields_fmt}`
        FROM {$table_name}
        WHERE id = ?
SQL;

        $params = array($this->id);

        $result = $this->query($query, $params);
        $this->verify($result !== false, "Failed to retrieve base fields for {$table_name}");
        $this->verify($result->RecordCount() == 1, "Failed to select single item for {$this->id} in {$table_name}");

        $row = $result->fields;

        foreach (static::$base_fields as $name)
            $this->$name = $row[$name];
    }

    private function fill_base_fields_from_obj($fields)
    {
        foreach (static::$base_fields as $name)
            $this->$name = $fields[$name];
    }

    protected function fill_complex_fields()
    {
    }

    function get_field($field)
    {
        $table_name = static::$table_name;

        $query = <<<SQL
        SELECT `{$field}`
        FROM {$table_name}
        WHERE id = ?
SQL;

        $params = array($this->id);

        $result = $this->query($query, $params);
        $this->verify($result !== false, "Failed to retrieve {$field} for {$table_name}");

        return $result->fields[$field];
    }

    function update($data)
    {
        $table_name = static::$table_name;

        $fields_fmt = '';
        $first = true;

        foreach ($data as $key => $value)
        {
            if (!$first)
                $fields_fmt .= ', ';
            else
                $first = false;

            $fields_fmt .= ' `'.$key.'` = ? ';
        }

        $query = <<<SQL
        UPDATE {$table_name}
        SET {$fields_fmt}
        WHERE id = ?
SQL;

        $data[] = $this->id;

        $result = $this->query($query, $data);
        $class = get_called_class();
        $this->verify($result !== false, "Failed to update object ({$class})");

        foreach ($data as $key => $value)
            $this->$key = $value;

        $this->update_in_cache();
    }

    function update_field($field, $value)
    {
        $table_name = static::$table_name;

        $query = <<<SQL
        UPDATE {$table_name}
        SET `{$field}` = ?
        WHERE id = ?
SQL;

        $params = array($value, $this->id);

        $result = $this->query($query, $params);
        $class = get_called_class();
        $this->verify($result !== false, "Failed to update object ({$class})");

        $this->$field = $value;

        $this->update_in_cache();
    }

    function decrease_field($field, $value = 1, $minimum = false)
    {
        $table_name = static::$table_name;

        $new_value_fmt = '';
        $params = array();

        if ($minimum)
        {
            $new_value_fmt = "GREATEST(?, `{$field}` - ?)";
            $params = array($minimum, $value);
        }
        else
        {
            $new_value_fmt = "`{$field}` - ?";
            $params = array($value);
        }

        $query = <<<SQL
        UPDATE {$table_name}
        SET `{$field}` = {$new_value_fmt}
        WHERE id = ?
SQL;

        $params[] = $this->id;

        $result = $this->query($query, $params);
        $class = get_called_class();
        $this->verify($result !== false, "Failed to decrease field of object ({$class})");

        $this->$field = $this->get_field($field);

        $this->update_in_cache();
    }

    function increase_field($field, $value = 1)
    {
        $table_name = static::$table_name;

        $query = <<<SQL
        UPDATE {$table_name}
        SET `{$field}` = `{$field}` + ?
        WHERE id = ?
SQL;

        $params = array($value, $this->id);

        $result = $this->query($query, $params);
        $class = get_called_class();
        $this->verify($result !== false, "Failed to increase field of object ({$class})");

        $this->$field = $this->get_field($field);

        $this->update_in_cache();
    }

    function delete()
    {
        $table_name = static::$table_name;

        $this->delete_from_cache();

        $query = <<<SQL
        DELETE FROM {$table_name}
        WHERE id = ?
SQL;

        $params = array($this->id);

        $result = $this->query($query, $params);
        $this->verify($result !== false, 'Failed to delete item');
    }

    static function create($data)
    {
        $table_name = static::$table_name;

        $fields_fmt = '';
        $first = true;

        foreach ($data as $key => $value)
        {
            if (!$first)
                $fields_fmt .= ', ';
            else
                $first = false;

            $fields_fmt .= ' `'.$key.'` = ? ';
        }

        $query = <<<SQL
        INSERT INTO {$table_name}
        SET {$fields_fmt}
SQL;

        $result = WF::get_main_db()->insert_query($query, $data);
        $class = get_called_class();
        WF::verify($result !== false, "Failed to create object ({$class})");

        return static::get_object_by_id($result, true);
    }

    static function count_objects($filter = array())
    {
        $table_name = static::$table_name;

        $params = array();
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

        $result = WF::get_main_db()->query($query, $params);
        $class = get_called_class();
        WF::verify($result !== false, "Failed to count objects ({$class})");
        WF::verify($result->RecordCount() == 1, "Failed to count objects ({$class})");

        return $result->fields['cnt'];
    }

    // This is the base retrieval function that all object functions should use
    // Cache checking is done here
    //
    static function get_object_by_id($id, $checked_presence = false)
    {
        if (static::$is_cacheable)
        {
            $cache = WF::get_static_cache();
            $obj = $cache->get(static::get_cache_id($id));

            // Cache hit
            //
            if ($obj !== false)
                return $obj;
        }

        $class = get_called_class();

        if ($checked_presence == false)
        {
            $table_name = static::$table_name;

            $query = <<<SQL
            SELECT id
            FROM {$table_name}
            WHERE id = ?
SQL;

            $params = array($id);

            $result = WF::get_main_db()->query($query, $params);

            WF::verify($result !== false, "Failed to retrieve object ({$class})");
            WF::verify($result->RecordCount() <= 1, "Non-unique object request ({$class})");

            if ($result->RecordCount() == 0)
                return false;
        }

        $obj = new $class($id);

        // Cache miss
        //
        $obj->update_in_cache();

        return $obj;
    }

    // Helper retrieval functions
    //
    static function get_object($filter = array())
    {
        $table_name = static::$table_name;

        $params = array();
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

        $result = WF::get_main_db()->query($query, $params);
        $class = get_called_class();
        WF::verify($result !== false, "Failed to retrieve object ({$class})");
        WF::verify($result->RecordCount() <= 1, "Non-unique object request ({$class})");

        if ($result->RecordCount() == 0)
            return false;

        return static::get_object_by_id($result->fields['id'], true);
    }

    static function get_object_info($filter = array())
    {
        return static::get_object_data('get_info', $filter);
    }

    static function get_object_data($data_function, $filter = array())
    {
        $obj = static::get_object($filter);

        if ($obj === false)
            return false;

        return $obj->$data_function();
    }

    static function get_object_info_by_id($id)
    {
        return static::get_object_data_by_id('get_info', $id);
    }

    static function get_object_data_by_id($data_function, $id)
    {
        $obj = static::get_object_by_id($id);

        if ($obj === false)
            return false;

        return $obj->$data_function();
    }

    static function get_objects($offset = 0, $results = 10, $filter = array(), $order = '')
    {
        $table_name = static::$table_name;

        $params = array();
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
            array_push($params, (int) $offset);
            array_push($params, (int) $results);
        }

        $query = <<<SQL
        SELECT id
        FROM {$table_name}
        {$where_fmt}
        {$order_fmt}
        {$limit_fmt}
SQL;

        $result = WF::get_main_db()->query($query, $params);
        $class = get_called_class();
        WF::verify($result !== false, "Failed to retrieve objects ({$class})");

        $info = array();
        foreach($result as $k => $row)
            $info[$row['id']] = static::get_object_by_id($row['id'], true);

        return $info;
    }

    static function get_objects_info($offset = 0, $results = 10, $filter = array(), $order = '')
    {
        return static::get_objects_data('get_info', $offset, $results, $filter, $order);
    }

    static function get_objects_data($data_function, $offset = 0, $results = 10, $filter = array(), $order = '')
    {
        $objs = static::get_objects($offset, $results, $filter, $order);

        $data = array();
        foreach ($objs as $obj)
            array_push($data, $obj->$data_function());

        return $data;
    }

    static function get_set_fmt($values)
    {
        $set_fmt = '';
        $first = true;

        foreach ($values as $key => $value)
        {
            if (!$first)
                $set_fmt .= ', ';
            else
                $first = false;

            $set_fmt .= ' `'.$key.'` = ? ';
        }

        return $set_fmt;
    }

    static function get_filter_array($filter)
    {
        $filter_fmt = '';
        $params = array();
        $first = true;

        foreach ($filter as $key => $value)
        {
            if (!$first)
                $filter_fmt .= ' AND ';
            else
                $first = false;

            if ($value === null)
                $filter_fmt .= "`{$key}` IS NULL";
            else
            {
                $filter_fmt .= "`{$key}` = ?";
                array_push($params, $value);
            }
        }

        return array(
            'query' => $filter_fmt,
            'params' => $params,
        );
    }

    function to_string()
    {
        $vars = call_user_func('get_object_vars', $this);
        scrub_state($vars);
        return $vars;
    }
};

// Can be used to register factories centrally
//
class FactoryCore extends FrameworkCore
{
    function __construct()
    {
        parent::__construct();
    }

    function __serialize()
    {
        return array();
    }

    function __unserialize($data)
    {
        parent::__unserialize($data);
    }
};
?>
