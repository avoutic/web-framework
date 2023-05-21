<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\Security\ProtectService;
use WebFramework\Core\ValidatorService;

class MessageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ProtectService $protect_service,
        private ValidatorService $validator_service,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $params = $this->validator_service->get_filtered_params(
            $request,
            [
                'msg' => '.*',
            ]
        );

        if (strlen($params['msg']))
        {
            $msg = $this->protect_service->decode_and_verify_array($params['msg']);

            $messages = $request->getAttribute('messages', []);
            $messages[] = $msg;
            $request = $request->withAttribute('messages', $messages);
        }

        return $handler->handle($request);
    }
}
