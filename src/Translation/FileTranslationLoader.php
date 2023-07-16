<?php

namespace WebFramework\Translation;

use WebFramework\Core\Cache;

class FileTranslationLoader implements TranslationLoader
{
    /** @var array<string, array<string, array<string, string>>> */
    private array $translations = [];

    public function __construct(
        private Cache $cache,
        private string $appDir,
        /** @var array<string> */
        private array $directories,
    ) {
    }

    /**
     * @return array<string, array<string, string>>
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
     * @return array<string, array<string, string>>
     */
    private function retrieveMergedLanguage(string $language): array
    {
        $mergedTranslations = [];

        foreach ($this->directories as $directory)
        {
            $translationsFile = "{$this->appDir}/{$directory}/{$language}.php";
            if (!file_exists($translationsFile))
            {
                continue;
            }

            $translations = include $translationsFile;

            $mergedTranslations = array_replace_recursive($mergedTranslations, $translations);
        }

        return $mergedTranslations;
    }

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

            return $tag;
        }

        return $translations[$category][$tag];
    }

    /**
     * Return whole category.
     *
     * @return array<string, string>
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
