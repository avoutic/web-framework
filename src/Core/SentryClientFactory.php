<?php

namespace WebFramework\Core;

use Http\Client\Common\EmulatedHttpAsyncClient;
use Http\Discovery\Psr17FactoryDiscovery;
use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Sentry\HttpClient\HttpClientFactory;
use Sentry\HttpClient\HttpClientFactoryInterface;
use Sentry\Serializer\PayloadSerializer;
use Sentry\Transport\HttpTransport;
use Sentry\Transport\TransportFactoryInterface;

class SentryClientFactory
{
    /**
     * @param array<string, mixed> $options
     */
    public function get(array $options): ClientInterface
    {
        $streamFactory = Psr17FactoryDiscovery::findStreamFactory();
        $httpClient = new EmulatedHttpAsyncClient(new \GuzzleHttp\Client());

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
            ) {
            }

            public function create(\Sentry\Options $options): \Sentry\Transport\TransportInterface
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
