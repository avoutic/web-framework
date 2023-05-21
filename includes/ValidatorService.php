<?php

namespace WebFramework\Core;

use Psr\Http\Message\ServerRequestInterface as Request;

class ValidatorService
{
    public function __construct(
        private AssertService $assert_service,
    ) {
    }

    /**
     * @param array<string, string> $filters
     */
    public function filter_request(Request $request, array $filters): Request
    {
        $raw_inputs = $request->getAttribute('raw_inputs', []);
        $inputs = $request->getAttribute('inputs', []);

        $results = $this->get_filter_results($request, $filters);

        $raw_inputs = array_merge($raw_inputs, $results['raw']);
        $inputs = array_merge($inputs, $results['filtered']);

        $request = $request->withAttribute('raw_inputs', $raw_inputs);

        return $request->withAttribute('inputs', $inputs);
    }

    /**
     * @param array<string, string> $filters
     *
     * @return array{raw:array<string, mixed>, filtered:array<string, mixed>}
     */
    public function get_filter_results(Request $request, array $filters): array
    {
        $raw = [];
        $filtered = [];

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
                $filtered[$name] = [];
                $raw[$name] = [];
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
                    $raw[$name][$key] = trim($value);
                    if (preg_match("/^\\s*{$regex}\\s*$/m", $value))
                    {
                        $filtered[$name][$key] = trim($value);
                    }
                }
            }
            else
            {
                $param = '';
                $filtered[$name] = '';

                if (isset($post_params[$name]))
                {
                    $param = $post_params[$name];
                }
                elseif (isset($query_params[$name]))
                {
                    $param = $query_params[$name];
                }

                $raw[$name] = trim($param);

                if (preg_match("/^\\s*{$regex}\\s*$/m", $param))
                {
                    $filtered[$name] = trim($param);
                }
            }
        }

        return [
            'raw' => $raw,
            'filtered' => $filtered,
        ];
    }

    /**
     * @param array<string, string> $filters
     *
     * @return array<string, mixed>
     */
    public function get_filtered_params(Request $request, array $filters): array
    {
        $results = $this->get_filter_results($request, $filters);

        return $results['filtered'];
    }
}
