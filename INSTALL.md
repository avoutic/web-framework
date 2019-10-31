# Installation

The installation of web-framework as your PHP framework of choice for your project requires you to take a number of steps.

## Submodule

If there's enough interest, I'll make a composer package available as well. For now you can add web-framework as a git submodule.

```
git submodule add https://github.com/avoutic/web-framework
```

## Installing prerequisites

web-framework depends on AdoDB as the database abstraction layer.

You can use composer to install the requirements as follows:

```
composer require adodb/adodb-php
```

In addition you will need to be able to rewrite requests. Assuming you use Apache as your main web server you can just enable the rewrite module from the command line:

```
a2enmod rewrite
```

## Structure and linking

Now that all prerequisites are in place, we can setup our default directory structure and link in the core framework file.

```
mkdir -p data htdocs frames includes templates views
cd htdocs && ln -s ../web-framework/htdocs/index.php .
```

If you now browse to the website's URL, you should see a 'Requirements Error'. This means the framework is in place, but you have not yet provided it with your configuration, etc.

## Setting up an example site

Let's set up a simple hello world page.

You will need to have a database available for web-framework.
To set up the base database structure you can use the example template provided.

Assuming you are using MySQL/MariaDB:

```
mysql -u USER -p DB < web-framework/examples/scheme_v1.sql
```

Then we use the example hello_world files to set up your first configuration file, frame file, view file, template file and routing logic.

```
cp -a web-framework/hello_world_example/* .
```

Make sure you adjust the configuration file (_includes/config.php_) with the right credentials for your database.

Now reload your page and voila. You should see 'Hello World'.
