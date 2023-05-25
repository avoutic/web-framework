<?php

namespace WebFramework\Core;

class CamelCaseAutoLoader
{
    private static ?CamelCaseAutoLoader $loader = null;

    /**
     * @var array<string, string>
     */
    protected array $wf_exceptions = [
    ];

    /**
     * @var array<string, string>
     */
    protected array $wf_namespaces = [
    ];

    public function __construct(
        private string $app_dir,
    ) {
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'autoload'], true, true);
    }

    public function register_exception(string $class, string $file): void
    {
        $this->wf_exceptions[$class] = $file;
    }

    public function register_namespace(string $namespace, string $relative_dir): void
    {
        $this->wf_namespaces[$namespace] = $relative_dir;
    }

    public function autoload(string $namespaced_class_name): void
    {
        $exploded = explode('\\', $namespaced_class_name);

        $namespace = '';
        if (count($exploded) > 1)
        {
            $namespace = implode('\\', array_slice($exploded, 0, -1));
        }

        $class_name = end($exploded);

        // Skip other namespaces
        //
        if (!isset($this->wf_namespaces[$namespace]))
        {
            return;
        }

        $dir = $this->wf_namespaces[$namespace];

        $this->try_load($dir, $class_name);

        if (str_ends_with($class_name, 'Factory'))
        {
            $this->try_load($dir, substr($class_name, 0, -7));
        }
    }

    protected function try_load(string $dir, string $class_name): void
    {
        // Convert Camelcase to lowercase underscores
        //
        $include_name = '';

        if (isset($this->wf_exceptions[$class_name]))
        {
            $include_name = $this->wf_exceptions[$class_name];
        }
        else
        {
            $include_name = preg_replace('/([a-z])([A-Z])/', '$1_$2', $class_name);
            if ($include_name === null)
            {
                exit("Cannot convert '{$class_name}' to CamelCase");
            }

            $include_name = strtolower($include_name);
        }

        $full_path = "{$this->app_dir}{$dir}{$include_name}.inc.php";
        if (file_exists($full_path))
        {
            include_once $full_path;

            return;
        }
    }

    public static function get_loader(string $app_dir): self
    {
        if (self::$loader === null)
        {
            self::$loader = new self($app_dir);
            self::$loader->register();
        }

        return self::$loader;
    }
}

global $app_dir;

CamelCaseAutoLoader::get_loader($app_dir);
