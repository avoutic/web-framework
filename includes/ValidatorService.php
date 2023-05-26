<?php

namespace WebFramework\Core;

use Psr\Http\Message\ServerRequestInterface as Request;

class ValidatorService
{
    public function __construct(
        private AssertService $assertService,
    ) {
    }

    /**
     * @param array<string, string> $filters
     */
    public function filterRequest(Request $request, array $filters): Request
    {
        $rawInputs = $request->getAttribute('raw_inputs', []);
        $inputs = $request->getAttribute('inputs', []);

        $results = $this->getFilterResults($request, $filters);

        $rawInputs = array_merge($rawInputs, $results['raw']);
        $inputs = array_merge($inputs, $results['filtered']);

        $request = $request->withAttribute('raw_inputs', $rawInputs);

        return $request->withAttribute('inputs', $inputs);
    }

    /**
     * @param array<string, string> $filters
     *
     * @return array{raw:array<string, mixed>, filtered:array<string, mixed>}
     */
    public function getFilterResults(Request $request, array $filters): array
    {
        $raw = [];
        $filtered = [];

        $queryParams = $request->getQueryParams();

        $parsedBody = $request->getParsedBody();
        $postParams = (is_array($parsedBody)) ? $parsedBody : [];

        $json = $request->getAttribute('json_data', []);
        $jsonParams = (is_array($json)) ? $json : [];

        foreach ($filters as $name => $regex)
        {
            $this->assertService->verify(strlen($regex), 'No regex provided');

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
        $results = $this->getFilterResults($request, $filters);

        return $results['filtered'];
    }
}
