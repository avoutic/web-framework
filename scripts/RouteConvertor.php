<?php

class RouteConvertor
{
    public function __construct(
        private string $collectorName = 'app',
        private string $prefix = '',
    ) {
    }

    /**
     * @param array<string> $variables
     */
    public function registerRoute(string $route, string $ignore, string $handler, array $variables = []): void
    {
        // Split the route definition
        list($method, $path) = explode(' ', $route);

        // If the prefix is set, remove it from the path
        if ($this->prefix && str_starts_with($path, $this->prefix.'/'))
        {
            $path = substr($path, strlen($this->prefix));
        }

        // Split the handler definition
        list($class, $function) = explode('.', $handler);

        // Replace backslashes with forward slashes for the class name
        $class = str_replace('\\', '\\\\', $class);

        // Build the path with the variables
        foreach ($variables as $variable)
        {
            $pattern = '/\(([^)]+)\)/'; // matches anything inside parenthesis
            $replacement = '{'.$variable.':$1}';
            $path = preg_replace($pattern, $replacement, $path, 1);
        }

        // Create the line to be outputted
        $output = '$'.$this->collectorName.'->'.strtolower($method)."('".$path."', [".$class."::class, '".$function."']);".PHP_EOL;

        // Print out the result
        echo $output;
    }
}
