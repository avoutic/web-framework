<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Task;

use Psr\Container\ContainerInterface as Container;
use WebFramework\Config\ConfigService;
use WebFramework\Core\BootstrapService;

/**
 * Task for displaying the loaded definition files and their contents.
 */
class DefinitionsShowTask extends ConsoleTask
{
    private ?string $definitionKey = null;

    /**
     * DefinitionsShowTask constructor.
     *
     * @param BootstrapService $bootstrapService The bootstrap service
     * @param ConfigService    $configService    The configuration service
     * @param resource         $outputStream     The output stream
     */
    public function __construct(
        private BootstrapService $bootstrapService,
        private ConfigService $configService,
        private Container $container,
        private $outputStream = STDOUT
    ) {}

    /**
     * Set the definition key to display.
     *
     * @param string $key The key (class name or string identifier) to filter by
     */
    public function setDefinitionKey(string $key): void
    {
        $this->definitionKey = $key;
    }

    /**
     * Write a message to the output stream.
     *
     * @param string $message The message to write
     */
    private function write(string $message): void
    {
        fwrite($this->outputStream, $message);
    }

    public function getCommand(): string
    {
        return 'definitions:show';
    }

    public function getDescription(): string
    {
        return 'Display the loaded definition files and their contents';
    }

    public function getUsage(): string
    {
        return <<<'EOF'
        Show the currently loaded definition files and their contents.

        Usage:
        framework definitions:show [key]

        Examples:
        framework definitions:show
        framework definitions:show WebFramework\Config\ConfigService
        framework definitions:show app_dir
        EOF;
    }

    public function getArguments(): array
    {
        return [
            new TaskArgument('key', 'Class name or string key to filter definitions (e.g., WebFramework\Config\ConfigService or app_dir)', false, [$this, 'setDefinitionKey']),
        ];
    }

    /**
     * Load a definition file and return its contents.
     *
     * @param string $appDir   The application directory
     * @param string $filePath The file path (may be absolute or relative)
     *
     * @return array<string, mixed> The definitions array from the file
     */
    private function loadDefinitionFile(string $appDir, string $filePath): array
    {
        $isOptional = $filePath[0] === '?';
        if ($isOptional)
        {
            $filePath = substr($filePath, 1);
        }

        // Handle absolute paths (starting with /)
        if (str_starts_with($filePath, '/'))
        {
            $fullPath = "{$appDir}{$filePath}";
        }
        else
        {
            // Relative paths are relative to definitions directory
            $fullPath = "{$appDir}/definitions/{$filePath}";
        }

        if ($isOptional && !file_exists($fullPath))
        {
            return [];
        }

        if (!file_exists($fullPath))
        {
            throw new \RuntimeException("Definition file does not exist: {$fullPath}");
        }

        $definitions = require $fullPath;
        if (!is_array($definitions))
        {
            throw new \RuntimeException("Definition file does not return an array: {$fullPath}");
        }

        return $definitions;
    }

    /**
     * Merge definition arrays, with later arrays overriding earlier ones.
     *
     * @param array<string, mixed> $base     The base definitions
     * @param array<string, mixed> $override The definitions to merge on top
     *
     * @return array<string, mixed> The merged definitions
     */
    private function mergeDefinitions(array $base, array $override): array
    {
        foreach ($override as $key => $value)
        {
            $base[$key] = $value;
        }

        return $base;
    }

