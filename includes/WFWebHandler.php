<?php

namespace WebFramework\Core;

use Slim\Psr7\Factory\ServerRequestFactory;

class WFWebHandler extends WF
{
    protected string $request_uri = '/';

    public function init(): void
    {
        parent::init();
        if ($this->get_config_service()->get('database_enabled') == false)
        {
            $this->exit_error(
                'Database required',
                'Web handler is used but no database is configured.'
            );
        }
    }

    protected function exit_error(string $short_message, string $message): void
    {
        $this->exit_send_error(500, $short_message, 'generic', $message);
    }

    public function handle_request(): void
    {
        $route_service = $this->get_route_service();
        if (!$this->get_route_service()->has_routes())
        {
            $this->exit_error(
                'No routes loaded',
                'No routes have been loaded into Web handler.'
            );
        }

        // Run WebHandler
        //
        $this->get_authentication_service()->cleanup();

        if ($this->get_config_service()->get('security.blacklist.enabled') == true)
        {
            // Check blacklist
            //
            $user_id = null;
            if ($this->is_authenticated())
            {
                $user = $this->get_authenticated_user();
                $user_id = $user->id;
            }

            if (isset($_SERVER['REMOTE_ADDR'])
                && $this->get_blacklist_service()->is_blacklisted($_SERVER['REMOTE_ADDR'], $user_id))
            {
                $this->exit_error(
                    'Blacklisted',
                    'Too much suspicious activity. Do you think this is a mistake?'
                );
            }
        }

        $this->load_raw_input();
        $this->add_security_headers();
        $this->handle_fixed_input();
        $this->handle_routing();
    }

    private function load_raw_input(): void
    {
        $data = file_get_contents('php://input');
        if ($data === false)
        {
            return;
        }

        $data = json_decode($data, true);
        if ($data !== false && is_array($data))
        {
            $this->raw_post = $data;
        }
    }

    private function add_security_headers(): void
    {
        // Add random header (against BREACH like attacks)
        //
        header('X-Random:'.substr(sha1((string) time()), 0, mt_rand(1, 40)));

        // Add Clickjack prevention header
        //
        header('X-Frame-Options: SAMEORIGIN');
    }

    private function add_message_from_url(string $url_str): void
    {
        $msg = $this->get_protect_service()->decode_and_verify_array($url_str);

        if ($msg === false)
        {
            return;
        }

        $this->add_message($msg['mtype'], $msg['message'], $msg['extra_message']);
    }

    private function handle_fixed_input(): void
    {
        $fixed_action_filter = [
            'msg' => '.*',
            'token' => '.*',
            'do' => 'yes|preview',
        ];

        array_walk($fixed_action_filter, [$this, 'validate_input']);

        if (strlen($this->input['msg']))
        {
            $this->add_message_from_url($this->input['msg']);
        }

        if (strlen($this->input['do']))
        {
            if (!$this->get_csrf_service()->validate_token($this->input['token']))
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
            array_walk($site_filter, [$this, 'validate_input']);
        }
    }

    private function handle_routing(): void
    {
        $route_service = $this->get_route_service();
        $request = ServerRequestFactory::createFromGlobals();

        $redirect = $route_service->get_redirect($request);
        if ($redirect !== false)
        {
            header('Location: '.$redirect['url'], true, $redirect['redirect_type']);

            exit();
        }

        $action = $route_service->get_action($request);
        if ($action !== false)
        {
            foreach ($action['args'] as $key => $value)
            {
                $this->raw_post[$key] = $value;
            }

            $this->call_obj_func($this->get_config('actions.app_namespace').$action['class'], $action['function']);

            exit();
        }

        $this->exit_send_404();
    }

