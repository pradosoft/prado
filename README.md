# Prado PHP Framework

PRADO is a component-based and event-driven programming framework for developing Web applications in PHP 5 and 7.
PRADO stands for PHP Rapid Application Development Object-oriented.

[![Build Status](https://travis-ci.org/pradosoft/prado.png?branch=master)](https://travis-ci.org/pradosoft/prado)
[![Code Quality](https://scrutinizer-ci.com/g/pradosoft/prado/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/pradosoft/prado)
[![Coverage Status](https://coveralls.io/repos/pradosoft/prado/badge.png?branch=master)](https://coveralls.io/r/pradosoft/prado?branch=master)
[![Total Downloads](https://poser.pugx.org/pradosoft/prado/downloads.png)](https://packagist.org/packages/pradosoft/prado)
[![Latest Stable Version](https://poser.pugx.org/pradosoft/prado/v/stable.png)](https://packagist.org/packages/pradosoft/prado)
[![Gitter](https://badges.gitter.im/pradosoft/prado.png)](https://gitter.im/pradosoft/prado?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

PRADO is best suitable for creating Web applications that are highly user-interactive. It can be used to develop systems as simple as a blog system to those as complex as a content management system (CMS) or a complete e-commerce solution. Because PRADO promotes object-oriented programming (OOP) through its component-based methodology, it fits extremely well for team work and enterprise development. Its event-driven programming pattern helps developers gain better focus on business logic rather than distracted by various tedious and repetitive low-level coding handling.

PRADO comes with many features that can cut down development time significantly. In particular, it provides a rich set of pluggable Web controls, complete database support including both active record and complex object mapper, seamless AJAX support, theme and skin, internationalization and localization, various caching solutions, security measures, and many other features that are seldom found in other programming frameworks.

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

#### Add PRADO to an existing application
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
      "pradosoft/prado": "~4.0"
  }
}
```

The [asset-packagist](https://asset-packagist.org) repository is used to install javascript dependencies.
Assuming you already installed composer, run

```sh
composer install
```

Then you can include the autoloader, and you will have access to the library classes:

```php
<?php
require 'vendor/autoload.php';
```

## Quickstart Documentation

A great introduction to PRADO is available in the [Quickstart tutorial](http://www.pradoframework.net/demos/quickstart/).
The tutorial itself is a PRADO application included in the [demos](https://github.com/pradosoft/prado-demos)

## API Documentation

The complete API documentation can be found on the [API Manual](http://pradosoft.github.io/docs/manual/)

PRADO uses its own fork of ApiGen 4 (http://www.apigen.org) to generate its API documentation.
An ApiGen configuration file is providen, to generate the documentation just execute

```sh
composer gendoc
```

The documentation will be generated in the `build/docs/` directory.

## Demo Apps

Several different example PRADO applications are provided in the https://github.com/pradosoft/prado-demos repository. You can see these applications running here: http://www.pradoframework.net/site/demos/ .
When you create your own PRADO application you do NOT need these folders.

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

Starting point:

* Fork the repo
* Clone your repo
* Make your changes
* Write tests for your changes to ensure that later changes to PRADO won't break your code.
* Submit your pull request

## Testing

PRADO uses phpunit (https://phpunit.de/) for unit testing and Selenium (http://www.seleniumhq.org/) for functional testing.
A phpunit configuration file is providen, to run the tests just execute

```composer unittest``` to run unit tests and
```composer functionaltest``` to run functional tests.

Test results will be saved in in the `build/tests/` directory.
