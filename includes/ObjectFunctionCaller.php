<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use WebFramework\Exception\HttpForbiddenException;
use WebFramework\Exception\HttpUnauthorizedException;

class ObjectFunctionCaller
{
    public function __construct(
        private AssertService $assert_service,
        private Security\AuthenticationService $authentication_service,
        private WF $container_service,
        private ValidatorService $validator_service,
    ) {
    }

    public function execute(string $object_name, string $function_name, Request $request, Response $response): Response
    {
        $this->assert_service->verify(class_exists($object_name), "Requested object {$object_name} could not be located");
        $parents = class_parents($object_name);

        if (isset($parents[ActionCore::class]))
        {
            // Derives from old-style ActionCore
            // Handles own flow and response so will only return a Response in case of an error
            //
            return $this->execute_action_core($object_name, $function_name, $request, $response);
        }

        $action_obj = new $object_name($this->container_service);

        $this->assert_service->verify(method_exists($action_obj, $function_name), "Registered route function {$object_name}->{$function_name} does not exist");

        return $action_obj->{$function_name}($request, $response, $request->getAttribute('route_inputs'));
    }

    private function execute_action_core(string $object_name, string $function_name, Request $request, Response $response): Response
    {
        $action_permissions = $object_name::get_permissions();
        $has_permissions = $this->authentication_service->user_has_permissions($action_permissions);

        if (!$has_permissions)
        {
            if ($this->authentication_service->is_authenticated())
            {
                throw new HttpForbiddenException($request);
            }

            throw new HttpUnauthorizedException($request);
        }

        $action_filter = $object_name::get_filter();
        $request = $this->validator_service->filter_request($request, $action_filter);

        $action_obj = new $object_name();

        $this->assert_service->verify(method_exists($action_obj, 'set_inputs'), "{$object_name}->set_inputs() does not exist");
        $action_obj->set_inputs($request);

        $this->assert_service->verify(method_exists($action_obj, $function_name), "Registered route function {$object_name}->{$function_name} does not exist");
        $action_obj->{$function_name}();

        exit();
    }
}
