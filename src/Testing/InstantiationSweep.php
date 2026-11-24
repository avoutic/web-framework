<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Testing;

use Composer\Autoload\ClassLoader;
use Psr\Container\ContainerInterface as Container;

/**
 * Class InstantiationSweep.
 *
 * Tries to get every class of a set of namespaces from the container. Use it in a test to
 * find classes that cannot be instantiated, for example because a constructor parameter has
 * no definition, before a request runs into it.
 *
 * It has no dependency on a test framework. Classes are found through the PSR-4 map of the
 * Composer autoloader.
 */
class InstantiationSweep
{
    /**
     * InstantiationSweep constructor.
     *
     * @param Container $container The container to get the classes from
     */
    public function __construct(
        private Container $container,
    ) {}

    /**
     * Try to instantiate the classes of the given namespaces.
     *
     * Interfaces, traits, enums and abstract classes are left out.
     *
     * @param array<string>       $namespaces The namespaces to sweep, without a leading or trailing backslash
     * @param array<class-string> $skip       Classes that should not be tried, such as value objects and events
     * @param bool                $recursive  Whether to include the classes of sub namespaces
     */
    public function run(array $namespaces, array $skip = [], bool $recursive = false): InstantiationSweepResult
    {
        $instantiated = [];
        $failed = [];
        $skipped = [];
        $emptyNamespaces = [];

        foreach ($namespaces as $namespace)
        {
            $namespace = trim($namespace, '\\');
            $classes = $this->findClasses($namespace, $recursive);

            if (!count($classes))
            {
                $emptyNamespaces[] = $namespace;

                continue;
            }

            foreach ($classes as $class)
            {
                if (in_array($class, $instantiated, true) || isset($failed[$class]) || in_array($class, $skipped, true))
                {
                    continue;
                }

                if (!(new \ReflectionClass($class))->isInstantiable())
                {
                    continue;
                }

                if (in_array($class, $skip, true))
                {
                    $skipped[] = $class;

                    continue;
                }

                try
                {
                    $this->container->get($class);

                    $instantiated[] = $class;
                }
                catch (\Throwable $e)
                {
                    $failed[$class] = $e->getMessage();
                }
            }
        }

        return new InstantiationSweepResult($instantiated, $failed, $skipped, $emptyNamespaces);
    }

    /**
     * Find the classes, interfaces, traits and enums of a namespace.
     *
     * @return array<class-string> The names that exist, sorted
     */
    private function findClasses(string $namespace, bool $recursive): array
    {
        $classes = [];

        foreach ($this->getDirectories($namespace) as $directory)
        {
            foreach ($this->findPhpFiles($directory, $recursive) as $relativeFile)
            {
                $class = $namespace.'\\'.str_replace('/', '\\', substr($relativeFile, 0, -4));

                if (class_exists($class) || interface_exists($class) || trait_exists($class) || enum_exists($class))
                {
                    $classes[] = $class;
                }
            }
        }

        $classes = array_values(array_unique($classes));
        sort($classes);

        return $classes;
    }

    /**
     * Get the directories of a namespace, according to the PSR-4 map of Composer.
     *
     * @return array<string>
     */
    private function getDirectories(string $namespace): array
    {
        $directories = [];

        foreach (ClassLoader::getRegisteredLoaders() as $loader)
        {
            foreach ($loader->getPrefixesPsr4() as $prefix => $paths)
            {
                $prefix = trim($prefix, '\\');

                if ($prefix !== '' && $namespace !== $prefix && !str_starts_with($namespace, $prefix.'\\'))
                {
                    continue;
                }

                $rest = ($prefix === '') ? $namespace : substr($namespace, strlen($prefix));
                $subPath = str_replace('\\', '/', trim($rest, '\\'));

                foreach ($paths as $path)
                {
                    $directory = rtrim($path, '/').($subPath === '' ? '' : '/'.$subPath);

                    if (is_dir($directory))
                    {
                        $directories[] = $directory;
                    }
                }
            }
        }

        return array_values(array_unique($directories));
    }

    /**
     * Find the PHP files in a directory.
     *
     * @return array<string> The paths of the files, relative to the directory
     */
    private function findPhpFiles(string $directory, bool $recursive): array
    {
        $files = [];

        foreach ((array) scandir($directory) as $entry)
        {
            if ($entry === false || $entry === '.' || $entry === '..')
            {
                continue;
            }

            $path = $directory.'/'.$entry;

            if (is_dir($path))
            {
                if ($recursive)
                {
                    foreach ($this->findPhpFiles($path, true) as $file)
                    {
                        $files[] = $entry.'/'.$file;
                    }
                }

                continue;
            }

            if (str_ends_with($entry, '.php'))
            {
                $files[] = $entry;
            }
        }

        return $files;
    }
}
