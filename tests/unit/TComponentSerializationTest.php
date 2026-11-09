<?php

namespace Prado\Test\Unit;

use Prado\Util\TBehavior;

/**
 * Tests for TComponent serialization: __clone() and __wakeup() behaviour,
 * including behavior ownership re-wiring, event-handler list fixup,
 * class-behavior reconciliation across sleep/wake, and anonymous behaviors.
 */
class TComponentSerializationTest extends TComponentTestBase
{
	public function testClone()
	{
		$obj = new NewComponent();
		$this->component = new NewComponent();
		$this->component->attachBehavior('CopyBehavior', $b = new NewComponentBehavior());
		$this->component->onMyEvent[] = [$this->component, 'myEventHandler'];
		$this->component->onMyEvent[] = [$obj, 'myEventHandler'];
		$this->assertEquals(3, count($this->component->onMyEvent));

		$this->assertNotNull($this->component->asa('CopyBehavior'));
		$this->assertEquals($this->component, $this->component->CopyBehavior->getOwner());

		$copy = clone $this->component;
		$copy->Text = 'copyObject';

		$this->assertNotNull($copy->asa('COPYBehavior'));
		$this->assertEquals($this->component, $this->component->CopyBehavior->getOwner());
		$this->assertEquals($copy, $copy->CopyBehavior->getOwner());
		$this->assertTrue($copy->CopyBehavior !== $this->component->CopyBehavior);
		$this->assertEquals(3, count($this->component->onMyEvent));
		$this->assertEquals(-1, $this->component->onMyEvent->indexOf([$copy->CopyBehavior, 'ncBehaviorHandler']));
		$this->assertEquals(3, count($copy->onMyEvent));
		$this->assertEquals(-1, $copy->onMyEvent->indexOf([$b, 'ncBehaviorHandler']));
		$this->assertEquals(2, $copy->onMyEvent->indexOf([$copy->CopyBehavior, 'ncBehaviorHandler']));
	}

