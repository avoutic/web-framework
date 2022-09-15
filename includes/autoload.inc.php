<?php
namespace WebFramework\Core;

class WFAutoLoader
{
    private static ?WFAutoLoader $loader = null;

    /**
     * @var array<string, string>
     */
    protected array $wf_exceptions = array(
        'DBManager' => 'db_manager',
        'WF' => 'wf_core',
        'WFHelpers' => 'wf_helpers',
        'WFSecurity' => 'wf_security',
        'WFWebHandler' => 'wf_web_handler',
    );

    /**
     * @var array<string, string>
     */
    protected array $wf_namespaces = array(
        'WebFramework\\Core' => '/',
        'WebFramework\\Actions' => '/../actions/',
    );

    public function register(): void
    {
        spl_autoload_register(array($this, 'autoload'), true, true);
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
        $exploded = explode("\\", $namespaced_class_name);

        $namespace = '';
        if (count($exploded) > 1)
            $namespace = implode("\\", array_slice($exploded, 0, -1));

        $class_name = end($exploded);

        // Skip other namespaces
        //
        if (!isset($this->wf_namespaces[$namespace]))
            return;

        $dir = $this->wf_namespaces[$namespace];

        $this->try_load($dir, $class_name);

        if (str_ends_with($class_name, 'Factory'))
            $this->try_load($dir, substr($class_name, 0, -7));
    }

    protected function try_load(string $dir, string $class_name): void
    {
        // Convert Camelcase to lowercase underscores
        //
        $include_name = '';

        if (isset($this->wf_exceptions[$class_name]))
            $include_name = $this->wf_exceptions[$class_name];
        else
        {
            $include_name = preg_replace('/([a-z])([A-Z])/', '$1_$2', $class_name);
            if ($include_name === null)
                die("Cannot convert '{$class_name}' to CamelCase");

            $include_name = strtolower($include_name);
        }

        $full_path = __DIR__."{$dir}{$include_name}.inc.php";
        if (file_exists($full_path))
        {
            include_once($full_path);
            return;
        }
    }

    static function get_loader(): WFAutoLoader
    {
        if (self::$loader === null)
        {
            self::$loader = new WFAutoLoader();
            self::$loader->register();

        }

        return self::$loader;
    }
}

WFAutoLoader::get_loader();
?>
