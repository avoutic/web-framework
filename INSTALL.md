# Installation

The installation of web-framework as your PHP framework of choice for your project requires you to take a number of steps.

## Composer

Installing WebFramework with composer:

```
composer require avoutic/web-framework
```

You can also start with a base project:

```
composer create-project avoutic/web-framework-example
```

## Installing additional prerequisites

web-framework depends on AdoDB as the database abstraction layer. It will be automatically installed when you install WebFramework with composer.

### Postmark

If you also want to support Postmark for assert handling and transactional mail, you should add:

```
composer require wildbit/postmark-php:^64.0
```

### Redis caching

To support the WebFramework\Core\RedisCache, you should make sure `ext-redis` is available and tsadd:

```
composer require cache/redis-adapter:^1.1
```

### Stripe

If you want first class support for Stripe interfacing:

```
composer require stripe/stripe-php:^7.128
```

## Apache

If you are using Apache, you should make sure that the following rewrite rules are either in the site configuration file or in _htdocs/.htaccess_:

```
RewriteEngine on

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /index.php [END,NC,QSA]
```

These rewrite conditions and rule make sure that Apache will call web-framework for any and all files and directories that do not exist as a static file in _htdocs/_.

In addition you will need to be able to rewrite requests. Assuming you use Apache as your main web server you can just enable the rewrite module from the command line:

```
a2enmod rewrite
```