	public function testWakeUp()
	{
		NewComponent::attachClassBehavior('ClassBehavior1', $cb1 = new FooClassBehavior());
		$this->tearDownScripts['ClassBehavior1'] = function() {NewComponent::detachClassBehavior('ClassBehavior1');};
		$cb1->propertya = 'second value';
		NewComponent::attachClassBehavior('ClassBehavior2', $cb2 = new FooFooClassBehavior());
		$this->tearDownScripts['ClassBehavior2'] = function() {NewComponent::detachClassBehavior('ClassBehavior2');};
		NewComponent::attachClassBehavior('ClassBehavior4', $cb4 = new FooClassBehavior());
		$this->tearDownScripts['ClassBehavior4'] = function() {NewComponent::detachClassBehavior('ClassBehavior4');};

		// Anonynmous behavior
		$behavior5Name = $this->anonymousClassIndex++;
		NewComponent::attachClassBehavior(null, $cb5 = new FooClassBehavior());
		$this->tearDownScripts['ClassBehavior5'] = function() use ($behavior5Name) {NewComponent::detachClassBehavior($behavior5Name);};
		$cb5->propertya = '5th value';
		$this->assertEquals('classbehavior1', $cb1->getName());
		$this->assertEquals('classbehavior2', $cb2->getName());
		$this->assertEquals('classbehavior4', $cb4->getName());


		$obj = new NewComponent();
		$this->component = new NewComponent();

		$this->component->attachBehavior('CopyBehavior', $b = new NewComponentBehavior());
		$this->component->onMyEvent[] = [$this->component, 'myEventHandler'];
		$this->component->onMyEvent[] = [$obj, 'myEventHandler'];
		$this->assertEquals(3, count($this->component->onMyEvent));

		$this->assertNotNull($this->component->asa('CopyBehavior'));
		$this->assertNotNull($this->component->asa('ClassBehavior1'));
		$this->assertNotNull($this->component->asa('ClassBehavior2'));
		$this->assertNull($this->component->asa('ClassBehavior3'));
		$this->assertEquals($this->component, $this->component->CopyBehavior->getOwner());

		// Serialize
		$data = serialize($this->component);

		// Change environment
		NewComponent::detachClassBehavior('ClassBehavior2'); // Without an existing class behavior
		unset($this->tearDownScripts['ClassBehavior2']);
		$this->assertNull($this->component->asa('ClassBehavior2'));
		NewComponent::attachClassBehavior('ClassBehavior3', $cb3 = new BarClassBehavior());

		// ClassBehavior3 is new between sleep and wake up.
		$this->tearDownScripts['ClassBehavior3'] = function() {NewComponent::detachClassBehavior('ClassBehavior3');};
		$this->assertNotNull($this->component->asa('ClassBehavior3')); // With new class behavior
		NewComponent::detachClassBehavior('ClassBehavior4');	// with a replacement class behavior.
		NewComponent::attachClassBehavior('ClassBehavior4', $cb4a = new FooClassBehavior());
		$cb4a->propertya = 'new 4th value';
		$cb2->propertya = '3rd value';
		$cb5->propertya = 'old 5th value';

		// anonymous 1 is new between sleep and wake up.
		$behavior6Name = $this->anonymousClassIndex++;
		NewComponent::attachClassBehavior(null, $cb6 = new FooClassBehavior());
		$this->tearDownScripts['ClassBehavior6'] = function() use ($behavior6Name) {NewComponent::detachClassBehavior($behavior6Name);};
		$cb6->propertya = '6th value';

		// Unserialize
		$copy = unserialize($data);
		$copy->Text = 'copyObject';

		$this->assertNotEquals($cb5, $copy->asa(0));
		$this->assertInstanceOf($cb5::class, $copy->asa(0));
		$this->assertNull($copy->asa(1), "anonymous behavior added between sleep and wake up was attached when it should not have been");
		$this->assertEquals($cb5, $this->component->asa(0));
		$this->assertEquals($cb6, $this->component->asa(1));
		$this->assertEquals($cb3, $this->component->asa('ClassBehavior3'));
		$this->assertEquals($cb4a, $this->component->asa('ClassBehavior4'));
		$this->assertNotNull($copy->asa('CopyBehavior'));
		$this->assertEquals($this->component, $this->component->CopyBehavior->getOwner());
		$this->assertEquals($copy, $copy->CopyBehavior->getOwner());
		$this->assertTrue($copy->CopyBehavior !== $this->component->CopyBehavior);
		$this->assertEquals(3, count($this->component->onMyEvent));
		$this->assertEquals(-1, $this->component->onMyEvent->indexOf([$copy->CopyBehavior, 'ncBehaviorHandler']));
		$this->assertEquals(-1, $copy->onMyEvent->indexOf([$this->component->CopyBehavior, 'ncBehaviorHandler']));
		$this->assertEquals(0, $copy->onMyEvent->indexOf([$copy->CopyBehavior, 'ncBehaviorHandler']));
		$this->assertEquals(1, count($copy->onMyEvent));
		$this->assertEquals($this->component->asa('ClassBehavior1'), $copy->asa('ClassBehavior1'));
		$this->assertNull($this->component->asa('ClassBehavior2'));
		$this->assertNotNull($copy->asa('ClassBehavior2'));
		$this->assertNotEquals($cb2, $copy->asa('ClassBehavior2'));
		$this->assertEquals($this->component->asa('ClassBehavior3'), $copy->asa('ClassBehavior3'));
		$this->assertEquals($cb4a, $copy->asa('ClassBehavior4'));
	}

	/**
	 * A clone drops a handler that is itself an attached behavior of the original
	 * and keeps a closure handler.
	 */
	public function testClone_scrubsBehaviorObjectHandlerAndKeepsClosure()
	{
		$behavior = new TComponentSerializationTestInvokableBehavior();
		$closure = function ($sender, $param) {
		};
		$this->component->attachBehavior('invokable', $behavior);
		$this->component->attachEventHandler('onMyEvent', $behavior);
		$this->component->attachEventHandler('onMyEvent', $closure);
		$this->assertSame([$behavior, $closure], $this->component->onMyEvent->toArray());

		$copy = clone $this->component;

		$this->assertSame([$closure], $copy->onMyEvent->toArray());
		$this->assertSame([$behavior, $closure], $this->component->onMyEvent->toArray());
		$this->assertInstanceOf(TComponentSerializationTestInvokableBehavior::class, $copy->asa('invokable'));
		$this->assertNotSame($behavior, $copy->asa('invokable'));

		$copy->onMyEvent(null);
		$this->assertSame(0, $behavior->invoked);
		$this->assertSame(0, $copy->asa('invokable')->invoked);
		$this->component->onMyEvent(null);
		$this->assertSame(1, $behavior->invoked);

		$copy->unlisten();
	}

