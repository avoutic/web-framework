<?php

namespace Tests\Unit;

use Codeception\Test\Unit;
use WebFramework\Core\RuntimeEnvironment;
use WebFramework\Presentation\MessageService;
use WebFramework\Support\UrlBuilder;

/**
 * @internal
 *
 * @covers \WebFramework\Support\UrlBuilder
 */
final class UrlBuilderTest extends Unit
{
    public function testBuildQueryUrlSimple()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('/test', [], ['param' => 'value']);

        verify($result)->equals('/app/test?param=value');
    }

    public function testBuildQueryUrlWithTemplate()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('/user/{id}/profile', ['id' => 123], ['tab' => 'settings']);

        verify($result)->equals('/app/user/123/profile?tab=settings');
    }

    public function testBuildQueryUrlMissingTemplateValue()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->makeEmpty(RuntimeEnvironment::class);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        verify(function () use ($urlBuilder) {
            $urlBuilder->buildQueryUrl('/user/{id}/profile', [], []);
        })->callableThrows(\InvalidArgumentException::class, 'Missing value for URL placeholder: id');
    }

    public function testBuildQueryUrlWithMessage()
    {
        $messageService = $this->make(MessageService::class, [
            'getForUrl' => 'encoded_message',
        ]);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('/test', [], [], 'success', 'Operation completed');

        verify($result)->equals('/app/test?msg=encoded_message');
    }

    public function testBuildQueryUrlWithExistingQuery()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('/test?existing=param', [], ['new' => 'value']);

        verify($result)->equals('/app/test?existing=param&new=value');
    }

    public function testBuildQueryUrlWithFragment()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('/test#section', [], ['param' => 'value']);

        verify($result)->equals('/app/test?param=value#section');
    }

    public function testBuildQueryUrlAbsolute()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
            'getHttpMode' => 'https',
            'getServerName' => 'example.com',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('/test', [], ['param' => 'value'], null, null, null, true);

        verify($result)->equals('https://example.com/app/test?param=value');
    }

    public function testBuildQueryUrlWithNullTemplate()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('', [], ['param' => 'value']);

        verify($result)->equals('/app?param=value');
    }

    public function testBuildUrl()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildUrl('/test', ['id' => 123]);

        verify($result)->equals('/app/test');
    }

    public function testBuildUrlWithMessage()
    {
        $messageService = $this->make(MessageService::class, [
            'getForUrl' => 'encoded_message',
        ]);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildUrl('/test', [], 'error', 'Failed', 'Try again');

        verify($result)->equals('/app/test?msg=encoded_message');
    }

    public function testGetServerUrl()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getHttpMode' => 'https',
            'getServerName' => 'api.example.com',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->getServerUrl();

        verify($result)->equals('https://api.example.com');
    }

    public function testBuildQueryUrlMultipleTemplateValues()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('/user/{userId}/post/{postId}', [
            'userId' => 123,
            'postId' => 456,
        ], ['view' => 'full']);

        verify($result)->equals('/app/user/123/post/456?view=full');
    }

    public function testBuildQueryUrlEmptyQueryParameters()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('/test', [], []);

        verify($result)->equals('/app/test');
    }

    public function testBuildQueryUrlSpecialCharactersInValues()
    {
        $messageService = $this->makeEmpty(MessageService::class);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('/search/{term}', [
            'term' => 'hello world',
        ], ['filter' => 'type=user&active=true']);

        verify($result)->equals('/app/search/hello%20world?filter=type%3Duser%26active%3Dtrue');
    }

    public function testBuildQueryUrlWithExtraMessage()
    {
        $messageService = $this->make(MessageService::class, [
            'getForUrl' => 'encoded_message',
        ]);
        $runtimeEnvironment = $this->make(RuntimeEnvironment::class, [
            'getBaseUrl' => '/app',
        ]);

        $urlBuilder = new UrlBuilder($messageService, $runtimeEnvironment);

        $result = $urlBuilder->buildQueryUrl('/test', [], [], 'info', 'Info message', 'Extra details');

        verify($result)->equals('/app/test?msg=encoded_message');
    }

    private function buildUrlBuilder(): UrlBuilder
    {
        return new UrlBuilder(
            $this->makeEmpty(MessageService::class),
            $this->make(RuntimeEnvironment::class, ['getBaseUrl' => '/app']),
        );
    }

    public function testNoQuestionMarkWithoutQuery()
    {
        $urlBuilder = $this->buildUrlBuilder();

        verify($urlBuilder->buildQueryUrl('/test'))->equals('/app/test');
        verify($urlBuilder->buildQueryUrl('/test#section'))->equals('/app/test#section');
    }

    public function testExistingQueryIsKeptWithoutATrailingAmpersand()
    {
        $urlBuilder = $this->buildUrlBuilder();

        verify($urlBuilder->buildQueryUrl('/test?page=2'))->equals('/app/test?page=2');
        verify($urlBuilder->buildQueryUrl('/test?page=2', [], ['sort' => 'name']))->equals('/app/test?page=2&sort=name');
        verify($urlBuilder->buildQueryUrl('/test?page=2#top', [], ['sort' => 'name']))->equals('/app/test?page=2&sort=name#top');
    }

    public function testPlaceholderValuesAreUrlEncoded()
    {
        $urlBuilder = $this->buildUrlBuilder();

        verify($urlBuilder->buildQueryUrl('/user/{name}', ['name' => 'John Doe']))->equals('/app/user/John%20Doe');
        verify($urlBuilder->buildQueryUrl('/file/{path}', ['path' => 'docs/intro']))->equals('/app/file/docs%2Fintro');

        // A value cannot add a query or a fragment of its own
        verify($urlBuilder->buildQueryUrl('/search/{term}', ['term' => 'a?b=c#d&e'], ['page' => 1]))
            ->equals('/app/search/a%3Fb%3Dc%23d%26e?page=1')
        ;
        verify($urlBuilder->buildQueryUrl('/go?to={target}', ['target' => 'x&admin=1']))->equals('/app/go?to=x%26admin%3D1');
    }
}
