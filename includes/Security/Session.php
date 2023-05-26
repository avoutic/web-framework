<?php

namespace WebFramework\Security;

use WebFramework\Core\DataCore;
use WebFramework\Core\Helpers;

/**
 * @property array<string> $baseFields
 */
class Session extends DataCore
{
    protected static string $tableName = 'sessions';
    protected static array $baseFields = ['user_id', 'session_id', 'start', 'last_active'];

    public function isValid(): bool
    {
        // Check for session timeout
        $current = time();
        $lastActiveTimestamp = Helpers::mysqlDatetimeToTimestamp($this->lastActive);

        if ($current - $lastActiveTimestamp >
            $this->getConfig('authenticator.session_timeout'))
        {
            return false;
        }

        $timestamp = date('Y-m-d H:i:s');

        // Update timestamp every 5 minutes
        //
        if ($current - $lastActiveTimestamp > 60 * 5)
        {
            $this->updateField('last_active', $timestamp);
        }

        // Restart session every 4 hours
        //
        $startTimestamp = Helpers::mysqlDatetimeToTimestamp($this->start);
        if ($current - $startTimestamp > 4 * 60 * 60)
        {
            session_regenerate_id(true);
            $this->updateField('start', $timestamp);
        }

        return true;
    }
}
