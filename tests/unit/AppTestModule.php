<?php

namespace Prado\Test\Unit;

use Prado\TModule;

/**
 * A minimal concrete module for use in module-management tests.
 * TModule is abstract, so we need a concrete subclass.
 */
class AppTestModule extends TModule
{
	public function init($config) {}
}
