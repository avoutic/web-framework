<?php

namespace WebFramework\Core;

use Psr\Http\Message\ServerRequestInterface;

class ValidatorService
{
    public function __construct(
        private AssertService $assert_service,
    ) {
    }

    /**
     * @param array<string, string> $filters
     */
    public function filter_request(ServerRequestInterface $request, array $filters): ServerRequestInterface
    {
        $raw_inputs = $request->getAttribute('raw_inputs', []);
        $inputs = $request->getAttribute('inputs', []);

        $query_params = $request->getQueryParams();
        $post_params = [];

        $parsed_body = $request->getParsedBody();
        if (is_array($parsed_body))
        {
            $post_params = $parsed_body;
        }

        foreach ($filters as $name => $regex)
        {
            $this->assert_service->verify(strlen($regex), 'No regex provided');

            if (substr($name, -2) == '[]')
            {
                $name = substr($name, 0, -2);

                // Expect multiple values
                //
                $inputs[$name] = [];
                $raw_inputs[$name] = [];
                $params = [];

                if (isset($post_params[$name]))
                {
                    $params = $post_params[$name];
                }
                elseif (isset($query_params[$name]))
                {
                    $params = $query_params[$name];
                }

                foreach ($params as $key => $value)
                {
                    $raw_inputs[$name][$key] = trim($value);
                    if (preg_match("/^\\s*{$regex}\\s*$/m", $value))
                    {
                        $inputs[$name][$key] = trim($value);
                    }
                }
            }
            else
            {
                $param = '';
                $inputs[$name] = '';

                if (isset($post_params[$name]))
                {
                    $param = $post_params[$name];
                }
                elseif (isset($query_params[$name]))
                {
                    $param = $query_params[$name];
                }

                $raw_inputs[$name] = trim($param);

                if (preg_match("/^\\s*{$regex}\\s*$/m", $param))
                {
                    $inputs[$name] = trim($param);
                }
            }
        }

        $request = $request->withAttribute('raw_inputs', $raw_inputs);

        return $request->withAttribute('inputs', $inputs);
    }
}
