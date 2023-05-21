<?php

namespace WebFramework\Core;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareStack implements RequestHandlerInterface
{
    /** @var \SplStack<MiddlewareInterface> */
    private \SplStack $stack;

    public function __construct(
        private RequestHandlerInterface $default_handler,
    ) {
        $this->stack = new \SplStack();
    }

    public function push(MiddlewareInterface $middleware): void
    {
        $this->stack->push($middleware);
    }

    public function handle(Request $request): Response
    {
        if ($this->stack->isEmpty())
        {
            return $this->default_handler->handle($request);
        }

        $middleware = $this->stack->pop();

        return $middleware->process($request, $this);
    }
}
