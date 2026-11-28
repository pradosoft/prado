<?php

namespace Prado\Test\Unit\Harness\Autoload;

/**
 * A class that only Composer's autoloader loads. The Prado::using() tests reference it
 * nowhere else, so it is not loaded before them.
 */
class TUsingAutoloadClass
{
}
