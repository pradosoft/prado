# Prado PHP Framework

PRADO is a component-based and event-driven programming framework for developing Web applications in PHP.
PRADO stands for PHP Rapid Application Development Object-oriented.

[![Build Status](https://github.com/pradosoft/prado/actions/workflows/prado.yml/badge.svg)](https://github.com/pradosoft/prado/actions/workflows/prado.yml)
[![Code Quality](https://scrutinizer-ci.com/g/pradosoft/prado/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pradosoft/prado)
[![Code Coverage](https://scrutinizer-ci.com/g/pradosoft/prado/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/pradosoft/prado/?branch=master)
[![Total Downloads](https://poser.pugx.org/pradosoft/prado/downloads.png)](https://packagist.org/packages/pradosoft/prado)
[![Latest Stable Version](https://poser.pugx.org/pradosoft/prado/v/stable.png)](https://packagist.org/packages/pradosoft/prado)

PRADO is best suitable for creating Web applications that are highly user-interactive. It can be used to develop systems as simple as a blog system to those as complex as a content management system (CMS) or a complete e-commerce solution. Because PRADO promotes object-oriented programming (OOP) through its component-based methodology, it fits extremely well for team work and enterprise development. Its event-driven programming pattern helps developers gain better focus on business logic rather than distracted by various tedious and repetitive low-level coding handling.

PRADO comes with many features that can cut down development time significantly. In particular, it provides a rich set of pluggable Web controls, complete database support including both active record and complex object mapper, behavior aware objects for expanding core functionality, seamless AJAX support, theme and skin, internationalization and localization, various caching solutions, security measures, object extensions with behaviors, composer integration with plugins, cron task manager, industry standard RBAC permissions, command line application interface, and many other features.

The PRADO framework and the included demos are free software. They are released under the terms of the [LICENSE](https://github.com/pradosoft/prado/blob/master/LICENSE).

## Install

The best way to install PRADO is [through composer](http://getcomposer.org).
If you don't use composer yet, first install it:
```sh
# download composer.phar
curl -s http://getcomposer.org/installer | php
# install it globally on the system
mv composer.phar /usr/local/bin/composer
```

Then, create the application structure using composer:
```sh
composer create-project pradosoft/prado-app app
```

The application will be installed in the "app" directory.

#### Built-in PHP Test Web Server

The built-in PHP Test Web Server can be used to immediately start developing and testing a web application.
The web server is started with command (assuming the above application in the directory "app"):

```sh
cd app/protected
./vendor/bin/prado-cli http
```

The application is then accessible on the machine's browser at `http://127.0.0.1:8080/`.  The built-in web server is enabled when the application is in "Debug" mode or is enabled in the application configuration parameters.

### Add PRADO to an existing application

Just create a composer.json file for your project:

```JSON
{
  "repositories": [
    {
      "type": "composer",
      "url": "https://asset-packagist.org"
    }
  ],
    "require": {
      "pradosoft/prado": "^4"
  }
}
```

The [asset-packagist](https://asset-packagist.org) repository is used to install javascript dependencies.
Assuming you already installed composer, run the command:

```sh
composer install
```

Then you can include the autoloader, and you will have access to the library classes:

```php
<?php
require 'vendor/autoload.php';
```

### PRADO Composer Extensions

PRADO 4.2 supports Composer extensions to plug in new functionality directly into a PRADO application; such as new web controls, API integration, authorization, new services, analytics, custom behaviors, and CLI commands.  3rd party extensions can be found at [Packagist](https://packagist.org/?query=prado4&type=prado4-extension).  The community is encouraged to write and submit 3rd party PRADO 4 extensions for others to find and use.

PRADO provides a rich set of features for runtime extension of an application with composer extensions.  Behaviors modify existing functionality and add new properties and methods to instanced run-time objects.  Class-wide behaviors add default behaviors to classes, interfaces, and traits when instancing new objects and to "listen"ing instances.  "Global Events" enable extensions to communicate with other extensions without knowledge of application configuration.  "Dynamic Events" enable objects to communicate with their attached behaviors with direct function calls.

The example PRADO Composer extension has its own manifest, Module, web page, and error messages.  It can be included in your application with the following command:

```sh
composer require --prefer-dist pradosoft/prado-composer-extension "*"
```

Once downloaded and installed in composer, an extension is activated by adding the Composer Extension Name as a new module ID in the application configuration file application.xml or application.php. To include the example extension "pradosoft/prado-composer-extension", the PRADO configuration would include this xml:

```xml
<modules>
  ...
  <module id="pradosoft/prado-composer-extension" PropertyA='value1' />
</modules>
```

When using a Composer Extension as a module id, the class for the module is embedded in the Composer Extension Manifest and does not need to be specified.

## Documentation

A great introduction to PRADO is available in the [Quickstart tutorial](http://www.pradoframework.net/demos/quickstart/).
The tutorial itself is a PRADO application included in the [demos](https://github.com/pradosoft/prado-demos)
The complete API documentation can be found on the [API Manual](http://pradosoft.github.io/docs/manual/)

## Demo Apps

Several different example PRADO applications are provided in the https://github.com/pradosoft/prado-demos repository.
You can see these applications running here: http://www.pradoframework.net/site/demos/ .

## Integration with your favorite IDE/editor

Plugins providing syntax highlighting and code snippets can be found at https://github.com/pradosoft/editor-plugins

## Contributing

In the spirit of free software, **everyone** is encouraged to help improve this project.

Here are some ways *you* can contribute:

* by using prerelease versions
* by reporting bugs
* by writing specifications
* by writing code (*no patch is too small*: fix typos, add comments, clean up inconsistent whitespace)
* by refactoring code
* by resolving issues
* by reviewing patches
* by writing your own composer extension for PRADO and posting it on [Packagist](https://packagist.org/)
* by supporting and contributing to your favorite composer PRADO extensions

Starting point:

* Fork the repo
* Clone your repo
* Make your changes
* Write tests for your changes to ensure that later changes to PRADO won't break your code.
* Submit your pull request

## Testing

PRADO uses phpunit (https://phpunit.de/) for unit testing and Selenium (http://www.seleniumhq.org/) for functional testing.

In order to run tests, first clone the PRADO repository and have composer install the needed development libraries:
```
git clone https://github.com/pradosoft/prado.git`
cd prado
composer install
```

For functional tests only, you need to manually download and run an app called [Selenium Server](https://www.selenium.dev/downloads/).
It's a java application, so you'll need to install a [JRE/JDK](https://java.com/) and then run it from a terminal:

```
java -jar selenium-server-4.x.x.jar standalone
```
Depending on the browser you want to use to do functional testing, you may need an [additional driver](https://www.selenium.dev/documentation/en/webdriver/driver_requirements/).


Now you are redy to run tests; a phpunit configuration file is providen, to run the tests just execute

```composer unittest``` to run unit tests and
```composer functionaltest``` to run functional tests.

Test results will be saved in in the `build/tests/` directory.

## Generatting the API documentation

PRADO uses phpDocumentor 3 (https://github.com/phpDocumentor/phpDocumentor) to generate its API documentation.
A phpdoc.dist.xml configuration file is provided, to generate the documentation just execute:

```sh
composer gendoc
```

The documentation will be generated in the `build/docs/` directory.