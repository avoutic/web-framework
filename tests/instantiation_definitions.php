<?php

return [
    WebFramework\Queue\MemoryQueue::class => DI\autowire(WebFramework\Queue\MemoryQueue::class)
        ->constructorParameter('name', 'default'),
    WebFramework\Support\Image::class => DI\autowire(WebFramework\Support\Image::class)
        ->constructorParameter('location', 'image.png'),
    WebFramework\Support\StoredValuesService::class => DI\autowire()
        ->constructorParameter('module', 'db'),
    WebFramework\Support\StoredUserValuesService::class => DI\autowire()
        ->constructorParameter('module', 'account'),
    WebFramework\Support\Webhook::class => DI\autowire()
        ->constructorParameter('url', 'https://example.com'),
];
