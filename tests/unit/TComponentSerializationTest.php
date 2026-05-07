<?php

require_once __DIR__ . '/TComponentTestBase.php';

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
}
