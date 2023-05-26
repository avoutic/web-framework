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
        private MessageService $messageService,
        private ValidatorService $validatorService,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $params = $this->validatorService->getFilteredParams(
            $request,
            [
                'msg' => '.*',
            ]
        );

        if (strlen($params['msg']))
        {
            $this->messageService->addFromUrl($params['msg']);
        }

        return $handler->handle($request);
    }
}
