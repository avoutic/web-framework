<?php
abstract class DataCore
{
    protected $database;
    public $id;

    protected $table_name;
    protected $base_fields;

    function __construct($database, $id, $table_name, $base_fields)
    {
        $this->database = $database;
        $this->id = $id;
        $this->table_name = $table_name;
        $this->base_fields = $base_fields;
    }

    static function exists($database, $id)
    {
        $result = $database->Query('SELECT id FROM '.$this->table_name.
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
                'SELECT '.implode(',', $this->base_fields).
                ' FROM '.$this->table.' WHERE id = ?', array($this->id));

        if ($result === FALSE)
            die('Failed to retrieve information.');

        if ($result->RecordCount() != 1)
            die('Failed to select single item.');

        $row = $result->fields;

        foreach ($this->base_fields as $name)
            $this->$name = $row[$name];
    }

    protected function fill_complex_fields()
    {
    }

    function update_field($field, $value)
    {
        // Update single field in existing item
        //
        $result = $this->database->Query('UPDATE '.$this->table_name.
                ' SET '.$field.' = ? WHERE id = ?',
                array($value, $this->id));

        if ($result === FALSE)
            die('Failed to update item.');
    }

    function delete()
    {
        if (FALSE === $this->database->Query(
                    'DELETE FROM '.$this->table_name.' WHERE id = ?',
                    array($this->id)))
            die('Failed to delete item.');
    }
};
?>
