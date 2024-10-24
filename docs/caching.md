# Caching

This document provides a guide for developers on how to use caching in the WebFramework. Caching is used to store and retrieve data efficiently, reducing the need for repeated computations or database queries.

## Overview

Caching in WebFramework is managed using the `Cache` interface, which defines the contract for cache implementations. The framework provides two implementations:

- **RedisCache**: A Redis-based implementation for high-performance caching.
- **NullCache**: A no-op implementation that performs no caching, useful for testing or when caching is disabled.

## Using Caching in Your Application

To use caching in your application, you need to inject the `Cache` service and use its methods to store and retrieve data.

### Example Usage

~~~php
use WebFramework\Core\Cache;

class ExampleService
{
    public function __construct(
        private Cache $cache,
    ) {}

    public function getCachedData(string $key): mixed
    {
        if ($this->cache->exists($key)) {
            return $this->cache->get($key);
        }

        // Compute or retrieve the data
        $data = $this->computeData();

        // Store the data in the cache
        $this->cache->set($key, $data, 3600); // Cache for 1 hour

        return $data;
    }

    private function computeData(): mixed
    {
        // Perform some computation or data retrieval
        return 'computed data';
    }
}
~~~

In this example, the `ExampleService` uses the `Cache` service to check if data is cached, retrieve it if available, or compute and cache it if not.

## Enabling RedisCache in Dependency Injection

To enable `RedisCache` in your application, you need to configure it in your dependency injection container. This is typically done in your definition file.

### Example Configuration

~~~php
use WebFramework\Core\RedisCache;
use WebFramework\Core\Cache;

return [
    Cache::class => DI\autowire(RedisCache::class),
];
~~~

In this example, the `Cache` service is configured to use `RedisCache`.

## Providing an Alternative Caching Implementation

To provide an alternative caching implementation, you need to create a class that implements the `Cache` interface. This class should define the methods for storing, retrieving, and invalidating cache items.

### Example Custom Cache Implementation

~~~php
namespace App\Cache;

use WebFramework\Core\Cache;

class CustomCache implements Cache
{
    public function exists(string $path): bool
    {
        // Check if the item exists in the cache
    }

    public function get(string $path): mixed
    {
        // Retrieve the item from the cache
    }

    public function set(string $path, mixed $obj, ?int $expiresAfter = null): void
    {
        // Store the item in the cache
    }

    public function setWithTags(string $path, mixed $obj, array $tags, ?int $expiresAfter = null): void
    {
        // Store the item in the cache with tags
    }

    public function invalidate(string $path): void
    {
        // Invalidate the cache item
    }

    public function invalidateTags(array $tags): void
    {
        // Invalidate cache items with the given tags
    }

    public function flush(): void
    {
        // Flush the entire cache
    }
}
~~~

### Integrating the Custom Cache

Once you have implemented your custom cache, you need to register it in your [dependency injection container](dependency-injection.md).

~~~php
use App\Cache\CustomCache;
use WebFramework\Core\Cache;

return [
    Cache::class => DI\autowire(CustomCache::class),
];
~~~
