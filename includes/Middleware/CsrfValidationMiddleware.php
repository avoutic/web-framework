<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WebFramework\Core\MessageService;
use WebFramework\Core\Security\BlacklistService;
use WebFramework\Core\Security\CsrfService;
use WebFramework\Core\ValidatorService;

class CsrfValidationMiddleware implements MiddlewareInterface
{
    public function __construct(
        private BlacklistService $blacklist_service,
        private CsrfService $csrf_service,
        private MessageService $message_service,
        private ValidatorService $validator_service,
    ) {
    }

    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
        $params = $this->validator_service->get_filtered_params(
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
            if (!$this->csrf_service->validate_token($params['token']))
            {
                $inputs['do'] = '';

                $ip = $request->getAttribute('ip');
                $user_id = $request->getAttribute('user_id');

                $this->blacklist_service->add_entry($ip, $user_id, 'missing-csrf');
                $this->message_service->add('error', 'CSRF token missing, possible attack', '');
            }
        }

        $request = $request->withAttribute('inputs', $inputs);

        return $handler->handle($request);
    }
}
