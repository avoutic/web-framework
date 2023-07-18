<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\MessageService;
use WebFramework\Core\ValidatorService;
use WebFramework\Security\BlacklistService;
use WebFramework\Security\CsrfService;

class CsrfValidationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private BlacklistService $blacklistService,
        private CsrfService $csrfService,
        private MessageService $messageService,
        private ValidatorService $validatorService,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $params = $this->validatorService->getFilteredParams(
            $request,
            [
                'token' => '.*',
                'do' => 'yes|preview',
            ]
        );

        $inputs = $request->getAttribute('inputs', []);
        $inputs['do'] = $params['do'];

        if (strlen($params['do']))
        {
            if (!$this->csrfService->validateToken($params['token']))
            {
                $inputs['do'] = '';

                $ip = $request->getAttribute('ip');
                $userId = $request->getAttribute('user_id');

                $this->blacklistService->addEntry($ip, $userId, 'missing-csrf');
                $this->messageService->add('error', 'generic.csrf_missing');
            }
            else
            {
                $request = $request->withAttribute('passed_csrf', true);
            }
        }

        $request = $request->withAttribute('inputs', $inputs);

        return $handler->handle($request);
    }
}
