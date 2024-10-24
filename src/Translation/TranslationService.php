<?php

namespace WebFramework\Translation;

class TranslationService
{
    public function __construct(
        private TranslationLoader $loader,
        private string $language = 'en',
    ) {}

    /**
     * @param array<string, string> $params
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
     * Returns a whole category.
     *
     * @return array<string, string>
     */
    public function getCategory(string $category): array
    {
        return $this->loader->loadCategory($this->language, $category);
    }

    /**
     * Return category keys as filter.
     */
    public function getFilter(string $category): string
    {
        $keys = array_keys($this->getCategory($category));

        return implode('|', $keys);
    }

    public function tagExists(string $category, string $tag): bool
    {
        $translation = $this->loader->loadTranslation($this->language, $category, $tag);

        return ($translation !== "{$category}.{$tag}");
    }
}
