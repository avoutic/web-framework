<?php
require_once('wf_core.inc.php');

class WFWebHandler extends WF
{
    protected $blacklist = null;
    protected $authenticator = null;
    protected $auth_array = false;
    protected $route_array = array();
    protected $request_uri = '/';

    function init()
    {
        parent::init();
        if (WF::get_config('database_enabled') == false)
        {
            $this->exit_error('Database required',
                    'Web handler is used but no database is configured.');
        }

        if (WF::get_config('security.blacklist.enabled') == true)
        {
            require_once(WF::$includes.'blacklist.inc.php');
            $this->blacklist = new Blacklist();
        }
    }

    protected function exit_error($short_message, $message)
    {
        header('HTTP/1.0 500 Internal Error');
        print("<h1>$short_message</h1>");
        print($message.'<br/>');
        print('Please contact the administrator.');
        exit();
    }

    function handle_request()
    {
        if (count($this->route_array) == 0)
        {
            $this->exit_error('No routes loaded',
                    'No routes have been loaded into Web handler.');
        }

        // Run WebHandler
        //
        require_once(WF::$includes.'page_basic.inc.php');

        session_name(preg_replace('/\./', '_', WF::get_config('server_name')));
        session_set_cookie_params(60 * 60 * 24, '/', WF::get_config('server_name'),
                                  WF::get_config('http_mode') === 'https', true);
        session_start();

        if (WF::get_config('security.blacklist.enabled') == true)
        {
            // Check blacklist
            //
            $user_id = 0;
            if ($this->is_authenticated())
                $user_id = $this->get_authenticated('user_id');

            if ($this->blacklist->is_blacklisted($_SERVER['REMOTE_ADDR'], $user_id))
            {
                $this->exit_error('Blacklisted',
                        'Too much suspicious activity. Do you think this is a mistake?');
            }
        }

        $this->load_raw_input();
        $this->add_security_headers();
        $this->handle_fixed_input();
        $this->create_authenticator();
        $this->authenticator->cleanup();
        $this->auth_array = $this->authenticator->get_logged_in();
        $this->handle_page_routing();
    }

    private function load_raw_input()
    {
        $data = file_get_contents("php://input");
        $data = json_decode($data, true);
        if (is_array($data))
            $this->raw_post = $data;
    }

    private function add_security_headers()
    {
        // Add random header (against BREACH like attacks)
        //
        header('X-Random:'. substr(sha1(time()), 0, rand(1, 40)));

        // Add Clickjack prevention header
        //
        header('X-Frame-Options: SAMEORIGIN');
    }

    private function add_message_from_url($url_str)
    {
        $msg = $this->security->decode_and_verify_array($url_str);
        $this->internal_verify($msg !== false, 'Illegal message in url');

        $this->add_message($msg['mtype'], $msg['message'], $msg['extra_message']);
    }

    private function handle_fixed_input()
    {
        $fixed_page_filter = array(
                'msg' => '.*',
                'token' => '.*',
                'do' => 'yes|preview',
            );

        array_walk($fixed_page_filter, array($this, 'validate_input'));

        if (strlen($this->input['msg']))
            $this->add_message_from_url($this->input['msg']);

        if (strlen($this->input['do']))
        {
            if (!$this->security->validate_csrf_token($this->input['token']))
            {
                $this->input['do'] = '';
                $this->add_blacklist_entry('missing-csrf');
                $this->add_message('error', 'CSRF token missing, possible attack.', '');
            }
        }

        // Check if site logic has global filter
        //
        if (function_exists('site_get_filter'))
        {
            $site_filter = site_get_filter();
            array_walk($site_filter, array($this, 'validate_input'));
        }
    }

    private function create_authenticator()
    {
        // Create Authenticator
        //
        require(WF::$includes.'auth.inc.php');

        $auth_mode = WF::get_config('auth_mode');
        if ($auth_mode == 'redirect')
            $this->authenticator = new AuthRedirect();
        else if ($auth_mode == 'www-authenticate')
            $this->authenticator = new AuthWwwAuthenticate();
        else if ($auth_mode == 'custom' &&
                strlen(WF::get_config('auth_module')))
        {
            require_once(WF::$site_includes.WF::get_config('auth_module'));

            $this->authenticator = new AuthCustom();
        }
        else
            $this->internal_verify(false, 'No valid authenticator found.');
    }

