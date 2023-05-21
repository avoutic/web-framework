<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Psr7\Factory\ResponseFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use WebFramework\Middleware\AuthenticationInfoMiddleware;
use WebFramework\Middleware\BlacklistMiddleware;
use WebFramework\Middleware\CsrfValidationMiddleware;
use WebFramework\Middleware\IpMiddleware;
use WebFramework\Middleware\JsonParserMiddleware;
use WebFramework\Middleware\MessageMiddleware;
use WebFramework\Middleware\RoutingMiddleware;
use WebFramework\Middleware\SecurityHeadersMiddleware;

class WFWebHandler
{
    protected string $request_uri = '/';

    public function __construct(
        private Security\AuthenticationService $authentication_service,
        private Security\BlacklistService $blacklist_service,
        private ConfigService $config_service,
        private Security\CsrfService $csrf_service,
        private MessageService $message_service,
        private ObjectFunctionCaller $object_function_caller,
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

    public function handle_request(?Request $request = null, ?Response $response = null): void
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

        $middleware_stack = new MiddlewareStack(new RoutingMiddleware(
            $this->object_function_caller,
            $this->response_emitter,
            $this->response_factory,
            $this->route_service,
            $this->config_service->get('actions.app_namespace'),
        ));
        $middleware_stack->push(new SecurityHeadersMiddleware());
        $middleware_stack->push(new MessageMiddleware(
            $this->message_service,
            $this->validator_service,
        ));
        $middleware_stack->push(new JsonParserMiddleware());
        $middleware_stack->push(new BlacklistMiddleware(
            $this->blacklist_service,
        ));
        $middleware_stack->push(new CsrfValidationMiddleware(
            $this->blacklist_service,
            $this->csrf_service,
            $this->message_service,
            $this->validator_service,
        ));
        $middleware_stack->push(new AuthenticationInfoMiddleware(
            $this->authentication_service,
        ));
        $middleware_stack->push(new IpMiddleware());

        $response = $middleware_stack->handle($request);

        $this->response_emitter->emit($response);
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
