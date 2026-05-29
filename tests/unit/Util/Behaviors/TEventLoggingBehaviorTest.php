<?php

/**
 * TEventLoggingBehaviorTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

use Prado\Prado;
use Prado\TComponent;
use Prado\TEventParameter;
use Prado\Util\Behaviors\TEventLoggingBehavior;
use Prado\Util\TBehavior;
use Prado\Util\TLogger;

// ── Helper classes ────────────────────────────────────────────────────────────

/**
 * Exposes the protected _getZappableSleepProps for testing.
 */
class TEventLoggingBehaviorAccessor extends TEventLoggingBehavior
{
	public function pubGetZappableSleepProps(array &$exprops): void
	{
		$this->_getZappableSleepProps($exprops);
	}
}

/**
 * A minimal TComponent with two known on* events for attach/fire testing.
 */
class TEventLoggingOwner extends TComponent
{
	public function onAlpha(TEventParameter $param): void
	{
		$this->raiseEvent('OnAlpha', $this, $param);
	}

	public function onBeta(TEventParameter $param): void
	{
		$this->raiseEvent('OnBeta', $this, $param);
	}
}

/**
 * A TBehavior that contributes one additional on* event to its owner.
 */
class TEventLoggingBehaviorWithEvent extends TBehavior
{
	public function onGamma(TEventParameter $param): void
	{
		$this->raiseEvent('OnGamma', $this, $param);
	}
}

/**
 * A TBehavior that contributes no on* events — used to verify the skip path.
 */
class TEventLoggingBehaviorNoEvents extends TBehavior
{
	public function doSomething(): void {}
}

/**
 * A second TBehavior that also has onGamma — used to test event survival
 * when a peer behavior is detached.
 */
class TEventLoggingBehaviorWithGamma extends TBehavior
{
	public function onGamma(TEventParameter $param): void
	{
		$this->raiseEvent('OnGamma', $this, $param);
	}
}

/**
 * A TBehavior that contributes a unique onDelta event not present anywhere else.
 */
class TEventLoggingBehaviorWithDelta extends TBehavior
{
	public function onDelta(TEventParameter $param): void
	{
		$this->raiseEvent('OnDelta', $this, $param);
	}
}

// ── Test class ────────────────────────────────────────────────────────────────

/**
 * TEventLoggingBehaviorTest class.
 *
 * Tests TEventLoggingBehavior: construction, accessors (including string-coercion
 * for XML config compatibility), dynamic on* event discovery via reflection,
 * attach/detach lifecycle, logEvent() filter logic, __dycall() logging,
 * dyAttachBehavior/dyDetachBehavior live-tracking, and serialization via
 * _getZappableSleepProps (transient, default-excluded, and non-default-retained
 * fields).
 *
 * @package Prado\Tests\Unit\Util\Behaviors
 */
class TEventLoggingBehaviorTest extends PHPUnit\Framework\TestCase
{
	/** @var TEventLoggingBehaviorAccessor */
	private TEventLoggingBehaviorAccessor $behavior;

	/** @var TEventLoggingOwner */
	private TEventLoggingOwner $owner;

	protected function setUp(): void
	{
		$this->behavior = new TEventLoggingBehaviorAccessor();
		$this->owner = new TEventLoggingOwner();
	}

	protected function tearDown(): void
	{
		if ($this->behavior->hasOwner()) {
			$this->owner->detachBehavior('logger');
		}
	}

	// ── Construction / instance ───────────────────────────────────────────────

	public function testIsInstanceOfTEventLoggingBehavior(): void
	{
		$this->assertInstanceOf(TEventLoggingBehavior::class, $this->behavior);
	}

	public function testIsInstanceOfTBehavior(): void
	{
		$this->assertInstanceOf(TBehavior::class, $this->behavior);
	}

	// ── Default property values ───────────────────────────────────────────────

	public function testDefaultLevel(): void
	{
		$this->assertSame(TLogger::INFO, $this->behavior->getLevel());
	}

