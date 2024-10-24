<?php

/**
 * This file is part of WebFramework.
 *
 * (c) Avoutic <avoutic@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace WebFramework\Translation;

/**
 * Class TranslationService.
 *
 * This service provides translation functionality using a TranslationLoader.
 */
class TranslationService
{
    /**
     * TranslationService constructor.
     *
     * @param TranslationLoader $loader   The translation loader
     * @param string            $language The default language code
     */
    public function __construct(
        private TranslationLoader $loader,
        private string $language = 'en',
    ) {}

    /**
     * Translate a specific tag within a category.
     *
     * @param string                $category        The translation category
     * @param string                $tag             The translation tag
     * @param array<string, string> $params          Parameters to replace in the translation
     * @param bool                  $requirePresence Whether to require the translation to be present
     *
     * @return string The translated string with parameters replaced
     */
    public function translate(string $category, string $tag, array $params = [], bool $requirePresence = false): string
    {
        $translation = $this->loader->loadTranslation($this->language, $category, $tag, $requirePresence);

        foreach ($params as $key => $value)
        {
            $translation = str_replace('{'.$key.'}', $value, $translation);
        }

        return $translation;
    }

    /**
     * Get all translations for a specific category.
     *
     * @param string $category The translation category
     *
     * @return array<string, string> The category translations
     */
    public function getCategory(string $category): array
    {
        return $this->loader->loadCategory($this->language, $category);
    }

    /**
     * Get a filter string containing all keys of a category.
     *
     * @param string $category The translation category
     *
     * @return string A pipe-separated string of category keys
     */
    public function getFilter(string $category): string
    {
        $keys = array_keys($this->getCategory($category));

        return implode('|', $keys);
    }

    /**
     * Check if a translation tag exists in a category.
     *
     * @param string $category The translation category
     * @param string $tag      The translation tag
     *
     * @return bool True if the tag exists, false otherwise
     */
    public function tagExists(string $category, string $tag): bool
    {
        $translation = $this->loader->loadTranslation($this->language, $category, $tag);

        return ($translation !== "{$category}.{$tag}");
    }
}
