<?php
abstract class SimpleItem
{
    protected $database;
    protected $id;

    protected $table_name;
    protected $field_names;

    protected $select_query;

    function __construct($database, $id, $table_name, $field_names)
    {
        $this->database = $database;
        $this->id = $id;
        $this->table_name = $table_name;
        $this->field_names = $field_names;

        $this->select_query = 'SELECT ';
        $this->select_query .= implode(', ', $this->field_names);
        $this->select_query .= ' FROM '.$this->table_name.' WHERE id=?';

        // Retrieve from database
        //
        $result = $this->database->Query($this->select_query,
                array($this->id));

        if ($result === FALSE)
            die('Failed to select item');

        if ($result->RecordCount() != 1)
            die('Failed to select single item');

        foreach($this->field_descriptors as $name => $descriptor)
            $this->$name = $result->fields[$name];
    }

    function get_id()
    {
        return $this->id;
    }

    function get_field_names()
    {
        return $this->field_names;
    }
};

abstract class SimpleItemManipulator
{
    protected $database;
    protected $id;

    protected $table_name;
    protected $field_names;
    protected $field_descriptors;

    protected $select_query;
    protected $insert_query;
    protected $update_query;
    protected $delete_query;

    function __construct($database, $id, $table_name, $field_descriptors)
    {
        $this->database = $database;
        $this->id = $id;
        $this->table_name = $table_name;
        $this->field_names = array_keys($field_descriptors);
        $this->field_descriptors = $field_descriptors;

        $sql_fields = implode('=?, ', $this->field_names);
        $sql_fields .= '=?';

        $this->select_query = 'SELECT ';
        $this->select_query .= implode(', ', $this->field_names);
        $this->select_query .= ' FROM '.$this->table_name.' WHERE id=?';
        $this->insert_query .= $sql_fields;
        $this->insert_query = 'INSERT INTO '.$this->table_name.' SET ';
        $this->insert_query .= $sql_fields;
        $this->update_query = 'UPDATE '.$this->table_name.' SET ';
        $this->update_query .= $sql_fields;
        $this->update_query .= ' WHERE id=?';
        $this->delete_query = 'DELETE FROM '.$this->table_name.' WHERE id=?';

        if ($this->id == -1)
        {
            // Set default values
            //
            foreach($this->field_descriptors as $name => $descriptor)
                $this->$name = $descriptor['default'];
        }
        else
        {
            // Retrieve from database
            //
            $result = $this->database->Query($this->select_query,
                    array($this->id));

            if ($result === FALSE)
                die('Failed to select item');

            if ($result->RecordCount() != 1)
                die('Failed to select single item');

            foreach($this->field_descriptors as $name => $descriptor)
                $this->$name = $result->fields[$name];
        }
    }

    function get_id()
    {
        return $this->id;
    }

    function get_field_names()
    {
        return $this->field_names;
    }

    function get_field_descriptors()
    {
        return $this->field_descriptors;
    }

    function insert($field_values)
    {
        // Insert new one
        //
        $result = $this->database->InsertQuery($this->insert_query,
                $field_values);

        if ($result === FALSE)
            die('Failed to insert item');

        $this->id = $result;

        return $result;
    }

    function update($field_values)
    {
        array_push($field_values, $this->id);

        // Update existing one
        //
        $result = $this->database->Query($this->update_query,
                $field_values);

        if ($result === FALSE)
            die('Failed to update item');
    }

    function update_field($field, $value)
    {
        // Update single field in existing one
        //
        $result = $this->database->Query('UPDATE '.$this->table_name.' SET '.$field.' = ? WHERE id = ?',
                array($value, $this->id));

        if ($result === FALSE)
            die('Failed to update item');
    }

    function delete()
    {
        if (FALSE === $this->database->Query($this->delete_query,
                    array($this->id)))
            die('Failed to delete.');
    }
};

?>
