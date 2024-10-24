<?php

use WebFramework\Core\ContainerWrapper;
use WebFramework\Translation\TranslationService;

/**
 * @param array<string, string> $params
 */
function __(string $category, string $tag, array $params = [], bool $requirePresence = false): string
{
    $container = ContainerWrapper::get();

    $translationService = $container->get(TranslationService::class);

    return $translationService->translate($category, $tag, $params, $requirePresence);
}

/**
 * @return array<string, string>
 */
function __C(string $category): array
{
    $container = ContainerWrapper::get();

    $translationService = $container->get(TranslationService::class);

    return $translationService->getCategory($category);
}

function __F(string $category): string
{
    $container = ContainerWrapper::get();

    $translationService = $container->get(TranslationService::class);

    return $translationService->getFilter($category);
}
