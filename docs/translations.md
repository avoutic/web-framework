# Multi-Lingual Support

This document provides a guide for developers on how to deploy and configure multi-lingual support in an application using the WebFramework. The framework provides a robust translation system that allows you to manage translations for different languages.

## Overview

The WebFramework uses a combination of translation services and loaders to manage translations. The `TranslationService` is the main service responsible for providing translation functionality, while the `FileTranslationLoader` is used to load translations from files. This is all handled by the `BootstrapService`, so you don't have to worry about it.

## Setting Up Translations

### Step 1: Define Translation Files

Translation files are PHP files that return an associative array of translation keys and their corresponding translations. These files are typically organized by language and category.

#### Example Translation File

Create a file named `en.php` in your translations directory. For all messages in the WebFramework there is already a default translation in English.You can override default translations by using the same key in your translation file.

~~~php
<?php

return [
    'authenticator' => [
        'auth_required_message' => 'Authentication required. Please login.',
    ],
    'change_email' => [
        'duplicate' => 'E-mail address is already in use in another account',
        'success' => 'E-mail address changed successfully',
    ],
    // Add more categories and translations as needed
];
~~~

### Step 2: Configure Translation Directories

In your configuration file (e.g., `config.php`), specify the directories where translation files are located. This is done under the `translations` key.

For available configuration options and default settings, see `config/base_config.php`.

~~~php
return [
    // Other configuration settings...

    'translations' => [
        'default_language' => 'en',
        'directories' => [
            'translations', // Directory containing translation files
        ],
    ],
];
~~~

You can override the default language by setting the `default_language` in your configuration file.

### Step 3: Use the Translation Functions

The WebFramework provides helper functions `__()` and `__C()` to retrieve translations in your application. These functions use the `TranslationService` to fetch translations.

#### Example Usage

~~~php
// Translate a specific tag within a category
$message = __('notifications', 'hello_world');

// Get all translations for a specific category
$genderOptions = __C('genders');
~~~

In this example, the `__()` function is used to retrieve a specific translation, while the `__C()` function is used to retrieve all translations for a specific category. This can be useful when you need to retrieve all options for a selection input in your template.

## Handling Messages with Translations

The `MessageService` is used to manage messages in the application, including translating message keys into their corresponding translations.

### Adding Messages

When adding a message, you can specify a translation key instead of a plain text message. The `MessageService` will automatically translate the key using the `TranslationService`.

#### Example

~~~php
use WebFramework\Presentation\MessageService;

class ExampleAction
{
    public function __construct(
        private MessageService $messageService,
    ) {}

    public function execute(): void
    {
        $this->messageService->add('info', 'authenticator.auth_required_message');
    }
}
~~~

In this example, the `ExampleAction` adds a message using a translation key. The `MessageService` translates the key into the corresponding message.