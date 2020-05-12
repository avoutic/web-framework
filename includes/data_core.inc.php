<?php
abstract class DataCore
{
    protected $global_info;
    protected $database;
    protected $cache;
    public $id;

    static protected $table_name;
    static protected $base_fields;
    static protected $is_cacheable = true;

    function __construct($global_info, $id, $fill_complex = true)
    {
        $this->global_info = $global_info;
        $this->database = $global_info['database'];
        $this->cache = $global_info['cache'];
        $this->id = $id;

        $obj = FALSE;

        if ($this->cache != null && $this->is_cacheable)
            $obj = $this->cache->get(static::get_cache_id($id));

        $this->fill_fields($fill_complex, $obj);
    }

    static function exists($global_info, $id)
    {
        if ($global_info['cache'] != null && static::$is_cacheable)
            if (FALSE !== $global_info->cache->get(static::get_cache_id($id)))
                return TRUE;

        $result = $global_info['database']->Query('SELECT id FROM '.static::$table_name.
                                   ' WHERE id = ?', array($id));

        if ($result === FALSE)
            return FALSE;

        if ($result->RecordCount() != 1)
            return FALSE;

        return TRUE;
    }

    static function get_cache_id($id)
    {
       return static::$table_name.'{'.$id.'}';
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

    function fill_fields($fill_complex, $obj)
    {
        if ($obj === FALSE)
        {
            $this->fill_base_fields_from_db();

            if ($this->cache != null && $this->is_cacheable)
                $this->cache->add(static::get_cache_id($id), $this);
        }
        else
            $this->fill_base_fields_from_obj($obj);

        if ($fill_complex)
            $this->fill_complex_fields();
    }

    private function fill_base_fields_from_db()
    {
        $result = $this->database->Query(
                'SELECT `'.implode('`, `', static::$base_fields).'` '.
                'FROM '.static::$table_name.' WHERE id = ?', array($this->id));

        verify($result !== false, 'Failed to retrieve base fields for '.static::$table_name);
        verify($result->RecordCount() == 1, 'Failed to select single item. ('.$result->RecordCount().' for '.$this->id.' in '.static::$table_name.')');

        $row = $result->fields;

        foreach (static::$base_fields as $name)
            $this->$name = $row[$name];
    }

    private function fill_base_fields_from_obj($obj)
    {
        foreach (static::$base_fields as $name)
            $this->$name = $obj->$name;
    }

    protected function fill_complex_fields()
    {
    }

    function get_field($field)
    {
        $result = $this->database->Query('SELECT `'.$field.'` FROM '.static::$table_name.
                                         ' WHERE id = ?', array($this->id));
        verify($result !== FALSE, 'Failed to retrieve '.$field.' for '.static::$table_name);

        return $result->fields[$field];
    }

    function update($data)
    {
        $query = 'UPDATE '.static::$table_name;
        $query .= ' SET ';

        $first = true;
        foreach ($data as $key => $value)
        {
            if (!$first)
                $query .= ', ';

            $query .= ' `'.$key.'` = ? ';

            $first = false;
        }
        $args = $data;

        $query .= 'WHERE id = ?';
        $args[] = $this->id;

        $result = $this->database->Query($query, $args);
        $class = get_called_class();
        verify($result !== FALSE, 'Failed to update object ('.$class.')');

        foreach ($data as $key => $value)
            $this->$key = $value;

        return TRUE;
    }

    function update_field($field, $value)
    {
        $query = 'UPDATE '.static::$table_name;
        $query .= ' SET `'.$field.'` = ? ';
        $args[] = $value;

        $query .= 'WHERE id = ?';
        $args[] = $this->id;

        $result = $this->database->Query($query, $args);
        $class = get_called_class();
        verify($result !== FALSE, 'Failed to update object ('.$class.')');

        $this->$field = $value;

        return TRUE;
    }

    function decrease_field($field, $value = 1, $minimum = false)
    {
        $query = 'UPDATE '.static::$table_name;

        if ($minimum === false)
        {
            $query .= ' SET `'.$field.'` = `'.$field.'` - ? ';
            $args[] = $value;
        }
        else
        {
            $query .= ' SET `'.$field.'` = GREATEST(?, `'.$field.'` - ?) ';
            $args[] = $minimum;
            $args[] = $value;
        }

        $query .= 'WHERE id = ?';
        $args[] = $this->id;

        $result = $this->database->Query($query, $args);
        $class = get_called_class();
        verify($result !== FALSE, 'Failed to decrease field of object ('.$class.')');

        $this->$field = $this->get_field($field);

        return TRUE;
    }

    function increase_field($field, $value = 1)
    {
        $query = 'UPDATE '.static::$table_name;
        $query .= ' SET `'.$field.'` = `'.$field.'` + ? ';
        $args[] = $value;

        $query .= 'WHERE id = ?';
        $args[] = $this->id;

        $result = $this->database->Query($query, $args);
        $class = get_called_class();
        verify($result !== FALSE, 'Failed to increase field of object ('.$class.')');

        $this->$field = $this->get_field($field);

        return TRUE;
    }

    function delete()
    {
        if ($this->cache != null && $this->is_cacheable)
            $this->cache->delete(static::get_cache_id($this->id));

        $result = $this->database->Query(
                    'DELETE FROM '.static::$table_name.' WHERE id = ?',
                    array($this->id));
        verify($result !== false, 'Failed to delete item');

        return TRUE;
    }

    static function create($global_info, $data)
    {
        $query = 'INSERT INTO '.static::$table_name;
        $query .= ' SET ';

        $first = true;
        foreach ($data as $key => $value)
        {
            if (!$first)
                $query .= ', ';

            $query .= ' `'.$key.'` = ? ';

            $first = false;
        }

        $args = $data;

        $result = $global_info['database']->InsertQuery($query, $args);

        $class = get_called_class();

        verify($result !== FALSE, 'Failed to create object ('.$class.')');

        return new $class($global_info, $result);
    }

    static function count_objects($global_info, $filter = array())
    {
        $query = 'SELECT COUNT(id) AS cnt FROM '.static::$table_name;
        if (count($filter))
        {
            $query .= ' WHERE ';
            $first = true;
            foreach ($filter as $key => $value)
            {
                if (!$first)
                    $query .= ' AND ';

                $query .= ' `'.$key.'` = ? ';

                $first = false;
            }
        }

        $args = $filter;
        $result = $global_info['database']->Query($query, $args);
        verify($result !== FALSE, 'Failed to count objects');
        verify($result->RecordCount() == 1, 'Failed to count objects');

        return $result->fields['cnt'];
    }

    static function get_object($global_info, $filter = array())
    {
        $query = 'SELECT id FROM '.static::$table_name;
        if (count($filter))
        {
            $query .= ' WHERE ';
            $first = true;
            foreach ($filter as $key => $value)
            {
                if (!$first)
                    $query .= ' AND ';

                $query .= ' `'.$key.'` = ? ';

                $first = false;
            }
        }

        $args = $filter;

        verify(is_object($global_info["database"]), 'No database to query');
        $result = $global_info['database']->Query($query, $args);

        $class = get_called_class();

        verify($result !== FALSE, 'Failed to retrieve object ('.$class.')');
        verify($result->RecordCount() <= 1, 'Non-unique object request ('.$class.')');

        if ($result->RecordCount() == 0)
            return FALSE;

        return new $class($global_info, $result->fields['id']);
    }

    static function get_object_by_id($global_info, $id)
    {
        return static::get_object($global_info, array('id' => $id));
    }

    static function get_objects($global_info, $offset = 0, $results = 10, $filter = array(), $order = '')
    {
        $query = 'SELECT id FROM '.static::$table_name;
        if (count($filter))
        {
            $query .= ' WHERE ';
            $first = true;
            foreach ($filter as $key => $value)
            {
                if (!$first)
                    $query .= ' AND ';

                $query .= ' `'.$key.'` = ? ';

                $first = false;
            }
        }

        $args = $filter;
        if (strlen($order))
            $query .= ' ORDER BY '.$order;
        if($results != -1)
        {
            $query .= ' LIMIT ?,?';
            $args = array_merge($args, array((int) $offset, (int) $results));
        }

        $result = $global_info['database']->Query($query, $args);

        $class = get_called_class();

        verify($result !== FALSE, 'Failed to retrieve objects ('.$class.')');

        $info = array();
        foreach($result as $k => $row)
        {
            $info[$row['id']] = new $class($global_info, $row['id']);
        }

        return $info;
    }

    function to_string()
    {
        $vars = call_user_func('get_object_vars', $this);
        scrub_state($vars);
        return $vars;
    }
};

class FactoryCore
{
    protected $global_info;
    protected $database;
    protected $state;
    protected $config;