    /**
     * @param array<string> $permissions
     */
    private function enforce_permissions(string $object_name, array $permissions): void
    {
        $has_permissions = $this->user_has_permissions($permissions);

        if ($has_permissions)
        {
            return;
        }

        $is_json = (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json');

        if (!$this->is_authenticated() && !$is_json)
        {
            $query = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '';
            $msg = ['mtype' => 'info', 'message' => $this->get_config('authenticator.auth_required_message'), 'extra_message' => ''];

            $msg_fmt = 'msg='.$this->get_protect_service()->encode_and_auth_array($msg);

            header('Location: '.$this->get_config('base_url').$this->get_config('actions.login.location').'?return_page='.urlencode($this->request_uri).'&return_query='.urlencode($query).'&'.$msg_fmt, true, 302);

            exit();
        }

        $this->exit_send_403();
    }

    private function call_obj_func(string $object_name, string $function_name): void
    {
        $this->internal_verify(class_exists($object_name), "Requested object {$object_name} could not be located");
        $parents = class_parents($object_name);
        $this->internal_verify(isset($parents[ActionCore::class]), "Requested object {$object_name} does not derive from ActionCore");

        $action_filter = $object_name::get_filter();
        $action_permissions = $object_name::get_permissions();

        array_walk($action_filter, [$this, 'validate_input']);

        $this->enforce_permissions($object_name, $action_permissions);

        $action_obj = new $object_name();

        $this->internal_verify(method_exists($action_obj, $function_name), "Registered route function {$object_name}->{$function_name} does not exist");
        $action_obj->{$function_name}();
    }

    /**
     * @param array<string> $args
     */
    public function register_route(string $regex, string $file, string $class_function, array $args = []): void
    {
        @trigger_error('Deprecated. Directly call RouteService instead', E_USER_DEPRECATED);
        $target = explode('.', $class_function);

        if (count($target) !== 2)
        {
            throw new \InvalidArgumentException("Target name {$class_function} not class.function");
        }

        $regex_parts = explode(' ', $regex);

        if (count($regex_parts) !== 2)
        {
            throw new \InvalidArgumentException("Regex {$regex} does not contain single space");
        }

        $this->get_route_service()->register_action(
            $regex_parts[0],
            $regex_parts[1],
            $target[0],
            $target[1],
            $args,
        );
    }

    /**
     * @param array<string, int> $args
     */
    public function register_redirect(string $regex, string $redirect, string $type = '301', array $args = []): void
    {
        @trigger_error('Deprecated. Directly call RouteService instead', E_USER_DEPRECATED);
        $regex_parts = explode(' ', $regex);

        if (count($regex_parts) !== 2)
        {
            throw new \InvalidArgumentException("Regex {$regex} does not contain single space");
        }

        $this->get_route_service()->register_redirect(
            $regex_parts[0],
            $regex_parts[1],
            $redirect,
            (int) $type,
            $args,
        );
    }

    /**
     * @return never
     */
    public function exit_send_400(string $type = 'generic'): void
    {
        $this->exit_send_error(400, 'Bad Request', $type);
    }

    /**
     * @return never
     */
    public function exit_send_403(string $type = 'generic'): void
    {
        $this->exit_send_error(403, 'Access Denied', $type);
    }

    /**
     * @return never
     */
    public function exit_send_404(string $type = 'generic'): void
    {
        $this->exit_send_error(404, 'Page not found', $type);
    }

    /**
     * @return never
     */
    public function exit_send_error(int $code, string $title, string $type = 'generic', string $message = ''): void
    {
        if (!$this->initialized)
        {
            exit($title.PHP_EOL.$message.PHP_EOL);
        }

        if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] == 'application/json')
        {
            http_response_code($code);
            header('Content-type: application/json');

            echo(json_encode(
                [
                    'success' => false,
                    'title' => $title,
                    'details' => $message,
                ]
            ));

            exit();
        }

        $mapping = $this->get_config_service()->get('error_handlers.'.$code);
        $class = '';

        if (is_array($mapping))
        {
            if (isset($mapping[$type]))
            {
                $class = $mapping[$type];
            }
        }
        elseif (strlen($mapping))
        {
            $class = $mapping;
        }

        http_response_code($code);
        if (!strlen($class))
        {
            echo("<h1>{$title}</h1>");
            echo($message.'<br/>');
            echo('Please contact the administrator.');

            exit();
        }

        $this->input['error_title'] = $title;
        $this->input['error_message'] = $message;

        $object_name = $this->get_config_service()->get('actions.app_namespace').$class;
        $function_name = 'html_main';

        $this->call_obj_func($object_name, $function_name);

        exit();
    }

    public function get_csrf_token(): string
    {
        return $this->get_csrf_service()->get_token();
    }

    public function add_blacklist_entry(string $reason, int $severity = 1): void
    {
        if ($this->get_config_service()->get('security.blacklist.enabled') != true)
        {
            return;
        }

        $user_id = null;
        if ($this->is_authenticated())
        {
            $user = $this->get_authenticated_user();
            $user_id = $user->id;
        }

        $this->get_blacklist_service()->add_entry($_SERVER['REMOTE_ADDR'], $user_id, $reason, $severity);
    }
}