    /**
     * Try to resolve a closure using the container.
     *
     * @param \Closure $closure The closure to resolve
     *
     * @return null|mixed The resolved value, or null if resolution fails
     */
    private function tryResolveClosure(\Closure $closure): mixed
    {
        try
        {
            $reflection = new \ReflectionFunction($closure);
            $parameters = $reflection->getParameters();

            if (count($parameters) === 0)
            {
                $resolved = $closure();
            }
            else
            {
                // Try to resolve each parameter from the container
                $resolvedParams = [];
                foreach ($parameters as $parameter)
                {
                    $type = $parameter->getType();

                    // Skip if parameter has no type hint
                    if ($type === null)
                    {
                        // If no type hint and has default value, use default
                        if ($parameter->isDefaultValueAvailable())
                        {
                            $resolvedParams[] = $parameter->getDefaultValue();

                            continue;
                        }

                        // Can't resolve untyped parameter without default
                        return null;
                    }

                    // Handle union types and intersection types
                    if ($type instanceof \ReflectionUnionType || $type instanceof \ReflectionIntersectionType)
                    {
                        // For union/intersection types, can't resolve safely
                        return null;
                    }

                    // Must be ReflectionNamedType at this point
                    if (!($type instanceof \ReflectionNamedType))
                    {
                        return null;
                    }

                    // Get the type name (class name for class types)
                    $typeName = $type->getName();

                    // Try to resolve from container by class name
                    if (class_exists($typeName) || interface_exists($typeName))
                    {
                        if ($this->container->has($typeName))
                        {
                            $resolvedParams[] = $this->container->get($typeName);

                            continue;
                        }
                    }

                    // If parameter has default value, use it
                    if ($parameter->isDefaultValueAvailable())
                    {
                        $resolvedParams[] = $parameter->getDefaultValue();

                        continue;
                    }

                    // Can't resolve this parameter
                    return null;
                }

                $resolved = $closure(...$resolvedParams);
            }

            // Return scalar values, arrays, or null (arrays will be processed recursively)
            if (is_scalar($resolved) || is_array($resolved) || $resolved === null)
            {
                return $resolved;
            }

            // Return null for objects to indicate we can't serialize it directly
            return null;
        }
        catch (\Throwable $e)
        {
            // If resolution fails, return null to indicate we can't resolve it
            return null;
        }
    }

    /**
     * Extract class name and constructor parameters from autowire definition.
     *
     * @param object              $object            The object to search
     * @param null|string         $foundClassName    Output parameter for found class name
     * @param array<mixed, mixed> $constructorParams Output parameter for constructor parameters
     */
    private function extractAutowireInfo(object $object, ?string &$foundClassName, array &$constructorParams): void
    {
        try
        {
            $reflection = new \ReflectionObject($object);

            // Check all properties including parent class properties
            $properties = $reflection->getProperties();
            $parentClass = $reflection->getParentClass();
            if ($parentClass)
            {
                $properties = array_merge($properties, $parentClass->getProperties());
            }

            foreach ($properties as $property)
            {
                $property->setAccessible(true);

                try
                {
                    $propValue = $property->getValue($object);
                }
                catch (\Throwable $e)
                {
                    // Skip properties that can't be accessed
                    continue;
                }

                // Check if it's a string that looks like a class name
                if (is_string($propValue) && (class_exists($propValue) || interface_exists($propValue)) && $foundClassName === null)
                {
                    $foundClassName = $propValue;
                }

                // Check if it's an array that might be constructor parameters
                // AutowireDefinitionHelper stores constructor params in the $constructor property
                if (is_array($propValue) && !empty($propValue) && empty($constructorParams))
                {
                    // Check if it looks like constructor parameters (associative array with string keys)
                    $hasStringKeys = false;
                    foreach (array_keys($propValue) as $key)
                    {
                        if (is_string($key))
                        {
                            $hasStringKeys = true;

                            break;
                        }
                    }

                    if ($hasStringKeys)
                    {
                        $constructorParams = $propValue;
                    }
                }
            }
        }
        catch (\Throwable $e)
        {
            // Ignore reflection errors and continue
        }
    }