    function __construct($global_info)
    {
        $this->global_info = $global_info;
        $this->database = $global_info['database'];
        $this->state = $global_info['state'];
        $this->config = $global_info['config'];
    }

    protected function get_core_object_by_id($type, $id)
    {
        verify(class_exists($type), 'Core Object ("'.$type.'") not known');

        if (FALSE === $type::exists($this->global_info, $id))
            return FALSE;

        return new $type($this->global_info, $id);
    }

    protected function get_core_object($type, $filter = array())
    {
        verify(class_exists($type), 'Core Object ("'.$type.'") not known');

        return $type::get_object($this->global_info, $filter);
    }

    protected function core_object_exists($type, $id)
    {
        verify(class_exists($type), 'Core Object ("'.$type.'") not known');

        return $type::exists($this->global_info, $id);
    }

    protected function get_core_object_count($type, $filter = array())
    {
        verify(class_exists($type), 'Core Object ("'.$type.'") not known');

        return $type::count_objects($this->global_info, $filter);
    }

    protected function get_core_objects($type, $offset = 0, $results = 10, $filter = array(), $order = '')
    {
        verify(class_exists($type), 'Core Object ("'.$type.'") not known');

        return $type::get_objects($this->global_info, $offset, $results, $filter, $order);
    }

    protected function create_core_object($type, $data)
    {
        verify(class_exists($type), 'Core Object ("'.$type.'") not known');

        return $type::create($this->global_info, $data);
    }
};
?>
