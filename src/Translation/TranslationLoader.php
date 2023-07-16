<?php

namespace WebFramework\Translation;

interface TranslationLoader
{
    public function loadTranslation(string $language, string $category, string $tag, bool $requirePresence = false): string;

    /**
     * Return whole category.
     *
     * @return array<string, string>
     */
    public function loadCategory(string $language, string $category): array;
}
