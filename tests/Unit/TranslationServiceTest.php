<?php

namespace Tests\Unit;

use Codeception\Stub\Expected;
use Codeception\Test\Unit;
use WebFramework\Translation\TranslationLoader;
use WebFramework\Translation\TranslationService;

/**
 * @internal
 *
 * @covers \WebFramework\Translation\TranslationService
 */
final class TranslationServiceTest extends Unit
{
    public function testTranslateBasic()
    {
        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadTranslation' => Expected::once(function ($language, $category, $tag, $requirePresence) {
                    verify($language)->equals('en');
                    verify($category)->equals('test');
                    verify($tag)->equals('hello');
                    verify($requirePresence)->equals(false);

                    return 'Hello World';
                }),
            ]
        );

        $service = new TranslationService($loader);
        $result = $service->translate('test', 'hello');

        verify($result)->equals('Hello World');
    }

    public function testTranslateWithParams()
    {
        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadTranslation' => Expected::once('Hello {name}, welcome to {place}'),
            ]
        );

        $service = new TranslationService($loader);
        $result = $service->translate('test', 'welcome', ['name' => 'John', 'place' => 'Paris']);

        verify($result)->equals('Hello John, welcome to Paris');
    }

    public function testTranslateWithMultipleParams()
    {
        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadTranslation' => Expected::once('{a} {b} {c}'),
            ]
        );

        $service = new TranslationService($loader);
        $result = $service->translate('test', 'multi', ['a' => '1', 'b' => '2', 'c' => '3']);

        verify($result)->equals('1 2 3');
    }

    public function testTranslateWithRequirePresence()
    {
        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadTranslation' => Expected::once(function ($language, $category, $tag, $requirePresence) {
                    verify($requirePresence)->equals(true);

                    return 'Required translation';
                }),
            ]
        );

        $service = new TranslationService($loader);
        $result = $service->translate('test', 'required', [], true);

        verify($result)->equals('Required translation');
    }

    public function testTranslateWithCustomLanguage()
    {
        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadTranslation' => Expected::once(function ($language, $category, $tag, $requirePresence) {
                    verify($language)->equals('fr');

                    return 'Bonjour';
                }),
            ]
        );

        $service = new TranslationService($loader, 'fr');
        $result = $service->translate('test', 'hello');

        verify($result)->equals('Bonjour');
    }

    public function testGetCategory()
    {
        $expectedCategory = [
            'key1' => 'Value 1',
            'key2' => 'Value 2',
            'key3' => 'Value 3',
        ];

        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadCategory' => Expected::once(function ($language, $category) use ($expectedCategory) {
                    verify($language)->equals('en');
                    verify($category)->equals('test');

                    return $expectedCategory;
                }),
            ]
        );

        $service = new TranslationService($loader);
        $result = $service->getCategory('test');

        verify($result)->equals($expectedCategory);
    }

    public function testGetCategoryWithCustomLanguage()
    {
        $expectedCategory = [
            'key1' => 'Valeur 1',
            'key2' => 'Valeur 2',
        ];

        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadCategory' => Expected::once(function ($language, $category) use ($expectedCategory) {
                    verify($language)->equals('fr');

                    return $expectedCategory;
                }),
            ]
        );

        $service = new TranslationService($loader, 'fr');
        $result = $service->getCategory('test');

        verify($result)->equals($expectedCategory);
    }

    public function testGetFilter()
    {
        $category = [
            'key1' => 'Value 1',
            'key2' => 'Value 2',
            'key3' => 'Value 3',
        ];

        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadCategory' => Expected::once($category),
            ]
        );

        $service = new TranslationService($loader);
        $result = $service->getFilter('test');

        verify($result)->equals('key1|key2|key3');
    }

    public function testGetFilterWithSingleKey()
    {
        $category = [
            'single' => 'Value',
        ];

        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadCategory' => Expected::once($category),
            ]
        );

        $service = new TranslationService($loader);
        $result = $service->getFilter('test');

        verify($result)->equals('single');
    }

    public function testGetFilterWithEmptyCategory()
    {
        $category = [];

        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadCategory' => Expected::once($category),
            ]
        );

        $service = new TranslationService($loader);
        $result = $service->getFilter('test');

        verify($result)->equals('');
    }

    public function testTagExistsWhenTagExists()
    {
        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadTranslation' => Expected::once('Actual translation'),
            ]
        );

        $service = new TranslationService($loader);
        $result = $service->tagExists('test', 'exists');

        verify($result)->equals(true);
    }

    public function testTagExistsWhenTagDoesNotExist()
    {
        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadTranslation' => Expected::once('test.missing'),
            ]
        );

        $service = new TranslationService($loader);
        $result = $service->tagExists('test', 'missing');

        verify($result)->equals(false);
    }

    public function testTagExistsWithCustomLanguage()
    {
        $loader = $this->makeEmpty(
            TranslationLoader::class,
            [
                'loadTranslation' => Expected::once(function ($language, $category, $tag) {
                    verify($language)->equals('fr');

                    return 'Traduction réelle';
                }),
            ]
        );

        $service = new TranslationService($loader, 'fr');
        $result = $service->tagExists('test', 'exists');

        verify($result)->equals(true);
    }
}
