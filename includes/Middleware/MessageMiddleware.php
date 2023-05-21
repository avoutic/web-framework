<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\MessageService;
use WebFramework\Core\ValidatorService;

class MessageMiddleware implements MiddlewareInterface
{
    public function __construct(
        private MessageService $message_service,
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
            $this->message_service->add_from_url($params['msg']);
        }

        return $handler->handle($request);
    }
}
