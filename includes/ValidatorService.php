<?php

namespace WebFramework\Core;

use Psr\Http\Message\ServerRequestInterface as Request;

class ValidatorService
{
    /**
     * @param array<string, string> $filters
     */
    public function filterRequest(Request $request, array $filters): Request
    {
        $rawInputs = $request->getAttribute('raw_inputs', []);
        $inputs = $request->getAttribute('inputs', []);

        $params = $this->extractParams($request);

        $results = $this->getFilterResults($params, $filters);

        $rawInputs = array_merge($rawInputs, $results['raw']);
        $inputs = array_merge($inputs, $results['filtered']);

        $request = $request->withAttribute('raw_inputs', $rawInputs);

        return $request->withAttribute('inputs', $inputs);
    }

    /**
     * @param array{query: array<string, mixed>, post: array<string, mixed>, json: array<string, mixed>} $params
     * @param array<string, string>                                                                      $filters
     *
     * @return array{raw:array<string, mixed>, filtered:array<string, mixed>}
     */
    public function getFilterResults(array $params, array $filters): array
    {
        $raw = [];
        $filtered = [];

        $queryParams = $params['query'];
        $postParams = $params['post'];
        $jsonParams = $params['json'];

        foreach ($filters as $name => $regex)
        {
            if (!strlen($regex))
            {
                throw new \InvalidArgumentException('Zero-length regex provided');
            }

            if (substr($name, -2) == '[]')
            {
                $name = substr($name, 0, -2);

                // Expect multiple values
                //
                $filtered[$name] = [];
                $raw[$name] = [];
                $params = [];

                if (isset($jsonParams[$name]))
                {
                    $params = $jsonParams[$name];
                }
                elseif (isset($postParams[$name]))
                {
                    $params = $postParams[$name];
                }
                elseif (isset($queryParams[$name]))
                {
                    $params = $queryParams[$name];
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

                if (isset($jsonParams[$name]))
                {
                    $param = $jsonParams[$name];
                }
                elseif (isset($postParams[$name]))
                {
                    $param = $postParams[$name];
                }
                elseif (isset($queryParams[$name]))
                {
                    $param = $queryParams[$name];
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
    public function getFilteredParams(Request $request, array $filters): array
    {
        $params = $this->extractParams($request);

        $results = $this->getFilterResults($params, $filters);

        return $results['filtered'];
    }

    /**
     * @param array<string, string> $filters
     *
     * @return array{raw:array<string, mixed>, filtered:array<string, mixed>}
     */
    public function getParams(Request $request, array $filters): array
    {
        $params = $this->extractParams($request);

        return $results = $this->getFilterResults($params, $filters);
    }

    /**
     * @return array{query: array<string, mixed>, post: array<string, mixed>, json: array<string, mixed>}
     */
    private function extractParams(Request $request): array
    {
        $queryParams = $request->getQueryParams();

        $parsedBody = $request->getParsedBody();
        $postParams = (is_array($parsedBody)) ? $parsedBody : [];

        $json = $request->getAttribute('json_data', []);
        $jsonParams = (is_array($json)) ? $json : [];

        return [
            'query' => $queryParams,
            'post' => $postParams,
            'json' => $jsonParams,
        ];
    }
}