	public function testDefaultCategory(): void
	{
		$this->assertSame('prado', $this->behavior->getCategory());
	}

	public function testDefaultLogEventsIsTrue(): void
	{
		$this->assertTrue($this->behavior->getLogEvents());
	}

	public function testDefaultLogDynamicEventsIsFalse(): void
	{
		$this->assertFalse($this->behavior->getLogDynamicEvents());
	}

	public function testDefaultEventFilterIsEmpty(): void
	{
		$this->assertSame([], $this->behavior->getEventFilter());
	}

	public function testDefaultDynamicEventFilterIsEmpty(): void
	{
		$this->assertSame([], $this->behavior->getDynamicEventFilter());
	}

	public function testGetStrictEventsReturnsFalse(): void
	{
		$this->assertFalse($this->behavior->getStrictEvents());
	}

	// ── Property mutators ─────────────────────────────────────────────────────

	public function testSetGetLevel(): void
	{
		$this->behavior->setLevel(TLogger::WARNING);
		$this->assertSame(TLogger::WARNING, $this->behavior->getLevel());
	}

	public function testSetGetCategory(): void
	{
		$this->behavior->setCategory('myapp.events');
		$this->assertSame('myapp.events', $this->behavior->getCategory());
	}

	public function testSetLogEventsFalseAndTrue(): void
	{
		$this->behavior->setLogEvents(false);
		$this->assertFalse($this->behavior->getLogEvents());

		$this->behavior->setLogEvents(true);
		$this->assertTrue($this->behavior->getLogEvents());
	}

	public function testSetLogDynamicEventsTrueAndFalse(): void
	{
		$this->behavior->setLogDynamicEvents(true);
		$this->assertTrue($this->behavior->getLogDynamicEvents());

		$this->behavior->setLogDynamicEvents(false);
		$this->assertFalse($this->behavior->getLogDynamicEvents());
	}

	// ── setEventFilter / normalizeFilter ──────────────────────────────────────

	public function testSetEventFilterFromArray(): void
	{
		$this->behavior->setEventFilter(['onAlpha', 'onBeta']);
		$this->assertSame(['onAlpha', 'onBeta'], $this->behavior->getEventFilter());
	}

	public function testSetEventFilterFromCommaSeparatedString(): void
	{
		$this->behavior->setEventFilter('onAlpha, onBeta , onGamma');
		$this->assertSame(['onAlpha', 'onBeta', 'onGamma'], $this->behavior->getEventFilter());
	}

	public function testSetEventFilterEmptyStringClearsFilter(): void
	{
		$this->behavior->setEventFilter(['onAlpha']);
		$this->behavior->setEventFilter('');
		$this->assertSame([], $this->behavior->getEventFilter());
	}

	public function testSetEventFilterDropsEmptyAndWhitespaceEntries(): void
	{
		$this->behavior->setEventFilter(['onAlpha', '', '  ', 'onBeta']);
		$this->assertSame(['onAlpha', 'onBeta'], $this->behavior->getEventFilter());
	}

	public function testSetDynamicEventFilterFromArray(): void
	{
		$this->behavior->setDynamicEventFilter(['dyFoo', 'dyBar']);
		$this->assertSame(['dyFoo', 'dyBar'], $this->behavior->getDynamicEventFilter());
	}

	public function testSetDynamicEventFilterFromCommaSeparatedString(): void
	{
		$this->behavior->setDynamicEventFilter('dyFoo, dyBar');
		$this->assertSame(['dyFoo', 'dyBar'], $this->behavior->getDynamicEventFilter());
	}

	// ── events() and eventsLog() ──────────────────────────────────────────────

	public function testEventsReturnsEmptyMapWithoutOwner(): void
	{
		$this->assertSame([], $this->behavior->events());
	}

	public function testEventsIncludesOwnerOnMethods(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);

		$events = $this->behavior->events();

