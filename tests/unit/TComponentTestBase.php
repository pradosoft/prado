<?php

require_once __DIR__ . '/TComponentTestFixtures.php';

use Prado\TComponent;

/**
 * Abstract base class for TComponent test suites.
 *
 * All tests have been split into focused suites:
 *   - TComponentTestBase.php              — abstract base (setUp / tearDown)
 *   - TComponentTestFixtures.php          — shared fixture classes
 *   - TComponentGlobalEventsTest.php      — global-event listen / unlisten
 *   - TComponentClassBehaviorTest.php     — attachClassBehavior / detachClassBehavior
 *   - TComponentBehaviorTest.php          — instance behaviors (attach, detach, enable, disable)
 *   - TComponentCallTest.php              — __call() / __callStatic() dispatch
 *   - TComponentPropertyTest.php          — property get / set / isset / unset
 *   - TComponentEventTest.php             — on-event system (attach, detach, raise)
 *   - TComponentDynamicTest.php           — dy* / fx* dynamic & global events
 *   - TComponentSerializationTest.php     — clone and wakeup
 *
 * Provides the shared setUp / tearDown and helper state used by every split
 * test class.  All concrete test classes extend this rather than TestCase
 * directly so that the global-event cleanup and behavior-leak assertion run
 * consistently for every test.
 */
abstract class TComponentTestBase extends PHPUnit\Framework\TestCase
{
	/** @var array<callable> Closures executed in tearDown to reverse side-effects. */
	protected $tearDownScripts = [];

	/**
	 * Running counter used when attaching anonymous (null / numeric) class
	 * behaviors so that tearDown can detach them by their resolved index name.
	 */
	protected $anonymousClassIndex = 0;

	/** @var \NewComponent The primary component under test. */
	protected $component;

	protected function setUp(): void
	{
		// Clear any lingering global class-behavior handlers before each test.
		$component = new TComponent();
		$component->getEventHandlers('fxAttachClassBehavior')->clear();
		$component->getEventHandlers('fxDetachClassBehavior')->clear();
		unset($component);

		$this->tearDownScripts = [];
		$this->component = new NewComponent();
	}

	protected function tearDown(): void
	{
		// Closures may interact with $this->component, so run them first.
		foreach ($this->tearDownScripts as $closure) {
			$closure();
		}
		$this->tearDownScripts = [];
		$this->component = null;

		// Assert that no class behaviors leaked out of the test.
		$component = new NewComponent();
		$this->assertEquals([], $component->getBehaviors());
	}
}
