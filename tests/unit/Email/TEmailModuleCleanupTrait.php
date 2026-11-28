<?php

namespace Prado\Test\Unit\Email;

use Prado\Email\TEmailModule;
use Prado\Prado;
use Prado\Test\Unit\Email\Store\TSqliteEmailStore;

/**
 * Creates the email modules and stores of a test and releases them afterward.  A
 * module listens to global events while it is alive and PHPUnit keeps a test's
 * properties, so a module or store left listening would answer the events of later
 * tests and register its permissions a second time.
 */
trait TEmailModuleCleanupTrait
{
	/** @var \Prado\TComponent[] the modules and stores the test created. */
	private array $_emailComponents = [];

	protected function newEmailModule(): TEmailModule
	{
		return $this->_emailComponents[] = new TEmailModule();
	}

	protected function newEmailStore(int $time = 1700000000): TSqliteEmailStore
	{
		return $this->_emailComponents[] = new TSqliteEmailStore($time);
	}

	protected function tearDown(): void
	{
		$app = Prado::getApplication();
		$app->detachBehavior(TEmailModule::BEHAVIOR_NAME);
		foreach ($app->getEventHandlers('onEndRequest') as $handler) {
			if (is_array($handler) && $handler[0] instanceof TEmailModule) {
				$app->detachEventHandler('onEndRequest', $handler);
			}
		}
		foreach ($this->_emailComponents as $component) {
			$component->unlisten();
		}
		$this->_emailComponents = [];
	}
}
