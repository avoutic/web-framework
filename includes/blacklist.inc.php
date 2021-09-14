<?php
class BlackListEntry extends DataCore
{
    static protected $table_name = 'blacklist_entries';
    static protected $base_fields = array('ip', 'user_id', 'severity', 'reason', 'timestamp');
};

class Blacklist extends FrameworkCore
{
    function __construct()
    {
        parent::__construct();

        $this->module_config = $this->get_config('security.blacklist');
    }

    function cleanup()
    {
        $query = <<<SQL
        DELETE FROM blacklist_entries
        WHERE timestamp < ?
SQL;

        $cutoff = time() - $this->module_config['store_period'];

        $result = $this->query($query, array($cutoff));
        $this->verify($result !== false, 'Failed to clean up blacklist entries');
    }

    function add_entry($ip, $user_id, $reason, $severity = 1)
    {
        // Auto cleanup old entries (Over 30 days old)
        //
        $this->cleanup();

        $bt = debug_backtrace();
        $stack = array_reverse($bt);
        $caller = false;
        foreach($stack as $entry)
        {
            $caller = $entry;

            if (in_array($entry['function'], array('blacklist_verify', 'internal_blacklist_verify',
                                                   'add_blacklist_entry')))
                break;
        }

        $path_parts = pathinfo($caller['file']);
        $file = $path_parts['filename'];
        $full_reason = $file.':'.$caller['line'].':'.$reason;

        $entry = BlacklistEntry::create(array(
                        'ip' => $ip,
                        'user_id' => $user_id,
                        'severity' => $severity,
                        'reason' => $full_reason,
                        'timestamp' => time(),
                    ));
        $this->verify($entry !== false, 'Failed to add blacklist entry');
    }

    function is_blacklisted($ip, $user_id)
    {
        if ($this->module_config['enabled'] == false)
            return false;

        $query = <<<SQL
        SELECT SUM(severity) AS total
        FROM blacklist_entries
        WHERE ( ip = ? OR
                user_id = ?
              ) AND
              timestamp > ?
SQL;

        $cutoff = time() - $this->module_config['trigger_period'];

        $result = $this->query($query, array($ip, $user_id, $cutoff));
        $this->verify($result !== false, 'Failed to sum blacklist entries');

        return $result->fields['total'] > $this->module_config['threshold'];
    }
};
?>
