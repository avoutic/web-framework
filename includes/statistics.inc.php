<?php
class StatisticsCore
{
    static function log($database, $tag, $user_id, $action, $target_id, $extra1 = "", $extra2 = "", $extra3 = "")
    {
        $timestamp = date('Y-m-d H:i:s');

        $result = $database->InsertQuery('INSERT INTO statistics (tag, user_id, action, target_id, extra1, extra2, extra3, timestamp) VALUES (?,?,?,?,?,?,?,?)', array($tag, $user_id, $action, $target_id, $extra1, $extra2, $extra3, $timestamp));

        if ($result === FALSE)
            die('Failed to register statistics.');
    }
};
?>
