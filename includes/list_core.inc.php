<?php
abstract class ListCore
{
    protected $database;
    protected $list;

    function __construct($database, $list)
    {
        $this->database = $database;
        $this->list = $list;
    }

    function in_list($network,$identifier)
    {
        $result = $this->database->Query('SELECT id FROM lists WHERE list = ? AND network = ? AND identifier = ?',
                array($this->list, $network, $identifier));

        if ($result === FALSE)
            die('Failed to check list.');

        if ($result->RecordCount() >= 1)
            return TRUE;

        return FALSE;
    }

    function add($network, $identifier)
    {
        $result = $this->database->InsertQuery('INSERT INTO lists (list, network, identifier) VALUES (?,?,?)', array($this->list, $network, $identifier));

        if ($result === FALSE)
            die('Failed to add to list.');

        return $result;
    }

    function delete($id)
    {
        $result = $this->database->Query('DELETE FROM lists WHERE list = ? AND id = ?', array($this->list, $id));

        if ($result === FALSE)
            die('Failed to delete from list.');

        return TRUE;
    }

    function get_entries()
    {
        $result = $this->database->Query('SELECT id, network, identifier FROM lists WHERE list = ?', array($this->list));

        if ($result === FALSE)
            die('Failed to get list.');

        $info = array();
        foreach ($result as $k => $row)
        {
            array_push($info, array(
                    'id' => $row['id'],
                    'network' => $row['network'],
                    'identifier' => $row['identifier']));
        }

        return $info;
    }
};
?>
