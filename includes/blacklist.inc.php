<?php
namespace WebFramework\Core;

class Blacklist extends FrameworkCore
{
    /**
     * @var array<mixed>
     */
    private array $module_config;

    function __construct()
    {
        parent::__construct();

        $this->module_config = $this->get_config('security.blacklist');
    }

    public function cleanup(): void
    {
        $query = <<<SQL
        DELETE FROM blacklist_entries
        WHERE timestamp < ?
SQL;

        $cutoff = time() - $this->module_config['store_period'];

        $result = $this->query($query, array($cutoff));
        $this->verify($result !== false, 'Failed to clean up blacklist entries');
    }

    public function add_entry(string $ip, int $user_id, string $reason, int $severity = 1): void
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

        $file = 'unknown';
        $line = 'unknown';

        if ($caller !== false)
        {
            if (isset($caller['file']))
            {
                $path_parts = pathinfo($caller['file']);
                $file = $path_parts['filename'];
            }

            $line = (isset($caller['line'])) ? $caller['line'] : 'unknown';
        }

        $full_reason = $file.':'.$line.':'.$reason;

        $entry = BlacklistEntry::create(array(
                        'ip' => $ip,
                        'user_id' => $user_id,
                        'severity' => $severity,
                        'reason' => $full_reason,
                        'timestamp' => time(),
                    ));
        $this->verify($entry !== false, 'Failed to add blacklist entry');
    }

    public function is_blacklisted(string $ip, ?int $user_id): bool
    {
        if ($this->module_config['enabled'] == false)
            return false;

        $cutoff = time() - $this->module_config['trigger_period'];
        $params = array($cutoff, $ip);
        $user_fmt = '';

        if ($user_id != null)
        {
            array_push($params, $user_id);
            $user_fmt = 'OR user_id = ?';
        }

        $query = <<<SQL
        SELECT SUM(severity) AS total
        FROM blacklist_entries
        WHERE timestamp > ? AND
              (
                 ip = ?
                {$user_fmt}
              )
SQL;

        $result = $this->query($query, $params);
        $this->verify($result !== false, 'Failed to sum blacklist entries');

        return $result->fields['total'] > $this->module_config['threshold'];
    }
};
?>