    /**
     * Format a DI helper object to show meaningful information.
     *
     * @param object $value The DI helper object
     *
     * @return null|mixed Formatted representation or null if not a recognized DI helper
     */
    private function formatDiHelper(object $value): mixed
    {
        $className = get_class($value);

        // Handle DI\Definition\Helper\AutowireDefinitionHelper
        if (str_contains($className, 'AutowireDefinitionHelper'))
        {
            try
            {
                $foundClassName = null;
                $constructorParams = [];

                // Use introspection to extract class name and constructor parameters
                $this->extractAutowireInfo($value, $foundClassName, $constructorParams);

                // Build result structure
                if ($foundClassName && empty($constructorParams))
                {
                    // Simple case: just autowire with class name
                    return ['DI\autowire' => $foundClassName];
                }

                $result = [];

                if ($foundClassName)
                {
                    $result['class'] = $foundClassName;
                }

                // Process constructor parameters if any
                if (!empty($constructorParams))
                {
                    $processedParams = [];
                    foreach ($constructorParams as $paramName => $paramValue)
                    {
                        $processedParams[$paramName] = $this->makeSerializable($paramValue);
                    }
                    $result['constructorParameters'] = $processedParams;
                }

                return ['DI\autowire' => $result];
            }
            catch (\Throwable $e)
            {
                return ['DI\autowire' => '...'];
            }
        }

        // Handle DI\Definition\Helper\GetDefinitionHelper
        if (str_contains($className, 'GetDefinitionHelper'))
        {
            try
            {
                $reflection = new \ReflectionObject($value);
                foreach ($reflection->getProperties() as $property)
                {
                    $property->setAccessible(true);
                    $propValue = $property->getValue($value);

                    if (is_string($propValue))
                    {
                        return ['DI\get' => $propValue];
                    }
                }

                return ['DI\get' => '...'];
            }
            catch (\Throwable $e)
            {
                return ['DI\get' => '...'];
            }
        }

        // Handle DI\Definition\Helper\CreateDefinitionHelper
        if (str_contains($className, 'CreateDefinitionHelper'))
        {
            return ['DI\create' => '...'];
        }

        // Handle DI\Definition\Helper\StringDefinitionHelper
        if (str_contains($className, 'StringDefinitionHelper'))
        {
            try
            {
                $reflection = new \ReflectionObject($value);
                foreach ($reflection->getProperties() as $property)
                {
                    $property->setAccessible(true);
                    $propValue = $property->getValue($value);

                    if (is_string($propValue))
                    {
                        return ['DI\string' => $propValue];
                    }
                }

                return ['DI\string' => '...'];
            }
            catch (\Throwable $e)
            {
                return ['DI\string' => '...'];
            }
        }

        // Handle DI\Definition\Helper\FactoryDefinitionHelper
        if (str_contains($className, 'FactoryDefinitionHelper'))
        {
            return ['DI\factory' => '[Closure]'];
        }

        return null;
    }

    /**
     * Convert definitions to a JSON-serializable format by replacing non-serializable values.
     *
     * @param mixed $value The value to convert
     *
     * @return mixed The converted value
     */
    private function makeSerializable(mixed $value): mixed
    {
        if (is_object($value))
        {
            // Handle Closure objects - try to resolve them if they return simple values or arrays
            if ($value instanceof \Closure)
            {
                $resolved = $this->tryResolveClosure($value);
                if ($resolved !== null)
                {
                    // If resolved to an array, recursively process it to handle nested closures/objects
                    if (is_array($resolved))
                    {
                        return $this->makeSerializable($resolved);
                    }

                    return $resolved;
                }

                return '[Closure]';
            }

            // Handle DI helper objects - try to extract meaningful information
            $diHelperInfo = $this->formatDiHelper($value);
            if ($diHelperInfo !== null)
            {
                return $diHelperInfo;
            }

            // Handle DI helper objects - try to get a string representation
            if (method_exists($value, '__toString'))
            {
                return (string) $value;
            }

            // For other objects, show the class name
            $className = get_class($value);

            return "[object: {$className}]";
        }

        if (is_array($value))
        {
            $result = [];
            foreach ($value as $key => $item)
            {
                $result[$key] = $this->makeSerializable($item);
            }

            return $result;
        }

        return $value;
    }

    public function execute(): void
    {
        $this->bootstrapService->skipSanityChecks();
        $this->bootstrapService->bootstrap();

        try
        {
            $appDir = $this->container->get('app_dir');
            $definitionFiles = $this->configService->get('definition_files');

            $allDefinitions = [];
            foreach ($definitionFiles as $file)
            {
                $fileDefinitions = $this->loadDefinitionFile($appDir, $file);
                $allDefinitions = $this->mergeDefinitions($allDefinitions, $fileDefinitions);
            }

            // Filter by key if specified
            if ($this->definitionKey !== null)
            {
                if (!array_key_exists($this->definitionKey, $allDefinitions))
                {
                    $this->write("Error: Definition key '{$this->definitionKey}' not found.".PHP_EOL);

                    return;
                }

                $allDefinitions = [$this->definitionKey => $allDefinitions[$this->definitionKey]];
            }

            // Convert non-serializable values (closures, DI objects) to descriptive strings
            $serializableDefinitions = $this->makeSerializable($allDefinitions);

            // Sort the definitions by key
            ksort($serializableDefinitions);

            $json = json_encode($serializableDefinitions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

            if ($json === false)
            {
                $this->write('Unable to encode definitions.'.PHP_EOL);

                return;
            }

            $this->write($json.PHP_EOL);
        }
        catch (\RuntimeException $e)
        {
            $this->write('Error: '.$e->getMessage().PHP_EOL);

            return;
        }
    }

    public function handlesOwnBootstrapping(): bool
    {
        return true;
    }
}
