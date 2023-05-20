<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Exception\HttpForbiddenException;
use WebFramework\Exception\HttpNotFoundException;
use WebFramework\Exception\HttpUnauthorizedException;

class WFWebHandler
{
    protected string $request_uri = '/';

    /**
     * @var array<array{mtype: string, message: string, extra_message: string}>
     */
    private array $messages = [];

    public function __construct(
        private Security\AuthenticationService $authentication_service,
        private Security\BlacklistService $blacklist_service,
        private ConfigService $config_service,
        private Security\CsrfService $csrf_service,
        private ObjectFunctionCaller $object_function_caller,
        private Security\ProtectService $protect_service,
        private ResponseEmitter $response_emitter,
        private ResponseFactory $response_factory,
        private RouteService $route_service,
        private ValidatorService $validator_service,
    ) {
    }

    protected function exit_error(string $short_message, string $message): void
    {
        $this->exit_send_error(500, $short_message, 'generic', $message);
    }

    public function handle_request(?ServerRequestInterface $request = null, ?ResponseInterface $response = null): void
    {
        $request ??= ServerRequestFactory::createFromGlobals();
        $response ??= $this->response_factory->createResponse();

        if (!$this->route_service->has_routes())
        {
            $response = $this->response_emitter->error($request, $response, 'No routes loaded');
            $this->response_emitter->emit($response);
        }

        // Run WebHandler
        //
        $this->authentication_service->cleanup();

        $response = $this->kick_off_request($request, $response);

        $this->response_emitter->emit($response);
    }

    private function kick_off_request(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $request = $this->set_default_attributes($request);

        // Check blacklist
        //
        if ($this->blacklist_service->is_blacklisted(
            $request->getAttribute('ip'),
            $request->getAttribute('user_id'),
        ))
        {
            return $this->response_emitter->blacklisted($request, $response);
        }

        $request = $this->handle_fixed_input($request);
        $response = $this->add_security_headers($response);

        return $this->handle_routing($request, $response);
    }

    private function set_default_attributes(ServerRequestInterface $request): ServerRequestInterface
    {
        $server_params = $request->getServerParams();

        $ip = (isset($server_params['REMOTE_ADDR'])) ? $server_params['REMOTE_ADDR'] : 'app';
        $request = $request->withAttribute('ip', $ip);

        $content_type = $request->getHeaderLine('Content-Type');
        $is_json = (str_contains($content_type, 'application/json'));
        $request = $request->withAttribute('is_json', $is_json);

        if ($is_json)
        {
            $body = (string) $request->getBody();
            $data = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE)
            {
                $request = $request->withAttribute('json_data', $data);
            }
        }

        $user = null;
        $user_id = null;
        if ($this->authentication_service->is_authenticated())
        {
            $user = $this->authentication_service->get_authenticated_user();
            $user_id = $user->id;
        }

        $request = $request->withAttribute('user', $user);

        return $request->withAttribute('user_id', $user_id);
    }

    private function add_security_headers(ResponseInterface $response): ResponseInterface
    {
        // Add random header (against BREACH like attacks)
        //
        $response = $response->withHeader('X-Random', substr(sha1((string) time()), 0, mt_rand(1, 40)));

        // Add Clickjack prevention header
        //
        return $response->withHeader('X-Frame-Options', 'SAMEORIGIN');
    }

    /**
     * @return array<array{mtype: string, message: string, extra_message: string}>
     */
    public function get_messages(): array
    {
        return $this->messages;
    }

    public function add_message(string $type, string $message, string $extra_message): void
    {
        $this->messages[] = [
            'mtype' => $type,
            'message' => $message,
            'extra_message' => $extra_message,
        ];
    }

    private function add_message_from_url(string $url_str): void
    {
        $msg = $this->protect_service->decode_and_verify_array($url_str);

        if ($msg === false)
        {
            return;
        }

        $this->add_message($msg['mtype'], $msg['message'], $msg['extra_message']);
    }

    private function handle_fixed_input(ServerRequestInterface $request): ServerRequestInterface
    {
        $fixed_action_filter = [
            'msg' => '.*',
            'token' => '.*',
            'do' => 'yes|preview',
        ];

        $request = $this->validator_service->filter_request($request, $fixed_action_filter);

        $inputs = $request->getAttribute('inputs');

        if (strlen($inputs['msg']))
        {
            $this->add_message_from_url($inputs['msg']);
        }

        if (strlen($inputs['do']))
        {
            if (!$this->csrf_service->validate_token($inputs['token']))
            {
                $inputs['do'] = '';
                $request = $request->withAttribute('inputs', $inputs);

                $ip = $request->getAttribute('ip');
                $user_id = $request->getAttribute('user_id');

                $this->blacklist_service->add_entry($ip, $user_id, 'missing-csrf');
                $this->add_message('error', 'CSRF token missing, possible attack.', '');
            }
        }

        return $request;
    }

    private function handle_routing(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $redirect = $this->route_service->get_redirect($request);
        if ($redirect !== false)
        {
            return $this->response_emitter->redirect($redirect['url'], $redirect['redirect_type']);
        }

        $action = $this->route_service->get_action($request);
        if ($action !== false)
        {
            $request = $request->withAttribute('route_inputs', $action['args']);

            try
            {
                return $this->object_function_caller->execute($this->config_service->get('actions.app_namespace').$action['class'], $action['function'], $request, $response);
            }
            catch (HttpForbiddenException $e)
            {
                return $this->response_emitter->forbidden($request, $response);
            }
            catch (HttpNotFoundException $e)
            {
                return $this->response_emitter->not_found($request, $response);
            }
            catch (HttpUnauthorizedException $e)
            {
                return $this->response_emitter->unauthorized($request, $response);
            }
        }

        return $this->response_emitter->not_found($request, $response);
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

        $this->route_service->register_action(
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

        $this->route_service->register_redirect(
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

        $mapping = $this->config_service->get('error_handlers.'.$code);
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

        $object_name = $this->config_service->get('actions.app_namespace').$class;
        $function_name = 'html_main';

        $request = ServerRequestFactory::createFromGlobals();

        $response_factory = new ResponseFactory();
        $response = $response_factory->createResponse();

        $inputs = $request->getAttribute('inputs');

        $inputs['error_title'] = $title;
        $inputs['error_message'] = $message;

        $request = $request->withAttribute('inputs', $inputs);

        $this->object_function_caller->execute($object_name, $function_name, $request, $response);

        exit();
    }

    public function get_csrf_token(): string
    {
        @trigger_error('Deprecated. Directly call CsrfService instead', E_USER_DEPRECATED);

        return $this->csrf_service->get_token();
    }

    public function add_blacklist_entry(string $reason, int $severity = 1): void
    {
        @trigger_error('Deprecated. Directly call BlacklistService instead', E_USER_DEPRECATED);
        $user_id = null;
        if ($this->authentication_service->is_authenticated())
        {
            $user = $this->authentication_service->get_authenticated_user();
            $user_id = $user->id;
        }

        $this->blacklist_service->add_entry(
            $_SERVER['REMOTE_ADDR'],
            $user_id,
            $reason,
            $severity,
        );
    }
}
