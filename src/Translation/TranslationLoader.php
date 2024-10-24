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
 * Interface TranslationLoader.
 *
 * This interface defines the contract for translation loaders.
 */
interface TranslationLoader
{
    /**
     * Load a specific translation.
     *
     * @param string $language        The language code
     * @param string $category        The translation category
     * @param string $tag             The translation tag
     * @param bool   $requirePresence Whether to require the translation to be present
     *
     * @return string The translated string
     */
    public function loadTranslation(string $language, string $category, string $tag, bool $requirePresence = false): string;

    /**
     * Load all translations for a specific category.
     *
     * @param string $language The language code
     * @param string $category The translation category
     *
     * @return array<string, string> The category translations
     */
    public function loadCategory(string $language, string $category): array;
}
