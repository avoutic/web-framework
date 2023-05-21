<?php

namespace WebFramework\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonParserMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandlerInterface $handler): Response
    {
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

        return $handler->handle($request);
    }
}
