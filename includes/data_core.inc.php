<?php
abstract class DataCore extends FrameworkCore
{
    public $id;

    static protected $table_name;
    static protected $base_fields;
    static protected $is_cacheable = true;

    function __construct($id, $fill_complex = true)
    {
        parent::__construct();

        $this->id = $id;
        $obj = false;

        if ($this->cache != null && $this->is_cacheable)
            $obj = $this->cache->get(static::get_cache_id($id));

        $this->fill_fields($fill_complex, $obj);
    }

    static function exists($id)
    {
        global $global_cache;

        if ($global_cache != null && static::$is_cacheable)
            if (false !== $global_cache->exists(static::get_cache_id($id)))
                return true;

        $result = WF::get_main_db()->Query('SELECT id FROM '.static::$table_name.
                                   ' WHERE id = ?', array($id));

        if ($result === false)
            return false;

        if ($result->RecordCount() != 1)
            return false;

        return true;
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

    function fill_fields($fill_complex, $obj = false)
    {
        if ($obj === false)
        {
            $this->fill_base_fields_from_db();

            if ($this->cache != null && $this->is_cacheable)
                $this->cache->add(static::get_cache_id($id), $this->get_base_fields());
        }
        else
            $this->fill_base_fields_from_obj($obj);

        if ($fill_complex)
            $this->fill_complex_fields();
    }

    private function fill_base_fields_from_db()
    {
        $result = $this->query(
                'SELECT `'.implode('`, `', static::$base_fields).'` '.
                'FROM '.static::$table_name.' WHERE id = ?', array($this->id));

        $this->verify($result !== false, 'Failed to retrieve base fields for '.static::$table_name);
        $this->verify($result->RecordCount() == 1, 'Failed to select single item. ('.$result->RecordCount().' for '.$this->id.' in '.static::$table_name.')');

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
        $result = $this->query('SELECT `'.$field.'` FROM '.static::$table_name.
                                         ' WHERE id = ?', array($this->id));
        $this->verify($result !== false, 'Failed to retrieve '.$field.' for '.static::$table_name);

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

        $result = $this->query($query, $args);
        $class = get_called_class();
        $this->verify($result !== false, 'Failed to update object ('.$class.')');

        foreach ($data as $key => $value)
            $this->$key = $value;
    }

    function update_field($field, $value)
    {
        $query = 'UPDATE '.static::$table_name;
        $query .= ' SET `'.$field.'` = ? ';
        $args[] = $value;

        $query .= 'WHERE id = ?';
        $args[] = $this->id;

        $result = $this->query($query, $args);
        $class = get_called_class();
        $this->verify($result !== false, 'Failed to update object ('.$class.')');

        $this->$field = $value;
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

        $result = $this->query($query, $args);
        $class = get_called_class();
        $this->verify($result !== false, 'Failed to decrease field of object ('.$class.')');

        $this->$field = $this->get_field($field);
    }

    function increase_field($field, $value = 1)
    {
        $query = 'UPDATE '.static::$table_name;
        $query .= ' SET `'.$field.'` = `'.$field.'` + ? ';
        $args[] = $value;

        $query .= 'WHERE id = ?';
        $args[] = $this->id;

        $result = $this->query($query, $args);
        $class = get_called_class();
        $this->verify($result !== false, 'Failed to increase field of object ('.$class.')');

        $this->$field = $this->get_field($field);
    }

    function delete()
    {
        if ($this->cache != null && $this->is_cacheable)
            $this->cache->invalidate(static::get_cache_id($this->id));

        $result = $this->query(
                    'DELETE FROM '.static::$table_name.' WHERE id = ?',
                    array($this->id));
        $this->verify($result !== false, 'Failed to delete item');
    }

    static function create($data)
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

        $result = WF::get_main_db()->InsertQuery($query, $args);

        $class = get_called_class();

        WF::verify($result !== false, 'Failed to create object ('.$class.')');

        return new $class($result);
    }

    static function count_objects($filter = array())
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
        $result = WF::get_main_db()->Query($query, $args);
        WF::verify($result !== false, 'Failed to count objects');
        WF::verify($result->RecordCount() == 1, 'Failed to count objects');

        return $result->fields['cnt'];
    }

    static function get_object($filter = array())
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

        $result = WF::get_main_db()->Query($query, $args);

        $class = get_called_class();

        WF::verify($result !== false, 'Failed to retrieve object ('.$class.')');
        WF::verify($result->RecordCount() <= 1, 'Non-unique object request ('.$class.')');

        if ($result->RecordCount() == 0)
            return false;

        return new $class($result->fields['id']);
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

    static function get_object_by_id($id)
    {
        return static::get_object(array('id' => $id));
    }

    static function get_object_info_by_id($id)
    {
        return static::get_object_data('get_info', array('id' => $id));
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

        $result = WF::get_main_db()->Query($query, $args);

        $class = get_called_class();

        WF::verify($result !== false, 'Failed to retrieve objects ('.$class.')');

        $info = array();
        foreach($result as $k => $row)
        {
            $info[$row['id']] = new $class($row['id']);
        }

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
};
?>