		$this->assertArrayHasKey('onAlpha', $events);
		$this->assertArrayHasKey('onBeta', $events);
	}

	public function testEachEventMapsTologEvent(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);

		$events = $this->behavior->events();

		$this->assertContains('logEvent', $events['onAlpha']);
		$this->assertContains('logEvent', $events['onBeta']);
	}

	public function testEventsIncludesBehaviorContributedOnEvents(): void
	{
		// Attach auxiliary behavior with onGamma BEFORE the logging behavior.
		$aux = new TEventLoggingBehaviorWithEvent();
		$this->owner->attachBehavior('aux', $aux);
		$this->owner->attachBehavior('logger', $this->behavior);

		$events = $this->behavior->events();

		$this->assertArrayHasKey('onGamma', $events);
	}

	public function testEventsSkipsDisabledBehaviors(): void
	{
		$disabled = new TEventLoggingBehaviorWithEvent();
		$disabled->setEnabled(false);
		$this->owner->attachBehavior('disabled', $disabled);
		$this->owner->attachBehavior('logger', $this->behavior);

		$events = $this->behavior->events();

		// onGamma from the disabled behavior must not appear.
		$this->assertArrayNotHasKey('onGamma', $events);
	}

	public function testEventsSkipsSelfWhenScanningBehaviors(): void
	{
		// Attaching self must not cause recursion or spurious keys.
		$this->owner->attachBehavior('logger', $this->behavior);

		// Should not throw and should return a valid array.
		$events = $this->behavior->events();
		$this->assertIsArray($events);
	}

	public function testEventsOnlyIncludesOnUppercaseMethods(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);

		$events = $this->behavior->events();

		foreach (array_keys($events) as $name) {
			$this->assertMatchesRegularExpression(
				'/^on[A-Z]/',
				$name,
				"Unexpected event name discovered: $name"
			);
		}
	}

	public function testEventsLogIsNonCachingAndReflectsOwner(): void
	{
		// Before attachment: empty.
		$before = $this->behavior->eventsLog();
		$this->assertSame([], $before);

		// After attachment: reflects owner events.
		$this->owner->attachBehavior('logger', $this->behavior);
		$after = $this->behavior->eventsLog();

		$this->assertArrayHasKey('onAlpha', $after);
	}

	public function testEventsLogReturnsConsistentResultOnRepeatCalls(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);

		$first = $this->behavior->eventsLog();
		$second = $this->behavior->eventsLog();

		$this->assertSame(array_keys($first), array_keys($second));
	}

	// ── Attach / detach lifecycle ─────────────────────────────────────────────

	public function testAttachWiresHandlerForEachOwnerEvent(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);

		$this->assertTrue($this->owner->hasEventHandler('OnAlpha'));
		$this->assertTrue($this->owner->hasEventHandler('OnBeta'));
	}

	public function testDetachRemovesAllWiredHandlers(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);
		$this->owner->detachBehavior('logger');

		$this->assertFalse($this->owner->hasEventHandler('OnAlpha'));
		$this->assertFalse($this->owner->hasEventHandler('OnBeta'));
	}

	public function testReattachAfterDetachWiresCleanly(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);
		$this->owner->detachBehavior('logger');
		$this->owner->attachBehavior('logger', $this->behavior);

		$this->assertTrue($this->owner->hasEventHandler('OnAlpha'));
	}

	public function testMultipleAttachDetachCyclesLeaveNoResidualHandlers(): void
	{
		for ($i = 0; $i < 3; $i++) {
			$this->owner->attachBehavior('logger', $this->behavior);
			$this->assertTrue($this->owner->hasEventHandler('OnAlpha'));

			$this->owner->detachBehavior('logger');
			$this->assertFalse($this->owner->hasEventHandler('OnAlpha'));
		}
	}

	// ── logEvent() filtering ──────────────────────────────────────────────────

	private function countLogs(int $level, string $category): int
	{
		return count(Prado::getLogger()->getLogs($level, $category));
	}

	public function testLogEventWritesToLoggerWhenFilterIsEmpty(): void
	{
		$cat = 'test.elb.nofilter';
		$this->behavior->setCategory($cat);
		$this->behavior->setLevel(TLogger::DEBUG);

		$param = new TEventParameter();
		$param->setEventName('onAlpha');

		$before = $this->countLogs(TLogger::DEBUG, $cat);
		$this->behavior->logEvent($this->owner, $param);

		$this->assertSame($before + 1, $this->countLogs(TLogger::DEBUG, $cat));
	}

	public function testLogEventSkipsWhenLogEventsFalse(): void
	{
		$cat = 'test.elb.disabled';
		$this->behavior->setCategory($cat);
		$this->behavior->setLogEvents(false);

		$param = new TEventParameter();
		$param->setEventName('onAlpha');

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->behavior->logEvent($this->owner, $param);

		$this->assertSame($before, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testLogEventPassesWhenEventNameMatchesFilter(): void
	{
		$cat = 'test.elb.filter.pass';
		$this->behavior->setCategory($cat);
		$this->behavior->setEventFilter(['onAlpha']);

		$param = new TEventParameter();
		$param->setEventName('onAlpha');

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->behavior->logEvent($this->owner, $param);

		$this->assertSame($before + 1, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testLogEventSkipsWhenEventNameNotInFilter(): void
	{
		$cat = 'test.elb.filter.skip';
		$this->behavior->setCategory($cat);
		$this->behavior->setEventFilter(['onBeta']);

		$param = new TEventParameter();
		$param->setEventName('onAlpha');

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->behavior->logEvent($this->owner, $param);

		$this->assertSame($before, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testLogEventFilterIsCaseInsensitive(): void
	{
		$cat = 'test.elb.filter.case';
		$this->behavior->setCategory($cat);
		$this->behavior->setEventFilter(['OnAlpha']); // uppercase O

		$param = new TEventParameter();
		$param->setEventName('onAlpha'); // lowercase o

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->behavior->logEvent($this->owner, $param);

		$this->assertSame($before + 1, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testLogEventMessageContainsEventNameAndSenderClass(): void
	{
		$cat = 'test.elb.message';
		$this->behavior->setCategory($cat);

		$param = new TEventParameter();
		$param->setEventName('onAlpha');

		$this->behavior->logEvent($this->owner, $param);

		$logs = Prado::getLogger()->getLogs(TLogger::INFO, $cat);
		$this->assertNotEmpty($logs);

		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('onAlpha', $msg);
		$this->assertStringContainsString(TEventLoggingOwner::class, $msg);
	}

	public function testLogEventWithEmptyEventNameUsesUnnamedPlaceholder(): void
	{
		$cat = 'test.elb.unnamed';
		$this->behavior->setCategory($cat);

		// TEventParameter with no event name yet (empty string).
		$param = new TEventParameter();
		// Do NOT call setEventName — getEventName() returns ''.

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->behavior->logEvent($this->owner, $param);
		$after = $this->countLogs(TLogger::INFO, $cat);

		$this->assertSame($before + 1, $after);

		$logs = Prado::getLogger()->getLogs(TLogger::INFO, $cat);
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('(unnamed)', $msg);
	}

	// ── Integration: owner raises event → logEvent fires ─────────────────────

	public function testOwnerEventFireTriggersLogEvent(): void
	{
		$cat = 'test.elb.integration';
		$this->behavior->setCategory($cat);
		$this->owner->attachBehavior('logger', $this->behavior);

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->owner->onAlpha(new TEventParameter());

		$this->assertSame($before + 1, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testMultipleEventsFireMultipleLogEntries(): void
	{
		$cat = 'test.elb.multi';
		$this->behavior->setCategory($cat);
		$this->owner->attachBehavior('logger', $this->behavior);

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->owner->onAlpha(new TEventParameter());
		$this->owner->onBeta(new TEventParameter());

		$this->assertSame($before + 2, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testDisablingBehaviorStopsLogging(): void
	{
		$cat = 'test.elb.disable';
		$this->behavior->setCategory($cat);
		$this->owner->attachBehavior('logger', $this->behavior);
		$this->behavior->setEnabled(false);

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->owner->onAlpha(new TEventParameter());

		$this->assertSame($before, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testReenablingBehaviorResumesLogging(): void
	{
		$cat = 'test.elb.reenable';
		$this->behavior->setCategory($cat);
		$this->owner->attachBehavior('logger', $this->behavior);
		$this->behavior->setEnabled(false);
		$this->behavior->setEnabled(true);

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->owner->onAlpha(new TEventParameter());

		$this->assertSame($before + 1, $this->countLogs(TLogger::INFO, $cat));
	}

	// ── __dycall() ────────────────────────────────────────────────────────────

	public function testDycallReturnsFirstArgument(): void
	{
		$result = $this->behavior->__dycall('dyFoo', ['firstValue', 'secondValue']);
		$this->assertSame('firstValue', $result);
	}

	public function testDycallReturnsNullWhenNoArguments(): void
	{
		$result = $this->behavior->__dycall('dyFoo', []);
		$this->assertNull($result);
	}

	public function testDycallLogsWhenLogDynamicEventsEnabled(): void
	{
		$cat = 'test.elb.dycall';
		$this->behavior->setCategory($cat);
		$this->behavior->setLogDynamicEvents(true);

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->behavior->__dycall('dyFoo', ['x']);

		$this->assertSame($before + 1, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testDycallSkipsWhenLogDynamicEventsDisabled(): void
	{
		$cat = 'test.elb.nodynamic';
		$this->behavior->setCategory($cat);
		$this->behavior->setLogDynamicEvents(false);

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->behavior->__dycall('dyFoo', ['x']);

		$this->assertSame($before, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testDycallPassesDynamicEventFilter(): void
	{
		$cat = 'test.elb.dyfilter.pass';
		$this->behavior->setCategory($cat);
		$this->behavior->setLogDynamicEvents(true);
		$this->behavior->setDynamicEventFilter(['dyFoo']);

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->behavior->__dycall('dyFoo', ['x']);

		$this->assertSame($before + 1, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testDycallSkipsDynamicEventFilter(): void
	{
		$cat = 'test.elb.dyfilter.skip';
		$this->behavior->setCategory($cat);
		$this->behavior->setLogDynamicEvents(true);
		$this->behavior->setDynamicEventFilter(['dyBar']); // not dyFoo

		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->behavior->__dycall('dyFoo', ['x']);

		$this->assertSame($before, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testDycallMessageContainsMethodNameAndArgCount(): void
	{
		$cat = 'test.elb.dymessage';
		$this->behavior->setCategory($cat);
		$this->behavior->setLogDynamicEvents(true);
		$this->owner->attachBehavior('logger', $this->behavior);

		$this->behavior->__dycall('dyFoo', ['arg1', 'arg2']);

		$logs = Prado::getLogger()->getLogs(TLogger::INFO, $cat);
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('dyFoo', $msg);
		$this->assertStringContainsString('2', $msg);
		$this->assertStringContainsString(TEventLoggingOwner::class, $msg);
	}

	public function testDycallUsesDetachedLabelWhenNoOwner(): void
	{
		$cat = 'test.elb.detached';
		$this->behavior->setCategory($cat);
		$this->behavior->setLogDynamicEvents(true);
		// No owner attached.

		$this->behavior->__dycall('dyFoo', []);

		$logs = Prado::getLogger()->getLogs(TLogger::INFO, $cat);
		$msg = end($logs)[TLogger::LOG_MESSAGE];
		$this->assertStringContainsString('(detached)', $msg);
	}

	// ── _getZappableSleepProps ────────────────────────────────────────────────

	public function testZappableAlwaysExcludesLoggedEvents(): void
	{
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TEventLoggingBehavior::class . "\0_loggedEvents",
			$exprops
		);
	}

	public function testZappableExcludesLogEventsWhenDefault(): void
	{
		// Default _logEvents === true → excluded from serialization.
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TEventLoggingBehavior::class . "\0_logEvents",
			$exprops
		);
	}

	public function testZappableKeepsLogEventsWhenFalse(): void
	{
		// Non-default _logEvents === false → must be serialized.
		$this->behavior->setLogEvents(false);
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertNotContains(
			"\0" . TEventLoggingBehavior::class . "\0_logEvents",
			$exprops
		);
	}

	public function testZappableExcludesLogDynamicEventsWhenDefault(): void
	{
		// Default _logDynamicEvents === false → excluded.
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TEventLoggingBehavior::class . "\0_logDynamicEvents",
			$exprops
		);
	}

	public function testZappableKeepsLogDynamicEventsWhenTrue(): void
	{
		$this->behavior->setLogDynamicEvents(true);
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertNotContains(
			"\0" . TEventLoggingBehavior::class . "\0_logDynamicEvents",
			$exprops
		);
	}

	public function testZappableExcludesEmptyFilters(): void
	{
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TEventLoggingBehavior::class . "\0_eventFilter",
			$exprops
		);
		$this->assertContains(
			"\0" . TEventLoggingBehavior::class . "\0_dynamicEventFilter",
			$exprops
		);
	}

	public function testZappableKeepsNonEmptyFilters(): void
	{
		$this->behavior->setEventFilter(['onAlpha']);
		$this->behavior->setDynamicEventFilter(['dyFoo']);
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertNotContains(
			"\0" . TEventLoggingBehavior::class . "\0_eventFilter",
			$exprops
		);
		$this->assertNotContains(
			"\0" . TEventLoggingBehavior::class . "\0_dynamicEventFilter",
			$exprops
		);
	}

	public function testZappableExcludesLevelWhenDefault(): void
	{
		// Default _level === TLogger::INFO → excluded to keep the payload lean.
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TEventLoggingBehavior::class . "\0_level",
			$exprops
		);
	}

	public function testZappableKeepsLevelWhenNonDefault(): void
	{
		$this->behavior->setLevel(TLogger::WARNING);
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertNotContains(
			"\0" . TEventLoggingBehavior::class . "\0_level",
			$exprops
		);
	}

	public function testZappableExcludesCategoryWhenDefault(): void
	{
		// Default _category === 'prado' → excluded.
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertContains(
			"\0" . TEventLoggingBehavior::class . "\0_category",
			$exprops
		);
	}

	public function testZappableKeepsCategoryWhenNonDefault(): void
	{
		$this->behavior->setCategory('myapp.events');
		$exprops = [];
		$this->behavior->pubGetZappableSleepProps($exprops);

		$this->assertNotContains(
			"\0" . TEventLoggingBehavior::class . "\0_category",
			$exprops
		);
	}

	public function testSerializationRoundTripPreservesNonDefaultProperties(): void
	{
		$this->behavior->setLevel(TLogger::WARNING);
		$this->behavior->setCategory('myapp.events');
		$this->behavior->setLogEvents(false);
		$this->behavior->setLogDynamicEvents(true);
		$this->behavior->setEventFilter(['onAlpha', 'onBeta']);
		$this->behavior->setDynamicEventFilter(['dyFoo']);

		/** @var TEventLoggingBehaviorAccessor $restored */
		$restored = unserialize(serialize($this->behavior));

		$this->assertSame(TLogger::WARNING, $restored->getLevel());
		$this->assertSame('myapp.events', $restored->getCategory());
		$this->assertFalse($restored->getLogEvents());
		$this->assertTrue($restored->getLogDynamicEvents());
		$this->assertSame(['onAlpha', 'onBeta'], $restored->getEventFilter());
		$this->assertSame(['dyFoo'], $restored->getDynamicEventFilter());
		// _loggedEvents must always be empty after deserialization.
		$this->assertSame([], $restored->events());
	}

	// ── String-coercion for XML configuration ─────────────────────────────────

	public function testSetLevelAcceptsStringFromXmlConfig(): void
	{
		$this->behavior->setLevel((string) TLogger::WARNING);
		$this->assertSame(TLogger::WARNING, $this->behavior->getLevel(),
			'setLevel() must accept a numeric string as passed from XML config.');
	}

	public function testSetLogEventsAcceptsFalseString(): void
	{
		// PHP coerces "false" (non-empty string) to bool true before a typed `bool`
		// parameter receives it. setLogEvents() must accept bool|string and delegate
		// to TPropertyValue::ensureBoolean() to handle the string correctly.
		$this->behavior->setLogEvents('false');
		$this->assertFalse($this->behavior->getLogEvents(),
			'setLogEvents("false") must set LogEvents to false.');
	}

	public function testSetLogEventsAcceptsTrueString(): void
	{
		$this->behavior->setLogEvents(false);
		$this->behavior->setLogEvents('true');
		$this->assertTrue($this->behavior->getLogEvents(),
			'setLogEvents("true") must set LogEvents to true.');
	}

	public function testSetLogDynamicEventsAcceptsFalseString(): void
	{
		$this->behavior->setLogDynamicEvents(true);
		$this->behavior->setLogDynamicEvents('false');
		$this->assertFalse($this->behavior->getLogDynamicEvents(),
			'setLogDynamicEvents("false") must set LogDynamicEvents to false.');
	}

	public function testSetLogDynamicEventsAcceptsTrueString(): void
	{
		$this->behavior->setLogDynamicEvents('true');
		$this->assertTrue($this->behavior->getLogDynamicEvents(),
			'setLogDynamicEvents("true") must set LogDynamicEvents to true.');
	}

	// ── Behavior-with-no-on-events does not contaminate discovery ────────────

	public function testBehaviorWithNoEventMethodsDoesNotContaminateDiscovery(): void
	{
		$noEvents = new TEventLoggingBehaviorNoEvents();
		$this->owner->attachBehavior('noevents', $noEvents);
		$this->owner->attachBehavior('logger', $this->behavior);

		$events = $this->behavior->events();

		// Only onAlpha and onBeta should appear from the owner class.
		$this->assertArrayHasKey('onAlpha', $events);
		$this->assertArrayHasKey('onBeta', $events);
		$this->assertArrayNotHasKey('onGamma', $events);
	}

	// ── dyAttachBehavior — live behavior tracking ─────────────────────────────

	public function testDyAttachWiresEventsFromLateAttachedBehavior(): void
	{
		// Logger attached first, then a behavior with onGamma.
		$this->owner->attachBehavior('logger', $this->behavior);
		$aux = new TEventLoggingBehaviorWithEvent();
		$this->owner->attachBehavior('aux', $aux);

		$this->assertTrue($this->owner->hasEventHandler('OnGamma'));
	}

	public function testDyAttachFiresLogEventForLateAttachedBehaviorEvent(): void
	{
		$cat = 'test.elb.dyattach.fire';
		$this->behavior->setCategory($cat);
		$this->owner->attachBehavior('logger', $this->behavior);
		$aux = new TEventLoggingBehaviorWithEvent();
		$this->owner->attachBehavior('aux', $aux);

		// Raise OnGamma on the owner — the logEvent handler was wired via dyAttachBehavior.
		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->owner->raiseEvent('OnGamma', $this->owner, new TEventParameter());

		$this->assertSame($before + 1, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testDyAttachSkipsDisabledLateAttachedBehavior(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);
		$aux = new TEventLoggingBehaviorWithEvent();
		$aux->setEnabled(false);
		$this->owner->attachBehavior('aux', $aux);

		// onGamma from a disabled behavior must not be wired.
		$this->assertFalse($this->owner->hasEventHandler('OnGamma'));
	}

	public function testDyAttachDoesNotDuplicateAlreadyWiredEvents(): void
	{
		// Attach aux first, then logger (so onGamma is wired at attach time),
		// then attach a second behavior also declaring onGamma.
		$cat = 'test.elb.dyattach.nodup';
		$this->behavior->setCategory($cat);
		$first = new TEventLoggingBehaviorWithEvent();
		$this->owner->attachBehavior('first', $first);
		$this->owner->attachBehavior('logger', $this->behavior);

		$second = new TEventLoggingBehaviorWithGamma();
		$this->owner->attachBehavior('second', $second);

		// Raise OnGamma on the owner — handler must fire exactly once.
		$before = $this->countLogs(TLogger::INFO, $cat);
		$this->owner->raiseEvent('OnGamma', $this->owner, new TEventParameter());

		$this->assertSame($before + 1, $this->countLogs(TLogger::INFO, $cat));
	}

	public function testDyAttachBehaviorWithNoOnEventsChangesNothing(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);
		$handlersBefore = $this->owner->hasEventHandler('OnAlpha');

		$noEvents = new TEventLoggingBehaviorNoEvents();
		$this->owner->attachBehavior('noevents', $noEvents);

		// OnAlpha handler state is unchanged.
		$this->assertSame($handlersBefore, $this->owner->hasEventHandler('OnAlpha'));
		$this->assertFalse($this->owner->hasEventHandler('OnGamma'));
	}

	// ── dyDetachBehavior — live behavior tracking ─────────────────────────────

	public function testDyDetachUnwiresEventsFromDetachedBehavior(): void
	{
		// Logger first, then aux with onGamma.
		$this->owner->attachBehavior('logger', $this->behavior);
		$aux = new TEventLoggingBehaviorWithEvent();
		$this->owner->attachBehavior('aux', $aux);

		$this->assertTrue($this->owner->hasEventHandler('OnGamma'));

		$this->owner->detachBehavior('aux');

		$this->assertFalse($this->owner->hasEventHandler('OnGamma'));
	}

	public function testDyDetachKeepsEventWhenOwnerClassAlsoHasIt(): void
	{
		// onAlpha is on TEventLoggingOwner. A behavior also declares onAlpha.
		// When that behavior is detached, onAlpha should stay wired.
		$aux = new class extends TBehavior {
			public function onAlpha(TEventParameter $param): void
			{
				$this->raiseEvent('OnAlpha', $this, $param);
			}
		};
		$this->owner->attachBehavior('logger', $this->behavior);
		$this->owner->attachBehavior('aux', $aux);

		$this->owner->detachBehavior('aux');

		// onAlpha still wired because TEventLoggingOwner has it.
		$this->assertTrue($this->owner->hasEventHandler('OnAlpha'));
	}

	public function testDyDetachKeepsEventWhenAnotherBehaviorAlsoHasIt(): void
	{
		// Two behaviors both provide onGamma. Detaching one should keep the handler.
		$this->owner->attachBehavior('logger', $this->behavior);
		$first = new TEventLoggingBehaviorWithEvent();
		$second = new TEventLoggingBehaviorWithGamma();
		$this->owner->attachBehavior('first', $first);
		$this->owner->attachBehavior('second', $second);

		$this->owner->detachBehavior('first');

		// second still provides onGamma, so handler stays.
		$this->assertTrue($this->owner->hasEventHandler('OnGamma'));
	}

	public function testDyDetachRemovesUniqueEventNotSharedByAnyone(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);
		$delta = new TEventLoggingBehaviorWithDelta();
		$this->owner->attachBehavior('delta', $delta);

		$this->assertTrue($this->owner->hasEventHandler('OnDelta'));

		$this->owner->detachBehavior('delta');

		$this->assertFalse($this->owner->hasEventHandler('OnDelta'));
	}

	public function testDyDetachBehaviorWithNoOnEventsChangesNothing(): void
	{
		$this->owner->attachBehavior('logger', $this->behavior);
		$noEvents = new TEventLoggingBehaviorNoEvents();
		$this->owner->attachBehavior('noevents', $noEvents);

		$hadAlpha = $this->owner->hasEventHandler('OnAlpha');
		$this->owner->detachBehavior('noevents');

		$this->assertSame($hadAlpha, $this->owner->hasEventHandler('OnAlpha'));
	}
}
