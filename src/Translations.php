<?php

/**
 * @param array<string, string> $params
 */
function __(string $category, string $tag, array $params = [], bool $requirePresence = false): string
{
    global $container;

    $translationService = $container->get(WebFramework\Translation\TranslationService::class);

    return $translationService->translate($category, $tag, $params, $requirePresence);
}

/**
 * @return array<string, string>
 */
function __C(string $category): array
{
    global $container;

    $translationService = $container->get(WebFramework\Translation\TranslationService::class);

    return $translationService->getCategory($category);
}

function __F(string $category): string
{
    global $container;

    $translationService = $container->get(WebFramework\Translation\TranslationService::class);

    return $translationService->getFilter($category);
}
