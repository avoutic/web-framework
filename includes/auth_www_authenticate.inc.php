<?php
namespace WebFramework\Core;

class AuthWwwAuthenticate extends Authenticator
{
    function __construct()
    {
        parent::__construct();

        if (strlen($this->get_config('realm')))
            $this->realm = $this->get_config('realm');
        else
            $this->realm = 'Unknown realm';
    }

    public function cleanup(): void
    {
        # Nothing to cleanup
    }

    public function set_logged_in(User $user): void
    {
        # Cannot specifically log in on this authentication method
        # Done every get_logged_in() call
    }

    /**
     * @return false|array{user: User, user_id: int, username: string, email: string}
     */
    public function get_logged_in(): false|array
    {
        if (!isset($_SERVER['PHP_AUTH_USER']) ||
            !isset($_SERVER['PHP_AUTH_PW']))
        {
            return false;
        }

        $username = $_SERVER['PHP_AUTH_USER'];
        $password = sha1($_SERVER['PHP_AUTH_PW']);

        $factory = new BaseFactory();

        $user = $factory->get_user_by_username($username);

        if ($user === false || !$user->check_password($password))
            return false;

        $info = $this->get_auth_array($user);

        return $info;
    }

    public function logoff(): void
    {
        # Cannot deauthenticate from server side on this authentication method
    }

    public function auth_invalidate_sessions(int $user_id): void
    {
        # Cannot deauthenticate from server side on this authentication method
    }
};
?>
