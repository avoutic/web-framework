<?php

namespace WebFramework\Core;

class CamelCaseAutoLoader
{
    private static ?CamelCaseAutoLoader $loader = null;

    /**
     * @var array<string, string>
     */
    protected array $wfExceptions = [
    ];

    /**
     * @var array<string, string>
     */
    protected array $wfNamespaces = [
    ];

    public function __construct(
        private string $appDir,
    ) {
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'autoload'], true, true);
    }

    public function registerException(string $class, string $file): void
    {
        $this->wfExceptions[$class] = $file;
    }

    public function registerNamespace(string $namespace, string $relativeDir): void
    {
        $this->wfNamespaces[$namespace] = $relativeDir;
    }

    public function autoload(string $namespacedClassName): void
    {
        $exploded = explode('\\', $namespacedClassName);

        $namespace = '';
        if (count($exploded) > 1)
        {
            $namespace = implode('\\', array_slice($exploded, 0, -1));
        }

        $className = end($exploded);

        // Skip other namespaces
        //
        if (!isset($this->wfNamespaces[$namespace]))
        {
            return;
        }

        $dir = $this->wfNamespaces[$namespace];

        $this->tryLoad($dir, $className);

        if (str_ends_with($className, 'Factory'))
        {
            $this->tryLoad($dir, substr($className, 0, -7));
        }
    }

    protected function tryLoad(string $dir, string $className): void
    {
        // Convert Camelcase to lowercase underscores
        //
        $includeName = '';

        if (isset($this->wfExceptions[$className]))
        {
            $includeName = $this->wfExceptions[$className];
        }
        else
        {
            $includeName = preg_replace('/([a-z])([A-Z])/', '$1_$2', $className);
            if ($includeName === null)
            {
                exit("Cannot convert '{$className}' to CamelCase");
            }

            $includeName = strtolower($includeName);
        }

        $fullPath = "{$this->appDir}{$dir}{$includeName}.inc.php";
        if (file_exists($fullPath))
        {
            include_once $fullPath;

            return;
        }
    }

    public static function getLoader(string $appDir): self
    {
        if (self::$loader === null)
        {
            self::$loader = new self($appDir);
            self::$loader->register();
        }

        return self::$loader;
    }
}

global $appDir;

CamelCaseAutoLoader::getLoader($appDir);
