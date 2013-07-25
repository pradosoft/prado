# Prado PHP Framework

Git mirror for Prado3 (https://code.google.com/p/prado3/)

# Warning!

This is a mirror with some small but core changes needed for working into *ClavisNG*.  
Comperio does not provide support for this framework, please refer to the original [issue tracker](https://code.google.com/p/prado3/issues/list).

[![Build Status](https://secure.travis-ci.org/nicmart/DomainSpecificQuery.png?branch=master)](http://travis-ci.org/nicmart/DomainSpecificQuery)

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
