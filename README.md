# Warning: this is a development version of the upcoming Prado 4.
Even if are trying hard to ensure that the framework is usable, your mileage may vary.
We encourage you to test it and to report any problem that you find.

# Prado PHP Framework

PRADO is a component-based and event-driven programming framework for developing Web applications in PHP 5 and 7.
PRADO stands for PHP Rapid Application Development Object-oriented.

[![Build Status](https://travis-ci.org/pradosoft/prado.png?branch=master)](https://travis-ci.org/pradosoft/prado)
[![Coverage Status](https://coveralls.io/repos/pradosoft/prado/badge.png?branch=master)](https://coveralls.io/r/pradosoft/prado?branch=master)
[![Total Downloads](https://poser.pugx.org/pradosoft/prado/downloads.png)](https://packagist.org/packages/pradosoft/prado)
[![Latest Stable Version](https://poser.pugx.org/pradosoft/prado/v/stable.png)](https://packagist.org/packages/pradosoft/prado)

[![Gitter](https://badges.gitter.im/pradosoft/prado.png)](https://gitter.im/pradosoft/prado?utm_source=badge&utm_medium=badge&utm_campaign=pr-badge&utm_content=badge)

## API Documentation
The complete API documentation can be found at http://pradosoft.github.io/docs/manual/

Prado uses ApiGen (http://www.apigen.org) to generate its API documentation.
An ApiGen configuration file is providen, to generate the documentation just execute
```./vendor/bin/apigen generate --config=.apigen.yaml```
The documentation will be generated in the `build/docs/` directory.

## Install

The best way to install Prado is [through composer](http://getcomposer.org).
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

#### Add Prado to an existing application
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
## Demo Apps
Several different example prado applications are provided in the https://github.com/pradosoft/prado-demos repository. You can see these applications running here: http://www.pradoframework.net/site/demos/ .
When you create your own prado application you do NOT need these folders.

## Testing

Prado uses phpunit (https://phpunit.de/) for unit testing and Selenium (http://www.seleniumhq.org/) for functional testing.
A phpunit configuration file is providen, to run the tests just execute
```./vendor/bin/phpunit --testsuite unit``` to run unit tests and
```./vendor/bin/phpunit --testsuite functional``` to run functional tests.

Test results will be saved in in the `build/tests/` directory.

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
* Write tests for your changes to ensure that later changes to prado won't break your code.
* Submit your pull request

