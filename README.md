# Prado PHP Framework

Git mirror for Prado3 (https://code.google.com/p/prado3/)

# A Word Of Warning

This is a Prado unofficial mirror to get it into [Packagist](https://packagist.org/).
_At the moment_ it's synced and updated with Prado branch *3.2* (currently developed),
 read below for the list of applied patches, if any.

[Comperio](http://www.comperio.it) does not provide support for this framework, please refer to the original
 [issue tracker](https://code.google.com/p/prado3/issues/list) or the [forum](http://www.pradoframework.com/forum).

[![Build Status](https://travis-ci.org/comperio/prado.png?branch=master)](https://travis-ci.org/comperio/prado)

## Patches applied in this mirror

* None, at the moment

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
