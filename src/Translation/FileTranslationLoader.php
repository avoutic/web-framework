<?php

/*
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Translation;

use WebFramework\Core\Cache;
use WebFramework\Core\RuntimeEnvironment;

/**
 * Class FileTranslationLoader.
 *
 * This class is responsible for loading translations from files and caching them.
 */
class FileTranslationLoader implements TranslationLoader
{
    /** @var array<string, array<string, array<string, string>>> */
    private array $translations = [];

    /**
     * FileTranslationLoader constructor.
     *
     * @param Cache              $cache              The cache service
     * @param RuntimeEnvironment $runtimeEnvironment The runtime environment
     * @param array<string>      $directories        The directories to search for translation files
     */
    public function __construct(
        private Cache $cache,
        private RuntimeEnvironment $runtimeEnvironment,
        /** @var array<string> */
        private array $directories,
    ) {}

    /**
     * Get translations for a specific language.
     *
     * @param string $language The language code
     *
     * @return array<string, array<string, string>> The translations
     */
    private function getTranslations(string $language): array
    {
        if (isset($this->translations[$language]))
        {
            return $this->translations[$language];
        }

        // Load from cache if present
        //
        $cacheId = "translations[{$language}]";
        if ($this->cache->exists($cacheId))
        {
            $this->translations[$language] = $this->cache->get($cacheId);

            return $this->translations[$language];
        }

        // Load from disk
        //
        $this->translations[$language] = $this->retrieveMergedLanguage($language);
        $this->cache->set($cacheId, $this->translations[$language], 10);

        return $this->translations[$language];
    }

    /**
     * Retrieve and merge translations for a specific language from all directories.
     *
     * @param string $language The language code
     *
     * @return array<string, array<string, string>> The merged translations
     */
    private function retrieveMergedLanguage(string $language): array
    {
        $mergedTranslations = [];

        foreach ($this->directories as $directory)
        {
            $translationsFile = "{$this->runtimeEnvironment->getAppDir()}/{$directory}/{$language}.php";
            if (!file_exists($translationsFile))
            {
                continue;
            }

            $translations = include $translationsFile;

            $mergedTranslations = array_replace_recursive($mergedTranslations, $translations);
        }

        return $mergedTranslations;
    }

    /**
     * Load a specific translation.
     *
     * @param string      $language         The language code
     * @param string      $category         The translation category
     * @param string      $tag              The translation tag
     * @param bool        $requirePresence  Whether to require the translation to be present
     * @param null|string $fallbackLanguage The fallback language code
     *
     * @return string The translated string
     *
     * @throws \RuntimeException If the translation is required but not found
     */
    public function loadTranslation(string $language, string $category, string $tag, bool $requirePresence = false, ?string $fallbackLanguage = 'en'): string
    {
        $translations = $this->getTranslations($language);

        if ($requirePresence)
        {
            if (!isset($translations[$category]))
            {
                throw new \RuntimeException("Unknown translation category '{$category}'");
            }

            if (!isset($translations[$category][$tag]))
            {
                throw new \RuntimeException("Unknown tag '{$tag}' in category '{$category}'");
            }
        }

        if (!isset($translations[$category]) || !isset($translations[$category][$tag]))
        {
            if ($fallbackLanguage !== null && $language !== $fallbackLanguage)
            {
                return $this->loadTranslation($fallbackLanguage, $category, $tag, false, null);
            }

            return "{$category}.{$tag}";
        }

        return $translations[$category][$tag];
    }

    /**
     * Load all translations for a specific category.
     *
     * @param string $language The language code
     * @param string $category The translation category
     *
     * @return array<string, string> The category translations
     *
     * @throws \RuntimeException If the category is not found
     */
    public function loadCategory(string $language, string $category): array
    {
        $translations = $this->getTranslations($language);

        if (!isset($translations[$category]))
        {
            throw new \RuntimeException("Unknown translation category '{$category}'");
        }

        return $translations[$category];
    }
}
