<?php

namespace WebFramework\Core;

use GuzzleHttp\Client;
use Http\Client\Common\EmulatedHttpAsyncClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\HttpClient\HttpClientFactoryInterface;
use Sentry\Options;
use Sentry\Serializer\PayloadSerializer;
use Sentry\Transport\HttpTransport;
use Sentry\Transport\TransportFactoryInterface;
use Sentry\Transport\TransportInterface;

/**
 * Class SentryClientFactory.
 *
 * Factory for creating Sentry client instances with custom configuration.
 */
class SentryClientFactory
{
    /**
     * Get a configured Sentry client instance.
     *
     * @param array<string, mixed> $options Configuration options for the Sentry client
     *
     * @return ClientInterface The configured Sentry client
     */
    public function get(array $options): ClientInterface
    {
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $httpClient = new EmulatedHttpAsyncClient(new Client());

        $httpClientFactory = new HttpClientFactory(
            Psr17FactoryDiscovery::findUriFactory(),
            Psr17FactoryDiscovery::findResponseFactory(),
            Psr17FactoryDiscovery::findStreamFactory(),
            $httpClient,
            'sentry.php',
            \Sentry\Client::SDK_VERSION
        );

        $transportFactory = new class($httpClientFactory) implements TransportFactoryInterface {
            public function __construct(
                private HttpClientFactoryInterface $clientFactory,
            ) {}

            public function create(Options $options): TransportInterface
            {
                return new HttpTransport(
                    $options,
                    $this->clientFactory->create($options),
                    Psr17FactoryDiscovery::findStreamFactory(),
                    Psr17FactoryDiscovery::findRequestFactory(),
                    new PayloadSerializer($options),
                );
            }
        };

        $builder = ClientBuilder::create($options);
        $builder->setTransportFactory($transportFactory);

        return $builder->getClient();
    }
}
