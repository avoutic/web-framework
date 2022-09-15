<?php
namespace WebFramework\Core;

abstract class Authenticator extends FrameworkCore
{
    abstract public function set_logged_in(User $user): void;

    /**
     * @return bool|array<mixed>
    */
    abstract public function get_logged_in(): bool|array;
    abstract public function logoff(): void;
    abstract public function cleanup(): void;
    abstract public function auth_invalidate_sessions(int $user_id): void;

    protected string $realm;

    public function redirect_login(string $type, string $target): void
    {
        if ($type == '401')
        {
            header('WWW-Authenticate: Basic realm="'.$this->realm.'"');
            header("HTTP/1.0 401 Unauthorized");
            print "<h1>Page requires authentication</h1>\n";
            print "Please include a WWW-Authenticate header field in the request.\n";
        }

        else if ($type == 'redirect')
        {
            $query = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '';

            header('Location: '.$this->get_config('base_url').$this->get_config('actions.login.location').'?return_page='.urlencode($target).'&return_query='.urlencode($query).'&'.$this->get_message_for_url('info', $this->get_config('authenticator.auth_required_message')), true, 302);
        }
        else if ($type == '403')
        {
            header("HTTP/1.0 403 Forbidden");
            print "<h1>Page requires authentication</h1>\n";
        }
        else
            die('Not a known redirect type.');

        exit(0);
    }


    // Deprecated (Remove for v4)
    //
    public function show_disabled(): void
    {
        trigger_error("Authenticator->show_disabled()", E_USER_DEPRECATED);

        header('HTTP/1.0 403 Page disabled');
        print '<h1>Page has been disabled</h1>';
        print 'This page has been disabled. Please return to the main page.';
        exit(0);
    }

    // Deprecated (Remove for v4)
    //
    public function access_denied(string $login_page): void
    {
        trigger_error("Authenticator->access_denied()", E_USER_DEPRECATED);

        # Access denied
        header('HTTP/1.0 403 Access Denied');
        print '<h1>Access Denied</h1>';
        print 'You do not have the authorization to view this page. Please return to the main page or <a href="'.$login_page.'">log in</a>.';
        exit(0);
    }

    /**
     * @return array{user: User, user_id: int, username: string, email: string}
     */
    public function get_auth_array(User $user): array
    {
        $info = array(
            'user' => $user,
            'user_id' => $user->id,
            'username' => $user->username,
            'email' => $user->email);

        return $info;
    }
}
?>