    private function handle_page_routing()
    {
        // Check page requested
        //
        if (isset($_SERVER['REQUEST_URI']))
            $this->request_uri = preg_replace('/\?.*/', '', $_SERVER['REQUEST_URI']);

        $full_request_uri = $_SERVER['REQUEST_METHOD'].' '.$this->request_uri;

        // Check if there is a route to follow
        //
        $include_page = '';
        $target_info = null;

        foreach ($this->route_array as $target)
        {
            $route = $target['regex'];
            if (preg_match("!^$route$!", $full_request_uri, $matches))
            {
                $target_info = $target;
                break;
            }
        }

        if ($target_info != null)
        {
            // Matched in the route array
            //
            if ($target_info['type'] ==  'redirect')
            {
                $url = $target_info['redirect'];
                foreach ($target_info['args'] as $name => $match_index)
                    $url = preg_replace("!\{$name\}!", $matches[$match_index], $url);

                header('Location: '.$url, TRUE, $target_info['redir_type']);
                return;
            }

            $include_page = $target_info['include_file'];
        }
        else if ($full_request_uri == 'GET /')
            $include_page = WF::get_config('page.default_page');

        if (!strlen($include_page))
            $this->exit_send_404();

        $include_page_file = WF::$site_views.$include_page.".inc.php";
        $this->internal_verify(is_file($include_page_file), 'Page file for configured route not present');

        require_once($include_page_file);

        $object_name = "";
        $function_name = "";

        if ($target_info != null)
        {
            $target = explode('.', $target_info['class']);
            if (count($target) != 2)
                die('Illegal target name.');

            $object_name = $target[0];
            $function_name = $target[1];

            for ($i = 0; $i < count($target_info['args']); $i++)
                $this->raw_post[$target_info['args'][$i]] = $matches[$i + 1];
        }
        else
        {
            $object_name = preg_replace_callback('/(?:^|[_\-\.])(.?)/',
                    function($m) {
                        return strtoupper($m[1]);
                    }, 'page_'.$include_page);
            $function_name = "html_main";
        }

        $this->call_obj_func($object_name, $function_name);
    }

    private function enforce_permissions($object_name, $permissions)
    {
        $has_permissions = $this->user_has_permissions($permissions);

        if ($has_permissions)
            return;

        if (!$this->is_authenticated())
        {
            $redirect_type = $object_name::redirect_login_type();
            $this->authenticator->redirect_login($redirect_type, $this->request_uri);
            exit();
        }

        $this->authenticator->access_denied(WF::get_config('pages.login.location'));
        exit();
    }

    private function call_obj_func($object_name, $function_name)
    {
        $include_page_filter = NULL;
        $page_permissions = NULL;
        $page_obj = NULL;

        $this->internal_verify(class_exists($object_name), 'Requested object could not be located.');

        $include_page_filter = $object_name::get_filter();
        $page_permissions = $object_name::get_permissions();

        $this->internal_verify(is_array($include_page_filter), 'Filter does not have correct form');

        array_walk($include_page_filter, array($this, 'validate_input'));

        $this->enforce_permissions($object_name, $page_permissions);

        $this->internal_verify(class_exists($object_name), 'Registered route class does not exist');
        $page_obj = new $object_name();

        $this->internal_verify(method_exists($page_obj, $function_name), 'Registered route function does not exist');
        $page_obj->$function_name();
    }

    function register_route($regex, $file, $class_function, $args = array())
    {
        array_push($this->route_array, array(
                    'type' => 'route',
                    'regex' => $regex,
                    'include_file' => $file,
                    'class' => $class_function,
                    'args' => $args));
    }

    function register_redirect($regex, $redirect, $type = '301', $args = array())
    {
        array_push($this->route_array, array(
                    'type' => 'redirect',
                    'regex' => $regex,
                    'redirect' => $redirect,
                    'redir_type' => $type,
                    'args' => $args));
    }

    function exit_send_404($type = 'generic')
    {
        $this->exit_send_error(404, 'Page not found', $type);
    }

    function exit_send_error($code, $title, $type = 'generic')
    {
        $mapping = WF::get_config('error_handlers.'.$code);
        $include_page = '';

        if (is_array($mapping))
        {
            if (isset($mapping[$type]))
                $include_page = $mapping[$type];
        }
        else if (strlen($mapping))
            $include_page = $mapping;

        http_response_code($code);
        if (!strlen($include_page))
        {
            print('<h1>'.$title.'</h1>');
            exit();
        }

        $include_page_file = WF::$site_views.$include_page.".inc.php";
        $this->verify(file_exists($include_page_file), 'Missing error page: '.$include_page_file);

        require_once($include_page_file);

        $object_name = preg_replace_callback('/(?:^|[_\-\.])(.?)/',
                    function($m) {
                        return strtoupper($m[1]);
                    }, 'page_'.$include_page);
        $function_name = "html_main";

        $this->call_obj_func($object_name, $function_name);
        exit();
    }

    function is_authenticated()
    {
        return $this->auth_array !== false;
    }

    function authenticate($user)
    {
        $this->authenticator->set_logged_in($user);
    }

    function deauthenticate()
    {
        $this->authenticator->logoff();
    }

    function invalidate_sessions($user_id)
    {
        $this->authenticator->auth_invalidate_sessions($user_id);
    }

    function get_authenticated($item = '')
    {
        if (!strlen($item))
            return $this->auth_array;

        $this->internal_verify(isset($this->auth_array[$item]), 'Authenticated item not present');

        return $this->auth_array[$item];
    }

    function user_has_permissions($permissions)
    {
        if (count($permissions) == 0)
            return true;

        if (!$this->is_authenticated())
            return false;

        foreach ($permissions as $permission)
        {
            if ($permission == 'logged_in')
                continue;

            if (!$this->auth_array['user']->has_right($permission))
                return false;
        }

        return true;
    }

    function get_csrf_token()
    {
        return $this->security->get_csrf_token();
    }

    function add_blacklist_entry($reason, $severity = 1)
    {
        if (WF::get_config('security.blacklist.enabled') != true)
            return;

        $user_id = null;
        if ($this->is_authenticated())
            $user_id = $this->get_authenticated('user_id');

        $this->blacklist->add_entry($_SERVER['REMOTE_ADDR'], $user_id, $reason, $severity);
    }
};
?>
