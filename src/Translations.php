<?php

use WebFramework\Core\ContainerWrapper;
use WebFramework\Translation\TranslationService;

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
function __(string $category, string $tag, array $params = [], bool $requirePresence = false): string
{
    $container = ContainerWrapper::get();

    $translationService = $container->get(TranslationService::class);

    return $translationService->translate($category, $tag, $params, $requirePresence);
}

/**
 * Get all translations for a specific category.
 *
 * @param string $category The translation category
 *
 * @return array<string, string> The category translations
 */
function __C(string $category): array
{
    $container = ContainerWrapper::get();

    $translationService = $container->get(TranslationService::class);

    return $translationService->getCategory($category);
}

/**
 * Get a filter string containing all keys of a category.
 *
 * @param string $category The translation category
 *
 * @return string A pipe-separated string of category keys
 */
function __F(string $category): string
{
    $container = ContainerWrapper::get();

    $translationService = $container->get(TranslationService::class);

    return $translationService->getFilter($category);
}
