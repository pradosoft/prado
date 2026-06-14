<?php

require_once __DIR__ . '/TComponentTestBase.php';

use Prado\Collections\TPriorityList;
use Prado\Exceptions\TInvalidOperationException;

/**
 * Tests for TComponent's property system: hasProperty(), canGetProperty(),
 * canSetProperty(), __get(), __set(), isset(), unset(), getSubProperty(),
 * setSubProperty(), hasMethod(), JavaScript getter/setter aliases, and
 * the protected-setter guard.
 */
class TComponentPropertyTest extends TComponentTestBase
{
	public function testHasProperty()
	{
		$this->assertTrue($this->component->hasProperty('Text'), "Component hasn't property Text");
		$this->assertTrue($this->component->hasProperty('text'), "Component hasn't property text");
		$this->assertFalse($this->component->hasProperty('Caption'), "Component has property Caption");

		$this->assertTrue($this->component->hasProperty('ColorAttribute'), "Component hasn't property JsColorAttribute");
		$this->assertTrue($this->component->hasProperty('colorattribute'), "Component hasn't property JsColorAttribute");
		$this->assertFalse($this->component->canGetProperty('PastelAttribute'), "Component has property JsPastelAttribute");

		$this->assertTrue($this->component->hasProperty('JSColorAttribute'), "Component hasn't property JsColorAttribute");
		$this->assertTrue($this->component->hasProperty('jscolorattribute'), "Component hasn't property JsColorAttribute");
		$this->assertFalse($this->component->hasProperty('jsPastelAttribute'), "Component has property JsPastelAttribute");

		$this->assertFalse($this->component->hasProperty('Excitement'), "Component has property Excitement");
		$this->component->attachBehavior('ExcitementPropBehavior', new BehaviorTestBehavior);
		$this->assertTrue($this->component->hasProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehaviors();
		$this->assertFalse($this->component->hasProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->hasProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehavior('ExcitementPropBehavior');
		$this->assertFalse($this->component->hasProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehavior('ExcitementPropBehavior');
		$this->assertTrue($this->component->hasProperty('Excitement'), "Component hasn't property Excitement");

		$this->component->detachBehavior('ExcitementPropBehavior');

		$this->assertFalse($this->component->hasProperty('Excitement'), "Component has property Excitement");
	}

	public function testCanGetProperty()
	{
		$this->assertTrue($this->component->canGetProperty('Text'));
		$this->assertTrue($this->component->canGetProperty('text'));
		$this->assertFalse($this->component->canGetProperty('Caption'));

		$this->assertTrue($this->component->canGetProperty('ColorAttribute'));
		$this->assertTrue($this->component->canGetProperty('colorattribute'));
		$this->assertFalse($this->component->canGetProperty('PastelAttribute'));

		$this->assertTrue($this->component->canGetProperty('JSColorAttribute'));
		$this->assertTrue($this->component->canGetProperty('jscolorattribute'));
		$this->assertFalse($this->component->canGetProperty('jsPastelAttribute'));


		$this->assertFalse($this->component->canGetProperty('Excitement'), "Component has property Excitement");
		$this->component->attachBehavior('ExcitementPropBehavior', new BehaviorTestBehavior);
		$this->assertTrue($this->component->canGetProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehaviors();
		$this->assertFalse($this->component->canGetProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->canGetProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehavior('ExcitementPropBehavior');
		$this->assertFalse($this->component->canGetProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehavior('ExcitementPropBehavior');
		$this->assertTrue($this->component->canGetProperty('Excitement'), "Component hasn't property Excitement");

		$this->component->detachBehavior('ExcitementPropBehavior');

		$this->assertFalse($this->component->canGetProperty('Excitement'), "Component has property Excitement");
	}

	public function testCanSetProperty()
	{
		$this->assertTrue($this->component->canSetProperty('Text'));
		$this->assertTrue($this->component->canSetProperty('text'));
		$this->assertFalse($this->component->canSetProperty('Caption'));

		$this->assertTrue($this->component->canSetProperty('ColorAttribute'));
		$this->assertTrue($this->component->canSetProperty('colorattribute'));
		$this->assertFalse($this->component->canSetProperty('PastelAttribute'));

		$this->assertTrue($this->component->canSetProperty('JSColorAttribute'));
		$this->assertTrue($this->component->canSetProperty('jscolorattribute'));
		$this->assertFalse($this->component->canSetProperty('jsPastelAttribute'));

		$this->assertFalse($this->component->canSetProperty('Excitement'), "Component has property Excitement");
		$this->component->attachBehavior('ExcitementPropBehavior', new BehaviorTestBehavior);
		$this->assertTrue($this->component->canSetProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehaviors();
		$this->assertFalse($this->component->canSetProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->canSetProperty('Excitement'), "Component hasn't property Excitement");
		$this->component->disableBehavior('ExcitementPropBehavior');
		$this->assertFalse($this->component->canSetProperty('Excitement'), "Component has property Excitement");
		$this->component->enableBehavior('ExcitementPropBehavior');
		$this->assertTrue($this->component->canSetProperty('Excitement'), "Component hasn't property Excitement");

		$this->component->detachBehavior('ExcitementPropBehavior');
	}

	public function testGetProperty()
	{
		$this->assertTrue('default' === $this->component->Text);
		try {
			$value2 = $this->component->Caption;
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		$this->assertTrue($this->component->OnMyEvent instanceof TPriorityList);
		try {
			$value2 = $this->component->onUndefinedEvent;
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		//Without the function parenthesis, the function is _not_ called but the __get
		//	method is called and the global events (list) are accessed
		$this->assertTrue($this->component->fxAttachClassBehavior instanceof TPriorityList);
		$this->assertTrue($this->component->fxDetachClassBehavior instanceof TPriorityList);

		// even undefined global events have a list as every object is able to access every event
		$this->assertTrue($this->component->fxUndefinedEvent instanceof TPriorityList);


		// Test the behaviors within the __get function
		$this->component->enableBehaviors();

		try {
			$value2 = $this->component->Excitement;
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		$this->component->attachBehavior('BehaviorTestBehavior', $behavior = new BehaviorTestBehavior);
		$this->assertEquals('faa', $this->component->Excitement);

		$this->component->disableBehaviors();

		try {
			$this->assertEquals('faa', $this->component->Excitement);
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		$this->component->enableBehaviors();
		$this->assertEquals('faa', $this->component->getExcitement());

		$this->component->disableBehavior('BehaviorTestBehavior');

		$this->assertEquals($behavior, $this->component->BehaviorTestBehavior);
		$this->assertEquals($behavior, $this->component->behaviortestbehavior);
		$this->assertEquals($behavior, $this->component->BEHAVIORTESTBEHAVIOR);
		try {
			$behavior = $this->component->BehaviorTestBehavior2;
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		try {
			$this->assertEquals('faa', $this->component->Excitement);
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}
		$this->component->enableBehavior('BehaviorTestBehavior');
		$this->assertEquals('faa', $this->component->getExcitement());


		// behaviors allow on and fx events to be passed through.
		$this->assertTrue($this->component->onBehaviorEvent instanceof TPriorityList);
	}

	public function testSetProperty()
	{
		$value = 'new value';
		$this->component->Text = $value;
		$text = $this->component->Text;
		$this->assertTrue($value === $this->component->Text);
		try {
			$this->component->NewMember = $value;
			$this->fail('exception not raised when setting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		// Test get only properties is a set function
		try {
			$this->component->ReadOnlyProperty = 'setting read only';
			$this->fail('a property without a set function was set to a new value without error');
		} catch (TInvalidOperationException $e) {
		}

		try {
			$this->component->ReadOnlyJsProperty = 'jssetting read only';
			$this->fail('a js property without a set function was set to a new value without error');
		} catch (TInvalidOperationException $e) {
		}

		try {
			$this->component->JsReadOnlyJsProperty = 'jssetting read only';
			$this->fail('a js property without a set function was set to a new value without error');
		} catch (TInvalidOperationException $e) {
		}

		$this->assertEquals(0, $this->component->getEventHandlers('onMyEvent')->getCount());
		$this->component->onMyEvent = [$this->component, 'myEventHandler'];
		$this->assertEquals(1, $this->component->getEventHandlers('onMyEvent')->getCount());
		$this->component->onMyEvent[] = [$this->component, 'Object.myEventHandler'];
		$this->assertEquals(2, $this->component->getEventHandlers('onMyEvent')->getCount());
		$c1 = new NewComponent();
		$c2 = new NewComponent();
		$this->component->onMyEvent = [[$c1, 'myEventHandler'], [$c2, 'myEventHandler']];
		$this->assertEquals(4, $this->component->getEventHandlers('onMyEvent')->getCount());

		$this->component->getEventHandlers('onMyEvent')->clear();

		// Test the behaviors within the __get function
		$this->component->enableBehaviors();

		try {
			$this->component->Excitement = 'laa';
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		$this->component->attachBehavior('BehaviorTestBehavior', $behavior1 = new BehaviorTestBehavior);
		$this->component->Excitement = 'laa';
		$this->assertEquals('laa', $this->component->Excitement);
		$this->assertEquals('sol', $this->component->Excitement = 'sol');


		$this->component->disableBehaviors();

		try {
			$this->component->Excitement = false;
			$this->assertEquals(false, $this->component->Excitement);
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}

		$this->component->enableBehaviors();
		$this->component->Excitement = 'faa';
		$this->assertEquals('faa', $this->component->getExcitement());

		$this->component->disableBehavior('BehaviorTestBehavior');

		try {
			$this->component->Excitement = false;
			$this->assertEquals(false, $this->component->Excitement);
			$this->fail('exception not raised when getting undefined property');
		} catch (TInvalidOperationException $e) {
		}
		$this->component->enableBehavior('BehaviorTestBehavior');
		$this->component->Excitement = 'sol';
		$this->assertEquals('sol', $this->component->Excitement);


		$this->component->attachBehavior('BehaviorTestBehavior2', $behavior2 = new BehaviorTestBehavior);

		$this->assertEquals('sol', $this->component->Excitement);
		$this->assertEquals('faa', $behavior2->Excitement);

		// this sets Excitement for both because they are not uniquely named
		$this->component->Excitement = 'befaad';

		$this->assertEquals('befaad', $this->component->Excitement);
		$this->assertEquals('befaad', $behavior1->Excitement);
		$this->assertEquals('befaad', $behavior2->Excitement);


		$this->component->detachBehavior('BehaviorTestBehavior2');

		// behaviors allow on and fx events to be passed through.
		$this->assertTrue($this->component->BehaviorTestBehavior->onBehaviorEvent instanceof TPriorityList);

		$this->assertEquals(0, $this->component->BehaviorTestBehavior->getEventHandlers('onBehaviorEvent')->getCount());
		$this->component->onBehaviorEvent = [$this->component, 'myEventHandler'];
		$this->assertEquals(1, $this->component->BehaviorTestBehavior->getEventHandlers('onBehaviorEvent')->getCount());
		$this->component->onBehaviorEvent[] = [$this->component, 'Object.myEventHandler'];
		$this->assertEquals(2, $this->component->BehaviorTestBehavior->getEventHandlers('onBehaviorEvent')->getCount());

		$this->component->BehaviorTestBehavior->getEventHandlers('onBehaviorEvent')->clear();
	}


	public function testIsSetFunction()
	{
		$this->assertTrue(isset($this->component->fxAttachClassBehavior));
		$this->component->unlisten();

		$this->assertFalse(isset($this->component->onMyEvent));
		$this->assertFalse(isset($this->component->undefinedEvent));
		$this->assertFalse(isset($this->component->fxAttachClassBehavior));

		$this->assertFalse(isset($this->component->BehaviorTestBehavior));
		$this->assertFalse(isset($this->component->onBehaviorEvent));

		$this->component->attachBehavior('BehaviorTestBehavior', new BehaviorTestBehavior());

		$this->assertTrue(isset($this->component->behaviortestbehavior));
		$this->assertTrue(isset($this->component->BehaviorTestBehavior));
		$this->assertTrue(isset($this->component->BEHAVIORTESTBEHAVIOR));
		$this->assertFalse(isset($this->component->onBehaviorEvent));

		$this->component->attachEventHandler('onBehaviorEvent', 'foo');
		$this->assertTrue(isset($this->component->onBehaviorEvent));

		$this->component->attachEventHandler('onMyEvent', 'foo');
		$this->assertTrue(isset($this->component->onMyEvent));

		$this->assertTrue(isset($this->component->Excitement));
		$this->component->Excitement = null;
		$this->assertFalse(isset($this->component->Excitement));
		$this->assertFalse(isset($this->component->UndefinedBehaviorProperty));
	}


	public function testUnsetFunction()
	{
		$this->assertEquals('default', $this->component->getText());
		unset($this->component->Text);
		$this->assertNull($this->component->getText());

		unset($this->component->UndefinedProperty);

		// object events
		$this->assertEquals(0, $this->component->onMyEvent->Count);
		$this->component->attachEventHandler('onMyEvent', 'foo');
		$this->assertEquals(1, $this->component->onMyEvent->Count);
		unset($this->component->onMyEvent);
		$this->assertEquals(0, $this->component->onMyEvent->Count);

		//global events
		$this->assertEquals(1, $this->component->fxAttachClassBehavior->Count);
		$component = new NewComponent();
		$this->assertEquals(2, $this->component->fxAttachClassBehavior->Count);
		unset($this->component->fxAttachClassBehavior);
		$this->assertEquals(1, $this->component->fxAttachClassBehavior->Count);
		try {
			unset($this->component->fxAttachClassBehaviors);
			$this->fail('TInvalidDataValueException not raised when unsetting an fxEvent that is not attached');
		} catch (\Prado\Exceptions\TInvalidDataValueException $e) {
		}
		$this->component->fxAttachClassBehavior[] = [$this->component, 'fxAttachClassBehavior'];
		$this->assertEquals(2, $this->component->fxAttachClassBehavior->Count);
		unset($this->component->fxAttachClassBehavior);

		// retain the other object event
		$this->assertEquals(1, $this->component->fxAttachClassBehavior->Count);
		$component->unlisten();

		try {
			unset($this->component->Object);
			$this->fail('TInvalidOperationException not raised when unsetting get only property');
		} catch (\Prado\Exceptions\TInvalidOperationException $e) {
		}
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior);
		$this->assertTrue($this->component->asa($behaviorTestBehaviorName) instanceof BehaviorTestBehavior);
		$this->assertFalse($this->component->asa('BehaviorTestBehavior2') instanceof BehaviorTestBehavior);

		$this->assertEquals('faa', $this->component->Excitement);
		unset($this->component->Excitement);
		$this->assertNull($this->component->Excitement);
		$this->component->Excitement = 'sol';
		$this->assertEquals('sol', $this->component->Excitement);

		// Test the disabling of unset within behaviors
		$this->component->disableBehaviors();
		unset($this->component->Excitement);
		$this->component->enableBehaviors();
		// This should still be 'sol'  because the unset happened inside behaviors being disabled
		$this->assertEquals('sol', $this->component->Excitement);
		$this->component->disableBehavior($behaviorTestBehaviorName);
		unset($this->component->Excitement);
		$this->component->enableBehavior($behaviorTestBehaviorName);
		$this->assertEquals('sol', $this->component->Excitement);

		unset($this->component->Excitement);
		$this->assertNull($this->component->Excitement);

		try {
			unset($this->component->ReadOnly);
			$this->fail('TInvalidOperationException not raised when unsetting get only property');
		} catch (TInvalidOperationException $e) {
		}

		$this->component->onBehaviorEvent = 'foo';
		$this->assertEquals(1, count($this->component->onBehaviorEvent));
		$this->assertEquals(1, count($this->component->$behaviorTestBehaviorName->onBehaviorEvent));
		unset($this->component->onBehaviorEvent);
		$this->assertEquals(0, count($this->component->onBehaviorEvent));
		$this->assertEquals(0, count($this->component->$behaviorTestBehaviorName->onBehaviorEvent));

		// Remove behavior via unset
		unset($this->component->$behaviorTestBehaviorName);
		$this->assertFalse($this->component->asa($behaviorTestBehaviorName) instanceof BehaviorTestBehavior);
	}

	public function testGetSubProperty()
	{
		$this->assertTrue('object text' === $this->component->getSubProperty('Object.Text'));
	}

	public function testSetSubProperty()
	{
		$this->component->setSubProperty('Object.Text', 'new object text');
		$this->assertEquals('new object text', $this->component->getSubProperty('Object.Text'));
	}

	/**
	 * The '@' separator reads an attached behavior via {@see TComponent::asa}.
	 */
	public function testGetSubPropertyBehaviorPath()
	{
		$behavior = new BehaviorTestBehavior();
		$this->component->attachBehavior('beh', $behavior);

		// A leading '@' resolves the first name as a behavior on this component.
		$this->assertEquals('faa', $this->component->getSubProperty('@beh.Excitement'));

		// Behavior name resolution is case-insensitive, like asa().
		$this->assertEquals('faa', $this->component->getSubProperty('@BEH.Excitement'));

		// '@' chains: a behavior attached to a behavior, with no '.' between.
		$inner = new BehaviorTestBehavior();
		$inner->setExcitement('deep');
		$behavior->attachBehavior('sub', $inner);
		$this->assertEquals('deep', $this->component->getSubProperty('@beh@sub.Excitement'));

		// A '.' property hop into a '@' behavior hop.
		$inner2 = new BehaviorTestBehavior();
		$inner2->setExcitement('objbeh');
		$this->component->getObject()->attachBehavior('ob', $inner2);
		$this->assertEquals('objbeh', $this->component->getSubProperty('Object@ob.Excitement'));

		// A missing behavior as the final name resolves to null (asa() miss).
		$this->assertNull($this->component->getSubProperty('@missing'));
	}

	/**
	 * setSubProperty resolves a '@' behavior prefix before writing the property.
	 */
	public function testSetSubPropertyBehaviorPath()
	{
		$behavior = new BehaviorTestBehavior();
		$this->component->attachBehavior('beh', $behavior);

		$this->component->setSubProperty('@beh.Excitement', 'set-on-behavior');
		$this->assertEquals('set-on-behavior', $behavior->Excitement);
		$this->assertEquals('set-on-behavior', $this->component->getSubProperty('@beh.Excitement'));

		// Property hop then behavior hop, then write.
		$inner = new BehaviorTestBehavior();
		$this->component->getObject()->attachBehavior('ob', $inner);
		$this->component->setSubProperty('Object@ob.Excitement', 'set-deep');
		$this->assertEquals('set-deep', $inner->Excitement);
	}

	/**
	 * A path terminating in an '@' behavior hop addresses a behavior, not a
	 * settable property, so setSubProperty is a no-op.
	 */
	public function testSetSubPropertyTerminalAtIsNoop()
	{
		$this->component->setText('default');
		$this->component->setSubProperty('Text@foo', 'changed'); // terminal '@' segment
		$this->assertSame('default', $this->component->getText());
		// Bare behavior address and a deeper behavior terminal also no-op.
		$this->component->setSubProperty('@beh', 'x');
		$this->component->setSubProperty('Object@ob', 'x');
		$this->assertTrue(true); // no exception raised
	}

	public function testSetSubPropertiesAppliesAll()
	{
		$behavior = new BehaviorTestBehavior();
		$this->component->attachBehavior('beh', $behavior);
		$this->component->setSubProperties([
			'Object.Text' => 'deep',
			'Text' => 'top',
			'@beh.Excitement' => 'e',
		]);
		$this->assertSame('top', $this->component->getText());
		$this->assertSame('deep', $this->component->getObject()->getText());
		$this->assertSame('e', $behavior->Excitement);
	}

	/**
	 * sortPropertyPaths orders ancestors before descendants, '@' before '.' at a
	 * divergence, and same-separator siblings in declaration order.
	 */
	public function testSortPropertyPathsStructuralOrder()
	{
		$sorted = PradoUnit::invoke($this->component, 'sortPropertyPaths', [
			'a.b' => 1,
			'a' => 2,
			'c' => 3,
			'a@x.p' => 4,
			'a.b.c' => 5,
		]);
		$this->assertSame(['a', 'a@x.p', 'a.b', 'a.b.c', 'c'], array_keys($sorted));
	}

	public function testSortPropertyPathsPreservesSiblingDeclarationOrder()
	{
		$sorted = PradoUnit::invoke($this->component, 'sortPropertyPaths', [
			'Width' => 1, 'Height' => 2, 'Color' => 3,
		]);
		$this->assertSame(['Width', 'Height', 'Color'], array_keys($sorted));

		$reversed = PradoUnit::invoke($this->component, 'sortPropertyPaths', [
			'Color' => 1, 'Height' => 2, 'Width' => 3,
		]);
		$this->assertSame(['Color', 'Height', 'Width'], array_keys($reversed));
	}

	public function testHasMethod()
	{
		$behaviorTestBehaviorName = 'BehaviorTestBehaviorName';
		$this->assertTrue($this->component->hasMethod('eventReturnValue'));
		$this->assertTrue($this->component->hasMethod('eventreturnvalue'));
		$this->assertFalse($this->component->hasMethod('noeventreturnvalue'));

		// fx won't throw an error if any of these fx function are called on an object.
		//	It is a special prefix event designation that every object responds to all events/methods.
		$this->assertTrue($this->component->hasMethod('fxAttachClassBehavior'));
		$this->assertTrue($this->component->hasMethod('fxattachclassbehavior'));

		$this->assertFalse($this->component->hasMethod('fxNonExistantGlobalEvent'));
		$this->assertFalse($this->component->hasMethod('fxnonexistantglobalevent'));

		$this->assertTrue($this->component->hasMethod('dyNonExistantLocalEvent'));
		$this->assertTrue($this->component->hasMethod('dynonexistantlocalevent'));


		//Test behavior events
		$this->assertFalse($this->component->hasMethod('getExcitement'));
		$this->component->attachBehavior($behaviorTestBehaviorName, new BehaviorTestBehavior());
		$this->assertTrue($this->component->hasMethod('getExcitement'));
		$this->assertTrue($this->component->$behaviorTestBehaviorName->hasMethod('getExcitement'));

		//Test behaviors within behaviors.
		$this->component->$behaviorTestBehaviorName->attachBehavior('SubBehavior', new FooFooClassBehavior());
		$this->assertTrue($this->component->$behaviorTestBehaviorName->hasMethod('faafaaEverMore'));
		$this->assertFalse($this->component->hasMethod('faafaaEverMore'));
		$this->assertEquals('ffemResult', $this->component->$behaviorTestBehaviorName->faafaaEverMore(null, null, null));
		try {
			$this->component->faafaaEverMore(null, null, null);
			$this->fail('TUnknownMethodException not raised when calling a behaviors behaviors method');
		} catch (\Prado\Exceptions\TUnknownMethodException $e) {
		}


		$this->component->disableBehavior($behaviorTestBehaviorName);
		$this->assertFalse($this->component->hasMethod('getExcitement'));
		$this->component->enableBehavior($behaviorTestBehaviorName);
		$this->assertTrue($this->component->hasMethod('getExcitement'));

		$this->component->disableBehaviors();
		$this->assertFalse($this->component->hasMethod('getExcitement'));
		$this->component->enableBehaviors();
		$this->assertTrue($this->component->hasMethod('getExcitement'));
	}

	public function testJavascriptGetterSetter()
	{
		$this->assertFalse(isset($this->component->ColorAttribute));
		$this->assertFalse(isset($this->component->JsColorAttribute));

		$this->component->ColorAttribute = "('#556677', '#abcdef', 503987)";
		$this->assertEquals("('#556677', '#abcdef', 503987)", $this->component->ColorAttribute);

		$this->assertTrue(isset($this->component->ColorAttribute));
		$this->assertTrue(isset($this->component->JsColorAttribute));

		$this->component->ColorAttribute = "new Array(1, 2, 3, 4, 5)";
		$this->assertEquals("new Array(1, 2, 3, 4, 5)", $this->component->JsColorAttribute);

		$this->component->JsColorAttribute = "['#112233', '#fedcba', 22009837]";
		$this->assertEquals("['#112233', '#fedcba', 22009837]", $this->component->ColorAttribute);
	}


	public function testJavascriptIssetUnset()
	{
		$this->component->JsColorAttribute = "['#112233', '#fedcba', 22009837]";
		$this->assertEquals("['#112233', '#fedcba', 22009837]", $this->component->ColorAttribute);

		unset($this->component->ColorAttribute);

		$this->assertFalse(isset($this->component->ColorAttribute));
		$this->assertFalse(isset($this->component->JsColorAttribute));

		$this->component->JsColorAttribute = "['#112233', '#fedcba', 22009837]";

		$this->assertTrue(isset($this->component->ColorAttribute));
		$this->assertTrue(isset($this->component->JsColorAttribute));

		unset($this->component->JsColorAttribute);

		$this->assertFalse(isset($this->component->ColorAttribute));
		$this->assertFalse(isset($this->component->JsColorAttribute));
	}

	public function testProtectedSetter()
	{
		// Calling protected method doesn't fail, but doesn't call the method
		//   and returns null.
		$this->assertEquals(null, $this->component->getProtectedValue());

		//
		$value = null;
		try {
			$value = $this->component->ProtectedValue;
			$this->fail("TInvalidOperationException was not properly thrown when accessing a protected property.");
		} catch(TInvalidOperationException $e) {
		}
		$this->assertEquals(null, $value);
	}
}
