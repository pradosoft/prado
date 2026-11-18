<?php

namespace Prado\Test\Unit\Email;

use Prado\Email\TEmailModule;
use Prado\Prado;

/**
 * Creates the email modules of a test and releases them afterward.  An email module
 * listens to global events and attaches to the application, so one left alive would
 * answer the events of later tests.
 */
trait TEmailModuleCleanupTrait
{
	/** @var TEmailModule[] the modules the test created. */
	private array $_emailModules = [];

	protected function newEmailModule(): TEmailModule
	{
		return $this->_emailModules[] = new TEmailModule();
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
		foreach ($this->_emailModules as $module) {
			$module->unlisten();
		}
		$this->_emailModules = [];
	}
}
