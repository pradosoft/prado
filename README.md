# Prado PHP Framework

PRADO is a component-based and event-driven programming framework for developing Web applications in PHP 5.
PRADO stands for PHP Rapid Application Development Object-oriented.

[![Build Status](https://travis-ci.org/pradosoft/prado.png?branch=master)](https://travis-ci.org/pradosoft/prado)
[![Coverage Status](https://coveralls.io/repos/pradosoft/prado/badge.png?branch=master)](https://coveralls.io/r/pradosoft/prado?branch=master)
[![Dependencies Status](https://d2xishtp1ojlk0.cloudfront.net/d/8499593)](http://depending.in/pradosoft/prado)
[![Total Downloads](https://poser.pugx.org/pradosoft/prado/downloads.png)](https://packagist.org/packages/pradosoft/prado)
[![Latest Stable Version](https://poser.pugx.org/pradosoft/prado/v/stable.png)](https://packagist.org/packages/pradosoft/prado)

## A Word Of Warning

Prado migration to Github has just been completed, if you find any problem please open an issue here or on the [forum](http://www.pradoframework.com/forum).

## API Documentation
The complete API documentation can be found at http://www.pradoframework.com/docs/manual/

## Install

The best way to install Prado is [through composer](http://getcomposer.org).

Just create a composer.json file for your project:

```JSON
{
    "require": {
        "pradosoft/prado": "~3.2"
    }
}
```

Then you can run these two commands to install it:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar install

or simply run `composer install` if you have have already [installed the composer globally](http://getcomposer.org/doc/00-intro.md#globally).

Then you can include the autoloader, and you will have access to the library classes:

```php
<?php
require 'vendor/autoload.php';
```
