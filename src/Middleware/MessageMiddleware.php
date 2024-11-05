<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\MessageService;
use WebFramework\Core\ValidatorService;

/**
 * Middleware to handle messages passed via URL parameters.
 */
class MessageMiddleware implements MiddlewareInterface
{
    /**
     * @param MessageService   $messageService   The message service
     * @param ValidatorService $validatorService The validator service
     */
    public function __construct(
        private MessageService $messageService,
        private ValidatorService $validatorService,
    ) {}

    /**
     * Process an incoming server request.
     *
     * @param Request                 $request The request
     * @param RequestHandlerInterface $handler The handler
     */
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
