<?php
abstract class DataCore
{
    protected $database;
    public $id;

    static protected $table_name;
    static protected $base_fields;

    function __construct($database, $id)
    {
        $this->database = $database;
        $this->id = $id;

        $this->fill_fields();
    }

    static function exists($database, $id)
    {
        $result = $database->Query('SELECT id FROM '.static::$table_name.
                                   ' WHERE id = ?', array($id));

        if ($result === FALSE)
            return FALSE;

        if ($result->RecordCount() != 1)
            return FALSE;

        return TRUE;
    }

    function get_id()
    {
        return $this->id;
    }

    function fill_fields()
    {
        $this->fill_base_fields();
        $this->fill_complex_fields();
    }

    private function fill_base_fields()
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

    protected function fill_complex_fields()
    {
    }

    function update_field($field, $value)
    {
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
        if (FALSE === $this->database->Query(
                    'DELETE FROM '.static::$table_name.' WHERE id = ?',
                    array($this->id)))
            die('Failed to delete item.');
    }
};

class FactoryCore
{
    protected $database;

    function __construct($database)
    {
        $this->database = $database;
    }

    protected function get_core_object($type, $id)
    {
        if (!class_exists($type))
            die("Core Object not known!");

        if (FALSE === $type::exists($this->database, $id))
            return FALSE;

        return new $type($this->database, $id);
    }

    protected function core_object_exists($type, $id)
    {
        if (!class_exists($type))
            die("Core Object not known!");

        return $type::exists($this->database, $id);
    }
};
?>
