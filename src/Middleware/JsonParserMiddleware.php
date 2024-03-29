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
        $contentType = $request->getHeaderLine('Content-Type');
        $isJson = (str_contains($contentType, 'application/json'));
        $request = $request->withAttribute('is_json', $isJson);

        if ($isJson)
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
