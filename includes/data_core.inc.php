<?php
abstract class DataCore
{
    protected $global_info;
    protected $database;
    protected $memcache;
    public $id;

    static protected $table_name;
    static protected $base_fields;
    static protected $is_cacheable = true;

    function __construct($global_info, $id)
    {
        $this->global_info = $global_info;
        $this->database = $global_info->database;
        $this->memcache = $global_info->memcache;
        $this->id = $id;

        $obj = FALSE;

        if ($this->memcache != null && $this->is_cacheable)
            $obj = $this->memcache->get(static::get_cache_id($id));

        $this->fill_fields($obj);
    }

    static function exists($global_info, $id)
    {
        if ($global_info->memcache != null && static::$is_cacheable)
            if (FALSE !== $global_info->memcache->get(static::get_cache_id($id)))
                return TRUE;
        
        $result = $database->Query('SELECT id FROM '.static::$table_name.
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

    function fill_fields($obj = FALSE)
    {
        if ($obj === FALSE)
        {
            $this->fill_base_fields_from_db();

            if ($this->memcache != null && $this->is_cacheable)
                $this->memcache->add(static::get_cache_id($id), $this);
        }
        else
            $this->fill_base_fields_from_obj($obj);

        $this->fill_complex_fields();
    }

    private function fill_base_fields_from_db()
    {
        $result = $this->database->Query(
                'SELECT '.implode(',', static::$base_fields).
                ' FROM '.static::$table_name.' WHERE id = ?', array($this->id));

        if ($result === FALSE)
            die('Failed to retrieve information.');

        if ($result->RecordCount() != 1)
            die('Failed to select single item. ('.$result->RecordCount().' for '.$this->id.' in '.static::$table_name.')');

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

    function update_field($field, $value)
    {
        $this->field = $value;

        if ($this->memcache != null && $this->is_cacheable)
            $this->memcache->replace(static::get_cache_id($this->id), $this);

        // Update single field in existing item
        //
        $result = $this->database->Query('UPDATE '.static::$table_name.
                ' SET '.$field.' = ? WHERE id = ?',
                array($value, $this->id));

        if ($result === FALSE)
            die('Failed to update item.');
    }

    function delete()
    {
        if ($this->memcache != null && $this->is_cacheable)
            $this->memcache->delete(static::get_cache_id($this->id));

        if (FALSE === $this->database->Query(
                    'DELETE FROM '.static::$table_name.' WHERE id = ?',
                    array($this->id)))
            die('Failed to delete item.');
    }

    static function count_objects($database)
    {
        $result = $database->Query('SELECT COUNT(id) AS cnt FROM '.static::$table_name,
                    array());

        if ($result === FALSE)
            die('Failed to count objects.');

        if ($result->RecordCount() != 1)
            die('Failed to count objects.');

        return $result->fields['cnt'];
    }

    static function get_objects($global_info, $offset = 0, $results = 10)
    {
        $result = $global_info->$database->Query('SELECT id FROM '.static::$table_name.' LIMIT ?,?',
                array((int) $offset, (int) $results));

        $class = get_called_class();

        if ($result === FALSE)
            die('Failed to retrieve objects ('.$class.').');

        $info = array();
        foreach($result as $k => $row)
        {
            $info[$row['id']] = new $class($global_info, $row['id']);
        }

        return $info;
    }
};

class FactoryCore
{
    protected $global_info;
    protected $database;

    function __construct($global_info)
    {
        $this->global_info = $global_info;
        $this->database = $global_info->database;
    }

    protected function get_core_object($type, $id)
    {
        if (!class_exists($type))
            die("Core Object not known!");

        if (FALSE === $type::exists($this->global_info, $id))
            return FALSE;

        return new $type($this->global_info, $id);
    }

    protected function core_object_exists($type, $id)
    {
        if (!class_exists($type))
            die("Core Object not known!");

        return $type::exists($this->global_info, $id);
    }

    protected function get_core_object_count($type)
    {
        if (!class_exists($type))
            die("Core Object not known!");

        return $type::count_objects($this->database);
    }

    protected function get_core_objects($type, $offset, $results)
    {
        if (!class_exists($type))
            die("Core Object not known!");

        return $type::get_objects($this->global_info, $offset, $results);
    }
};
?>
