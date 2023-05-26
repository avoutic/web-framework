<?php

namespace WebFramework\Core;

class BrowserSessionService
{
    public function start(string $hostName, string $httpMode): void
    {
        session_name(preg_replace('/\./', '_', $hostName));
        session_set_cookie_params(
            60 * 60 * 24,
            '/',
            $hostName,
            $httpMode === 'https',
            true
        );
        session_start();
    }

    public function get(string $key): mixed
    {
        if (!isset($_SESSION[$key]))
        {
            return null;
        }

        return $_SESSION[$key];
    }

    public function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function delete(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function getSessionId(): string|false
    {
        return session_id();
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        session_regenerate_id(true);
        session_destroy();
    }
}
