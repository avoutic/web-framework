<?php

use WebFramework\Queue\MemoryQueue;
use WebFramework\Support\Image;
use WebFramework\Support\Webhook;

return [
    MemoryQueue::class => DI\autowire(MemoryQueue::class)
        ->constructorParameter('name', 'default'),
    Image::class => DI\autowire(Image::class)
        ->constructorParameter('location', 'image.png'),
    Webhook::class => DI\autowire()
        ->constructorParameter('url', 'https://example.com'),
];
