# Prado PHP Framework

PRADO is a component-based and event-driven programming framework for developing Web applications in PHP 5.
PRADO stands for PHP Rapid Application Development Object-oriented.

[![Build Status](https://travis-ci.org/pradosoft/prado.png?branch=master)](https://travis-ci.org/comperio/pradosoft)

# A Word Of Warning

Prado migration to Github is still in progress, please refer to the original
 [issue tracker](https://code.google.com/p/prado3/issues/list) or the [forum](http://www.pradoframework.com/forum)
 until all issues have been migrated.

## API Documentation
The complete API documentation can be found at http://www.pradoframework.com/docs/manual/

## Install

The best way to install Prado is [through composer](http://getcomposer.org).

Just create a composer.json file for your project:

```JSON
{
    "require": {
        "comperio/prado": "~3.2"
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