	/**
	 * A function name, a 'Class::method' string, and a [class, method] pair have no
	 * handler object, so a clone of a component with behaviors keeps all three.
	 */
	public function testClone_withBehaviorKeepsFunctionAndStaticHandlers()
	{
		$staticPair = [self::class, 'staticCloneHandler'];
		$staticString = self::class . '::staticCloneHandler';
		$this->component->attachBehavior('CopyBehavior', $behavior = new NewComponentBehavior());
		$this->component->attachEventHandler('onMyEvent', 'foo');
		$this->component->attachEventHandler('onMyEvent', $staticPair);
		$this->component->attachEventHandler('onMyEvent', $staticString);
		$this->assertSame(4, $this->component->onMyEvent->getCount());

		$copy = clone $this->component;

		$copyBehavior = $copy->asa('CopyBehavior');
		$this->assertNotSame($behavior, $copyBehavior);
		$this->assertSame(
			['foo', $staticPair, $staticString, [$copyBehavior, 'ncBehaviorHandler']],
			$copy->onMyEvent->toArray()
		);
		$this->assertSame(
			[[$behavior, 'ncBehaviorHandler'], 'foo', $staticPair, $staticString],
			$this->component->onMyEvent->toArray()
		);

		self::$staticCloneHandlerSenders = [];
		$copy->onMyEvent(null);
		$this->assertSame([$copy, $copy], self::$staticCloneHandlerSenders);
		self::$staticCloneHandlerSenders = [];

		$copy->unlisten();
	}

	/** @var object[] the senders received by {@see staticCloneHandler}. */
	private static array $staticCloneHandlerSenders = [];

	/**
	 * Records the sender of an event handled through a static callable.
	 * @param mixed $sender the event sender.
	 * @param mixed $param the event parameter.
	 */
	public static function staticCloneHandler($sender, $param): void
	{
		self::$staticCloneHandlerSenders[] = $sender;
	}

	/**
	 * __sleep() keeps `_listeningenabled`, `_behaviorsenabled`, and `_m` only when
	 * they differ from their defaults and always drops the event handlers and the
	 * behavior method caches.  The behaviors-enabled state survives a round trip.
	 */
	public function testSleep_keepsOnlyNonDefaultState()
	{
		foreach ([false, true] as $listening) {
			foreach ([true, false] as $behaviorsEnabled) {
				foreach ([false, true] as $hasBehavior) {
					$case = 'listening=' . var_export($listening, true)
						. ' behaviorsEnabled=' . var_export($behaviorsEnabled, true)
						. ' hasBehavior=' . var_export($hasBehavior, true);
					$component = new NewComponentNoListen();
					if ($listening) {
						$component->listen();
					}
					if ($hasBehavior) {
						$component->attachBehavior('sleeper', new FooBehavior());
					}
					if (!$behaviorsEnabled) {
						$component->disableBehaviors();
					}
					$component->onMyEvent[] = 'foo';
					$component->hasMethod('faaEverMore'); // fills the owner-callable cache

					$props = $component->__sleep();

					$this->assertSame($listening, in_array("\0*\0_listeningenabled", $props, true), $case);
					$this->assertSame(!$behaviorsEnabled, in_array("\0*\0_behaviorsenabled", $props, true), $case);
					$this->assertSame($hasBehavior, in_array("\0*\0_m", $props, true), $case);
					$this->assertNotContains("\0*\0_e", $props, $case);
					$this->assertNotContains("\0" . \Prado\TComponent::class . "\0_bm", $props, $case);
					$this->assertNotContains("\0" . \Prado\TComponent::class . "\0_cm", $props, $case);
					$this->assertNotContains("\0" . \Prado\TComponent::class . "\0_bv", $props, $case);
					$this->assertContains("\0" . NewComponent::class . "\0_text", $props, $case);

					$copy = unserialize(serialize($component));

					$this->assertSame($behaviorsEnabled, $copy->getBehaviorsEnabled(), $case);
					$this->assertSame($hasBehavior, $copy->asa('sleeper') instanceof FooBehavior, $case);
					$this->assertSame($hasBehavior && $behaviorsEnabled, $copy->hasMethod('faaEverMore'), $case);
					$this->assertSame(0, $copy->onMyEvent->getCount(), $case);
					$copy->enableBehaviors();
					$this->assertSame($hasBehavior, $copy->hasMethod('faaEverMore'), $case);

					$copy->unlisten();
					$component->unlisten();
				}
			}
		}
	}
}

/**
 * Behavior that is itself a callable event handler.
 */
class TComponentSerializationTestInvokableBehavior extends TBehavior
{
	/** @var int the number of times the behavior was invoked as a handler. */
	public int $invoked = 0;

	/**
	 * Counts an invocation as an event handler.
	 * @param mixed $sender the event sender.
	 * @param mixed $param the event parameter.
	 */
	public function __invoke($sender, $param): void
	{
		$this->invoked++;
	}
}
