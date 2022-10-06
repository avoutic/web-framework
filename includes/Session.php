<?php

namespace WebFramework\Core;

/**
 * @property array<string> $base_fields
 */
class Session extends DataCore
{
    protected static string $table_name = 'sessions';
    protected static array $base_fields = ['user_id', 'session_id', 'start', 'last_active'];

    public string $user_id;
    public string $session_id;
    public string $start;
    public string $last_active;

    public function is_valid(): bool
    {
        // Check for session timeout
        $current = time();
        $last_active_timestamp = Helpers::mysql_datetime_to_timestamp($this->last_active);

        if ($current - $last_active_timestamp >
            $this->get_config('authenticator.session_timeout'))
        {
            return false;
        }

        $timestamp = date('Y-m-d H:i:s');

        // Update timestamp every 5 minutes
        //
        if ($current - $last_active_timestamp > 60 * 5)
        {
            $this->update_field('last_active', $timestamp);
        }

        // Restart session every 4 hours
        //
        $start_timestamp = Helpers::mysql_datetime_to_timestamp($this->start);
        if ($current - $start_timestamp > 4 * 60 * 60)
        {
            session_regenerate_id(true);
            $this->update_field('start', $timestamp);
        }

        return true;
    }
}
