<?php

use Prado\TComponent;
use Prado\Util\Behaviors\TGlobalClassAware;


class TGlobalClassAwareTest extends PHPUnit\Framework\TestCase
{
	protected $behavior;

	protected function setUp(): void
	{
		$this->behavior = new TGlobalClassAware();
	}
	

	protected function tearDown(): void
	{
		$this->behavior = null;
	}
	
	public function testAttachDetach()
	{
		$name = 'globalclassaware';
		$component = new TComponent();

		// hasEventHandler('fx*') checks the *global* static handler list, which may
		// already contain entries from other tests.  Assert on the specific callable
		// instead so the test is not sensitive to global state from other test cases.
		$attachKey = [$component, 'fxAttachClassBehavior'];
		$detachKey = [$component, 'fxDetachClassBehavior'];

		self::assertNotContains($attachKey, $component->getEventHandlers('fxAttachClassBehavior')->toArray());
		self::assertNotContains($detachKey, $component->getEventHandlers('fxDetachClassBehavior')->toArray());

		$component->attachBehavior($name, $this->behavior);

		self::assertContains($attachKey, $component->getEventHandlers('fxAttachClassBehavior')->toArray());
		self::assertContains($detachKey, $component->getEventHandlers('fxDetachClassBehavior')->toArray());

		$component->detachBehavior($name);

		self::assertNotContains($attachKey, $component->getEventHandlers('fxAttachClassBehavior')->toArray());
		self::assertNotContains($detachKey, $component->getEventHandlers('fxDetachClassBehavior')->toArray());
	}
	
}
