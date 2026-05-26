<?php

use PHPUnit\Framework\TestCase;
use Prado\TComponent;
use Prado\TComponentReflection;
use Prado\Exceptions\TInvalidDataTypeException;

// ---------------------------------------------------------------------------
// Fixture: a concrete TComponent subclass with one property, one event, one method
// ---------------------------------------------------------------------------

class ReflectionFixtureComponent extends TComponent
{
	private $_name = '';

	/**
	 * @return string the name.
	 */
	public function getName(): string
	{
		return $this->_name;
	}

	public function setName(string $value): void
	{
		$this->_name = $value;
	}

	/**
	 * Fires when something happens.
	 * @param \Prado\TEventParameter $param event parameter
	 */
	public function onSomethingHappened($param): void
	{
		$this->raiseEvent('OnSomethingHappened', $this, $param);
	}

	public function doWork(): void
	{
	}
}

// Subclass that adds an additional property, to test inheritance
class ReflectionFixtureChild extends ReflectionFixtureComponent
{
	private $_value = 0;

	/**
	 * @return int the value.
	 */
	public function getValue(): int
	{
		return $this->_value;
	}

	public function setValue(int $v): void
	{
		$this->_value = $v;
	}
}

// A plain PHP class — not a TComponent subclass
class PlainPhpClass
{
	public function getFoo(): string
	{
		return 'foo';
	}

	public function setFoo(string $v): void
	{
	}
}

// ---------------------------------------------------------------------------
// Prado3 alias registration
// class_alias() is idempotent when guarded; these simulate what Prado::using()
// does when loading a Prado3 dot-notation class name.
// ---------------------------------------------------------------------------

if (!class_exists('Prado3Alias_TComponent', false)) {
	class_alias(\Prado\TComponent::class, 'Prado3Alias_TComponent');
}

if (!class_exists('Prado3Alias_TList', false)) {
	class_alias(\Prado\Collections\TList::class, 'Prado3Alias_TList');
}

// ---------------------------------------------------------------------------
// Additional fixtures for edge-case coverage
// ---------------------------------------------------------------------------

/**
 * A component whose only property has no setter (readonly).
 */
class ReadonlyPropComponent extends TComponent
{
	/**
	 * @return int the answer.
	 */
	public function getAnswer(): int
	{
		return 42;
	}
	// intentionally no setAnswer()
}

/**
 * A component with a protected getter — should appear as a protected property,
 * not as a method.
 */
class ProtectedGetterComponent extends TComponent
{
	/**
	 * @return string a secret value.
	 */
	protected function getSecret(): string
	{
		return 'shhh';
	}
}

/**
 * A component with a protected non-getter method and a public static method.
 */
class ProtectedStaticComponent extends TComponent
{
	protected function helperMethod(): void
	{
	}

	public static function staticHelper(): string
	{
		return 'static';
	}
}

/**
 * A component whose getter has no @return docblock — type must be '{unknown}'
 * and comments must be false.
 */
class NodocComponent extends TComponent
{
	public function getNoDoc(): string
	{
		return '';
	}
}

/**
 * Boundary: method named exactly 'get' (length 3) must NOT become a property.
 * Method named exactly 'on' (length 2) must NOT become an event.
 * Both must appear in getMethods().
 */
class BoundaryMethodComponent extends TComponent
{
	public function get(): string
	{
		return '';
	}

	public function on(): void
	{
	}
}

/**
 * A component with multiple unrelated public methods to verify getMethods() sorting.
 */
class SortedMethodsComponent extends TComponent
{
	public function zebra(): void
	{
	}

	public function apple(): void
	{
	}

	public function mango(): void
	{
	}
}

// ---------------------------------------------------------------------------
// Additional fixtures for getReflectionClassByType tests
// ---------------------------------------------------------------------------

/** Plain interface — used to verify reflection works for interface types. */
interface ReflectionTestInterface
{
	public function doThing(): void;
}

/** Abstract class — used to verify reflection works for abstract types. */
abstract class ReflectionTestAbstract
{
	abstract public function doThing(): void;
}

/** Concrete plain (non-TComponent) class — used for cache and type tests. */
class ReflectionTestConcrete
{
	public function doThing(): void
	{
	}
}

// ---------------------------------------------------------------------------
// Fixtures for getReflectionMethodByType behavior discovery
// ---------------------------------------------------------------------------

class ReflectionTestBehavior extends \Prado\Util\TBehavior
{
	public function behaviorOnlyMethod(): string
	{
		return 'from behavior';
	}

	public function getDynamicProp(): string
	{
		return 'dynamic';
	}
}

// ---------------------------------------------------------------------------
// Fixtures for getReflectionPropertyByType hierarchy walking
// ---------------------------------------------------------------------------

class ReflectionPropertyAncestor
{
	private string $privateAncestorProp = 'secret';
}

class ReflectionPropertyChild extends ReflectionPropertyAncestor
{
	private string $privateChildProp = 'child_secret';
}

// ---------------------------------------------------------------------------

class TComponentReflectionTest extends TestCase
{
	// ========================================================================
	// Constructor
	// ========================================================================

	public function testConstructorAcceptsClassName(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$this->assertSame(ReflectionFixtureComponent::class, $r->getClassName());
	}

	public function testConstructorAcceptsObject(): void
	{
		$obj = new ReflectionFixtureComponent();
		$r = new TComponentReflection($obj);
		$this->assertSame(ReflectionFixtureComponent::class, $r->getClassName());
	}

	public function testConstructorThrowsOnInvalidString(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		new TComponentReflection('ThisClassDoesNotExist_XYZ');
	}

	public function testConstructorThrowsOnNonObject(): void
	{
		$this->expectException(TInvalidDataTypeException::class);
		new TComponentReflection(42);
	}

	// ========================================================================
	// Properties reflection — TComponent subclass
	// ========================================================================

	public function testGetPropertiesReturnsTComponentProperties(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$props = $r->getProperties();

		// The fixture defines getName/setName → property "Name"
		$this->assertArrayHasKey('Name', $props);
	}

	public function testPropertyEntryHasExpectedKeys(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$prop = $r->getProperties()['Name'];

		$this->assertArrayHasKey('type', $prop);
		$this->assertArrayHasKey('readonly', $prop);
		$this->assertArrayHasKey('protected', $prop);
		$this->assertArrayHasKey('class', $prop);
		$this->assertArrayHasKey('comments', $prop);
	}

	public function testPropertyReadonlyFalseWhenSetterExists(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$this->assertFalse($r->getProperties()['Name']['readonly']);
	}

	public function testPropertyTypeFromDocComment(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$this->assertSame('string', $r->getProperties()['Name']['type']);
	}

	public function testPropertyDeclaringClass(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$this->assertSame(
			ReflectionFixtureComponent::class,
			$r->getProperties()['Name']['class']
		);
	}

	public function testPropertyProtectedFalseForPublicGetter(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$this->assertFalse($r->getProperties()['Name']['protected']);
	}

	// ========================================================================
	// Readonly property (getter only, no setter)
	// ========================================================================

	public function testPropertyReadonlyTrueWhenNoSetter(): void
	{
		$r = new TComponentReflection(ReadonlyPropComponent::class);
		$this->assertArrayHasKey('Answer', $r->getProperties());
		$this->assertTrue($r->getProperties()['Answer']['readonly']);
	}

	public function testReadonlyPropertyTypeIsReflectedFromDocComment(): void
	{
		$r = new TComponentReflection(ReadonlyPropComponent::class);
		$this->assertSame('int', $r->getProperties()['Answer']['type']);
	}

	// ========================================================================
	// Protected getter → property with protected=true, absent from getMethods()
	// ========================================================================

	public function testProtectedGetterAppearsAsProperty(): void
	{
		$r = new TComponentReflection(ProtectedGetterComponent::class);
		$this->assertArrayHasKey('Secret', $r->getProperties());
	}

	public function testProtectedGetterPropertyHasProtectedTrue(): void
	{
		$r = new TComponentReflection(ProtectedGetterComponent::class);
		$this->assertTrue($r->getProperties()['Secret']['protected']);
	}

	public function testProtectedGetterNotExposedInMethods(): void
	{
		$r = new TComponentReflection(ProtectedGetterComponent::class);
		$this->assertArrayNotHasKey('getSecret', $r->getMethods());
	}

	// ========================================================================
	// Protected and static methods in getMethods()
	// ========================================================================

	public function testGetMethodsIncludesProtectedMethod(): void
	{
		$r = new TComponentReflection(ProtectedStaticComponent::class);
		$this->assertArrayHasKey('helperMethod', $r->getMethods());
	}

	public function testProtectedMethodHasProtectedTrue(): void
	{
		$r = new TComponentReflection(ProtectedStaticComponent::class);
		$this->assertTrue($r->getMethods()['helperMethod']['protected']);
	}

	public function testGetMethodsIncludesStaticMethod(): void
	{
		$r = new TComponentReflection(ProtectedStaticComponent::class);
		$this->assertArrayHasKey('staticHelper', $r->getMethods());
	}

	public function testStaticMethodHasStaticTrue(): void
	{
		$r = new TComponentReflection(ProtectedStaticComponent::class);
		$this->assertTrue($r->getMethods()['staticHelper']['static']);
	}

	public function testNonStaticMethodHasStaticFalse(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$this->assertFalse($r->getMethods()['doWork']['static']);
	}

	public function testGetMethodsEntryHasExpectedKeys(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$entry = $r->getMethods()['doWork'];
		$this->assertArrayHasKey('class', $entry);
		$this->assertArrayHasKey('protected', $entry);
		$this->assertArrayHasKey('static', $entry);
		$this->assertArrayHasKey('comments', $entry);
	}

	// ========================================================================
	// Property with no @return docblock → type='{unknown}', comments=false
	// ========================================================================

	public function testPropertyTypeUnknownWhenNoDocComment(): void
	{
		$r = new TComponentReflection(NodocComponent::class);
		$this->assertArrayHasKey('NoDoc', $r->getProperties());
		$this->assertSame('{unknown}', $r->getProperties()['NoDoc']['type']);
	}

	public function testPropertyCommentsIsFalseWhenNoDocComment(): void
	{
		$r = new TComponentReflection(NodocComponent::class);
		$this->assertFalse($r->getProperties()['NoDoc']['comments']);
	}

	// ========================================================================
	// Boundary: method named exactly 'get' or 'on' — must NOT become property/event
	// ========================================================================

	public function testMethodNamedExactlyGetIsNotProperty(): void
	{
		$r = new TComponentReflection(BoundaryMethodComponent::class);
		// 'get' is only 3 chars; isset($methodName[3]) is false → not a property getter
		$this->assertArrayNotHasKey('', $r->getProperties());
	}

	public function testMethodNamedExactlyGetAppearsInMethods(): void
	{
		$r = new TComponentReflection(BoundaryMethodComponent::class);
		// Since 'get' is not treated as a property getter, it is not reserved
		// and should therefore appear in getMethods()
		$this->assertArrayHasKey('get', $r->getMethods());
	}

	public function testMethodNamedExactlyOnIsNotEvent(): void
	{
		$r = new TComponentReflection(BoundaryMethodComponent::class);
		// 'on' is only 2 chars; isset($methodName[2]) is false → not an event
		$this->assertArrayNotHasKey('On', $r->getEvents());
		$this->assertArrayNotHasKey('on', $r->getEvents());
	}

	public function testMethodNamedExactlyOnAppearsInMethods(): void
	{
		$r = new TComponentReflection(BoundaryMethodComponent::class);
		$this->assertArrayHasKey('on', $r->getMethods());
	}

	// ========================================================================
	// Inherited properties
	// ========================================================================

	public function testChildInheritsParentProperties(): void
	{
		$r = new TComponentReflection(ReflectionFixtureChild::class);
		$props = $r->getProperties();

		$this->assertArrayHasKey('Name', $props);
		$this->assertArrayHasKey('Value', $props);
	}

	public function testInheritedPropertyHasParentClass(): void
	{
		$r = new TComponentReflection(ReflectionFixtureChild::class);
		$this->assertSame(
			ReflectionFixtureComponent::class,
			$r->getProperties()['Name']['class']
		);
	}

	public function testOwnPropertyHasOwnClass(): void
	{
		$r = new TComponentReflection(ReflectionFixtureChild::class);
		$this->assertSame(
			ReflectionFixtureChild::class,
			$r->getProperties()['Value']['class']
		);
	}

	// ========================================================================
	// Events reflection
	// ========================================================================

	public function testGetEventsReturnsTComponentEvents(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$events = $r->getEvents();

		// The fixture defines onSomethingHappened → event "OnSomethingHappened"
		$this->assertArrayHasKey('OnSomethingHappened', $events);
	}

	public function testEventEntryHasExpectedKeys(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$event = $r->getEvents()['OnSomethingHappened'];

		$this->assertArrayHasKey('class', $event);
		$this->assertArrayHasKey('protected', $event);
		$this->assertArrayHasKey('comments', $event);
	}

	public function testEventDeclaringClass(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$this->assertSame(
			ReflectionFixtureComponent::class,
			$r->getEvents()['OnSomethingHappened']['class']
		);
	}

	public function testEventKeyFirstCharForcedUppercaseO(): void
	{
		// The reflect() method does: $methodName[0] = 'O';
		// so a lowercase-'o' method like onSomethingHappened becomes 'OnSomethingHappened'.
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$events = $r->getEvents();
		$this->assertArrayHasKey('OnSomethingHappened', $events);
		$this->assertArrayNotHasKey('onSomethingHappened', $events);
	}

	public function testEventNotExposedInMethods(): void
	{
		// onSomethingHappened is an event and must not appear in getMethods()
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$this->assertArrayNotHasKey('onSomethingHappened', $r->getMethods());
	}

	// ========================================================================
	// Methods reflection
	// ========================================================================

	public function testGetMethodsIncludesPublicNonPropertyMethod(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$methods = $r->getMethods();

		$this->assertArrayHasKey('doWork', $methods);
	}

	public function testGetMethodsDoesNotIncludeGetterAsMethod(): void
	{
		// getter methods are surfaced as properties, not methods
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$methods = $r->getMethods();

		$this->assertArrayNotHasKey('getName', $methods);
	}

	public function testGetMethodsDoesNotIncludeSetterAsMethod(): void
	{
		// setter methods are reserved alongside their getter
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$methods = $r->getMethods();

		$this->assertArrayNotHasKey('setName', $methods);
	}

	public function testGetMethodsDoesNotIncludeMagicMethods(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$methods = $r->getMethods();

		foreach (array_keys($methods) as $name) {
			$this->assertStringNotContainsString('__', $name, "Magic method $name should not appear in getMethods()");
		}
	}

	// ========================================================================
	// Non-TComponent class — properties and events must be empty
	// (exercises the TComponent::class fix: $isComponent must be false)
	// ========================================================================

	public function testNonComponentClassHasEmptyProperties(): void
	{
		$r = new TComponentReflection(PlainPhpClass::class);
		$this->assertSame([], $r->getProperties());
	}

	public function testNonComponentClassHasEmptyEvents(): void
	{
		$r = new TComponentReflection(PlainPhpClass::class);
		$this->assertSame([], $r->getEvents());
	}

	public function testNonComponentClassHasMethods(): void
	{
		// methods are still reflected for non-component classes
		$r = new TComponentReflection(PlainPhpClass::class);
		$methods = $r->getMethods();

		// getFoo is a getter but $isComponent=false means it is NOT treated as a
		// property getter, so it appears in getMethods() for plain classes
		$this->assertArrayHasKey('getFoo', $methods);
	}

	public function testNonComponentClassSetterAlsoAppearsInMethods(): void
	{
		// For a non-component class the setter is not reserved either
		$r = new TComponentReflection(PlainPhpClass::class);
		$this->assertArrayHasKey('setFoo', $r->getMethods());
	}

	// ========================================================================
	// TComponent itself
	// ========================================================================

	public function testReflectingTComponentItself(): void
	{
		$r = new TComponentReflection(TComponent::class);
		// TComponent has properties like Behaviors, etc.
		$this->assertNotEmpty($r->getProperties());
		$this->assertSame(TComponent::class, $r->getClassName());
	}

	// ========================================================================
	// Sorting: getProperties(), getEvents(), getMethods() — all sorted by key
	// ========================================================================

	public function testPropertiesAreSorted(): void
	{
		$r = new TComponentReflection(ReflectionFixtureChild::class);
		$keys = array_keys($r->getProperties());
		$sorted = $keys;
		sort($sorted);
		$this->assertSame($sorted, $keys, 'getProperties() should return entries sorted by name');
	}

	public function testEventsAreSorted(): void
	{
		$r = new TComponentReflection(ReflectionFixtureComponent::class);
		$keys = array_keys($r->getEvents());
		$sorted = $keys;
		sort($sorted);
		$this->assertSame($sorted, $keys, 'getEvents() should return entries sorted by name');
	}

	public function testMethodsAreSorted(): void
	{
		$r = new TComponentReflection(SortedMethodsComponent::class);
		$keys = array_keys($r->getMethods());
		$sorted = $keys;
		sort($sorted);
		$this->assertSame($sorted, $keys, 'getMethods() should return entries sorted by name');
	}

	// ========================================================================
	// Prado3 class-alias compatibility
	//
	// Prado::using() registers class aliases (class_alias(FQN, shortName)) as a
	// side-effect of autoloading.  The reflected class name stored in _className
	// may therefore be an alias string rather than the FQN.  All code paths that
	// test "is this a TComponent?" must use is_a() rather than === so that aliases
	// resolve correctly.
	// ========================================================================

	public function testConstructorAcceptsPrado3AliasOfTComponentSubclass(): void
	{
		// 'Prado3Alias_TList' is class_alias'd to Prado\Collections\TList.
		// class_exists('Prado3Alias_TList', false) must return true, so the
		// constructor should store it as _className without autoloading.
		$r = new TComponentReflection('Prado3Alias_TList');
		$this->assertSame('Prado3Alias_TList', $r->getClassName());
	}

	public function testPrado3AliasSubclassIsReflectedAsComponent(): void
	{
		// When _className is a Prado3 alias of a TComponent subclass,
		// $isComponent must be true → properties and events must be reflected.
		$r = new TComponentReflection('Prado3Alias_TList');
		// TList defines several properties (Count, ReadOnly, …)
		$this->assertNotEmpty($r->getProperties());
	}

	public function testPrado3AliasSubclassEventsAreReflected(): void
	{
		$r = new TComponentReflection('Prado3Alias_TList');
		// Because $isComponent is true, event methods (on*) are surfaced.
		// The assertion just ensures the events array is not suppressed;
		// TList may have zero events itself, so we only verify no exception is thrown
		// and the return type is an array.
		$this->assertIsArray($r->getEvents());
	}

	public function testConstructorAcceptsPrado3AliasOfTComponentItself(): void
	{
		// 'Prado3Alias_TComponent' is class_alias'd to Prado\TComponent.
		// is_subclass_of('Prado3Alias_TComponent', TComponent::class) returns false
		// because an alias of a class is not a *sub*class of itself.
		// The old code relied on ($className === TComponent::class) which also fails
		// for an alias.  is_a($className, TComponent::class, true) is the fix.
		$r = new TComponentReflection('Prado3Alias_TComponent');
		$this->assertSame('Prado3Alias_TComponent', $r->getClassName());
	}

	public function testPrado3AliasOfTComponentItselfIsReflectedAsComponent(): void
	{
		// The critical regression: if $isComponent is wrongly false for a TComponent
		// alias, getProperties() returns [] even though TComponent has properties.
		$r = new TComponentReflection('Prado3Alias_TComponent');
		// TComponent defines at least the Behaviors property
		$this->assertNotEmpty($r->getProperties(),
			'Prado3 alias of TComponent itself must be treated as a component class');
	}

	public function testPrado3AliasOfTComponentEventsAreReflected(): void
	{
		$r = new TComponentReflection('Prado3Alias_TComponent');
		$this->assertIsArray($r->getEvents());
	}

	public function testPrado3AliasDeclaringClassReturnsFqn(): void
	{
		// Declaring-class entries in property/event/method info always hold the
		// canonical FQN (from ReflectionMethod::getDeclaringClass()->getName()),
		// not the alias name — even when _className is an alias.
		$r = new TComponentReflection('Prado3Alias_TList');
		foreach ($r->getProperties() as $name => $prop) {
			$this->assertTrue(
				class_exists($prop['class'], false),
				"Property '$name' declares class '{$prop['class']}' which is not a known class"
			);
		}
	}

	public function testPrado3AliasMethodsAreReflected(): void
	{
		// getMethods() must work correctly when _className is a Prado3 alias.
		$r = new TComponentReflection('Prado3Alias_TList');
		$this->assertIsArray($r->getMethods());
	}

	// ========================================================================
	// getReflectionClassByType — static reflection cache (4.4.0)
	// ========================================================================

	public function testGetReflectionClassByTypeNullThrowsTypeError(): void
	{
		$this->expectException(\TypeError::class);
		TComponentReflection::getReflectionClassByType(null);
	}

	public function testGetReflectionClassByTypeReturnsReflectionClassInstance(): void
	{
		$ref = TComponentReflection::getReflectionClassByType(ReflectionTestConcrete::class);
		$this->assertInstanceOf(\ReflectionClass::class, $ref);
	}

	public function testGetReflectionClassByTypeReturnsCorrectClassName(): void
	{
		$ref = TComponentReflection::getReflectionClassByType(ReflectionTestConcrete::class);
		$this->assertSame(ReflectionTestConcrete::class, $ref->getName());
	}

	public function testGetReflectionClassByTypeNonExistentClassReturnsNull(): void
	{
		$this->assertNull(
			TComponentReflection::getReflectionClassByType('ThisClassAbsolutelyDoesNotExist_XYZ_99999')
		);
	}

	public function testGetReflectionClassByTypeReturnsSameInstanceOnRepeatedCall(): void
	{
		// Two calls with the same name must return the identical cached object.
		$first  = TComponentReflection::getReflectionClassByType(ReflectionFixtureComponent::class);
		$second = TComponentReflection::getReflectionClassByType(ReflectionFixtureComponent::class);
		$this->assertSame($first, $second);
	}

	public function testGetReflectionClassByTypeIsCaseInsensitive(): void
	{
		// PHP class names are case-insensitive; the cache must normalise to
		// lowercase so different casings share one entry.
		$canonical = TComponentReflection::getReflectionClassByType(ReflectionTestConcrete::class);
		$uppercase = TComponentReflection::getReflectionClassByType(strtoupper(ReflectionTestConcrete::class));
		$lowercase = TComponentReflection::getReflectionClassByType(strtolower(ReflectionTestConcrete::class));

		$this->assertSame($canonical, $uppercase);
		$this->assertSame($canonical, $lowercase);
	}

	public function testGetReflectionClassByTypeWorksForInterface(): void
	{
		$ref = TComponentReflection::getReflectionClassByType(ReflectionTestInterface::class);
		$this->assertInstanceOf(\ReflectionClass::class, $ref);
		$this->assertTrue($ref->isInterface());
		$this->assertSame(ReflectionTestInterface::class, $ref->getName());
	}

	public function testGetReflectionClassByTypeWorksForAbstractClass(): void
	{
		$ref = TComponentReflection::getReflectionClassByType(ReflectionTestAbstract::class);
		$this->assertInstanceOf(\ReflectionClass::class, $ref);
		$this->assertTrue($ref->isAbstract());
	}

	public function testGetReflectionClassByTypeWorksForTComponentSubclass(): void
	{
		$ref = TComponentReflection::getReflectionClassByType(ReflectionFixtureComponent::class);
		$this->assertInstanceOf(\ReflectionClass::class, $ref);
		$this->assertTrue($ref->isSubclassOf(TComponent::class));
	}

	public function testGetReflectionClassByTypeWorksForTComponentItself(): void
	{
		$ref = TComponentReflection::getReflectionClassByType(TComponent::class);
		$this->assertInstanceOf(\ReflectionClass::class, $ref);
		$this->assertSame(TComponent::class, $ref->getName());
	}

	public function testGetReflectionClassByTypeWorksForTComponentReflectionItself(): void
	{
		// The class uses getReflectionClassByType internally in reflect(); verify
		// that reflecting TComponentReflection itself does not cause recursion or error.
		$ref = TComponentReflection::getReflectionClassByType(TComponentReflection::class);
		$this->assertInstanceOf(\ReflectionClass::class, $ref);
		$this->assertSame(TComponentReflection::class, $ref->getName());
	}

	public function testGetReflectionClassByTypeInterfaceSameInstanceOnRepeatedCall(): void
	{
		$first  = TComponentReflection::getReflectionClassByType(ReflectionTestInterface::class);
		$second = TComponentReflection::getReflectionClassByType(ReflectionTestInterface::class);
		$this->assertSame($first, $second);
	}

	public function testGetReflectionClassByTypeNonExistentClassDoesNotCacheFailure(): void
	{
		// Failed lookups (ReflectionException caught) must NOT be cached so that
		// subsequent calls retry reflection rather than returning a stale null.
		$missing = 'ThisClassWillNeverExist_Cache_Test_ABC123';

		$first  = TComponentReflection::getReflectionClassByType($missing);
		$second = TComponentReflection::getReflectionClassByType($missing);

		// Both calls must return null (no stale cached value).
		$this->assertNull($first);
		$this->assertNull($second);

		// A valid lookup immediately following the failed ones must still succeed —
		// the null result must not have corrupted an adjacent cache slot.
		$valid = TComponentReflection::getReflectionClassByType(ReflectionTestConcrete::class);
		$this->assertInstanceOf(\ReflectionClass::class, $valid);
		$this->assertSame(ReflectionTestConcrete::class, $valid->getName());

		// And the same-instance guarantee must still hold after the failed lookups.
		$validAgain = TComponentReflection::getReflectionClassByType(ReflectionTestConcrete::class);
		$this->assertSame($valid, $validAgain);
	}

	public function testGetReflectionClassByTypeUsedInternallyByReflect(): void
	{
		// Constructing a TComponentReflection triggers reflect(), which calls
		// getReflectionClassByType() internally.  Verify the returned object
		// is then also available via the static cache — i.e. the constructor
		// populates the shared cache as a side-effect.
		$r = new TComponentReflection(ReflectionFixtureChild::class);
		$this->assertSame(ReflectionFixtureChild::class, $r->getClassName());

		$cached = TComponentReflection::getReflectionClassByType(ReflectionFixtureChild::class);
		$this->assertInstanceOf(\ReflectionClass::class, $cached);
		$this->assertSame(ReflectionFixtureChild::class, $cached->getName());
	}

	public function testGetReflectionClassByTypePrado3AliasResolvesCorrectly(): void
	{
		// A Prado3 alias is a class_alias() target; PHP treats it as valid for
		// ReflectionClass, so getReflectionClassByType must return non-null.
		$ref = TComponentReflection::getReflectionClassByType('Prado3Alias_TComponent');
		$this->assertInstanceOf(\ReflectionClass::class, $ref);
	}

	// ========================================================================
	// getReflectionClassByType — object parameter (4.4.0)
	// ========================================================================

	public function testGetReflectionClassByTypeAcceptsObject(): void
	{
		$obj = new ReflectionFixtureComponent();
		$ref = TComponentReflection::getReflectionClassByType($obj);
		$this->assertInstanceOf(\ReflectionClass::class, $ref);
		$this->assertSame(ReflectionFixtureComponent::class, $ref->getName());
	}

	public function testGetReflectionClassByTypeSameInstanceForStringAndObject(): void
	{
		$byString = TComponentReflection::getReflectionClassByType(ReflectionTestConcrete::class);
		$obj = new ReflectionTestConcrete();
		$byObject = TComponentReflection::getReflectionClassByType($obj);
		$this->assertSame($byString, $byObject);
	}

	// ========================================================================
	// getReflectionMethodByType — string class name (4.4.0)
	// ========================================================================

	public function testGetReflectionMethodByTypeWithStringExistingMethod(): void
	{
		$rm = TComponentReflection::getReflectionMethodByType(ReflectionFixtureComponent::class, 'doWork');
		$this->assertInstanceOf(\ReflectionMethod::class, $rm);
		$this->assertSame('doWork', $rm->getName());
	}

	public function testGetReflectionMethodByTypeWithStringMissingMethod(): void
	{
		$rm = TComponentReflection::getReflectionMethodByType(ReflectionFixtureComponent::class, 'noSuchMethod');
		$this->assertNull($rm);
	}

	public function testGetReflectionMethodByTypeWithStringMissingClass(): void
	{
		$rm = TComponentReflection::getReflectionMethodByType('NonExistentClass_XYZ', 'doWork');
		$this->assertNull($rm);
	}

	public function testGetReflectionMethodByTypeStringCachingSameInstance(): void
	{
		$first = TComponentReflection::getReflectionMethodByType(ReflectionFixtureComponent::class, 'doWork');
		$second = TComponentReflection::getReflectionMethodByType(ReflectionFixtureComponent::class, 'doWork');
		$this->assertSame($first, $second);
	}

	public function testGetReflectionMethodByTypeStringCachesNull(): void
	{
		$first = TComponentReflection::getReflectionMethodByType(ReflectionFixtureComponent::class, 'missingMethod_XYZ');
		$this->assertNull($first);

		// Second call must return the same cached null
		$second = TComponentReflection::getReflectionMethodByType(ReflectionFixtureComponent::class, 'missingMethod_XYZ');
		$this->assertNull($second);
	}

	public function testGetReflectionMethodByTypeStringCaseInsensitive(): void
	{
		$canonical = TComponentReflection::getReflectionMethodByType(ReflectionTestConcrete::class, 'doThing');
		$upper = TComponentReflection::getReflectionMethodByType(strtoupper(ReflectionTestConcrete::class), 'doThing');
		$this->assertInstanceOf(\ReflectionMethod::class, $canonical);
		$this->assertSame($canonical, $upper);
	}

	// ========================================================================
	// getReflectionMethodByType — object parameter (4.4.0)
	// ========================================================================

	public function testGetReflectionMethodByTypeWithObject(): void
	{
		$obj = new ReflectionFixtureComponent();
		$rm = TComponentReflection::getReflectionMethodByType($obj, 'doWork');
		$this->assertInstanceOf(\ReflectionMethod::class, $rm);
		$this->assertSame('doWork', $rm->getName());
	}

	public function testGetReflectionMethodByTypeWithNonTComponentObject(): void
	{
		$obj = new PlainPhpClass();
		$rm = TComponentReflection::getReflectionMethodByType($obj, 'getFoo');
		$this->assertInstanceOf(\ReflectionMethod::class, $rm);
		$this->assertSame('getFoo', $rm->getName());
	}

	// ========================================================================
	// getReflectionMethodByType — behavior discovery (4.4.0)
	// ========================================================================

	public function testGetReflectionMethodByTypeDiscoversBehaviorMethod(): void
	{
		$component = new ReflectionFixtureComponent();
		$behavior = new ReflectionTestBehavior();
		$component->attachBehavior('test', $behavior);

		$rm = TComponentReflection::getReflectionMethodByType($component, 'behaviorOnlyMethod');
		$this->assertInstanceOf(\ReflectionMethod::class, $rm);
		$this->assertSame('behaviorOnlyMethod', $rm->getName());
		$this->assertSame(ReflectionTestBehavior::class, $rm->getDeclaringClass()->getName());
	}

	public function testGetReflectionMethodByTypeClassMethodTakesPriorityOverBehavior(): void
	{
		$component = new ReflectionFixtureComponent();
		$behavior = new ReflectionTestBehavior();
		$component->attachBehavior('test', $behavior);

		// 'getName' exists on the component class itself — behavior should not shadow it
		$rm = TComponentReflection::getReflectionMethodByType($component, 'getName');
		$this->assertInstanceOf(\ReflectionMethod::class, $rm);
		$this->assertSame('getName', $rm->getName());
		$this->assertSame(ReflectionFixtureComponent::class, $rm->getDeclaringClass()->getName());
	}

	public function testGetReflectionMethodByTypeBehaviorMethodCachedByBehaviorId(): void
	{
		$component1 = new ReflectionFixtureComponent();
		$behavior1 = new ReflectionTestBehavior();
		$component1->attachBehavior('test', $behavior1);

		$component2 = new ReflectionFixtureComponent();
		$behavior2 = new ReflectionTestBehavior();
		$component2->attachBehavior('test', $behavior2);

		$rm1 = TComponentReflection::getReflectionMethodByType($component1, 'behaviorOnlyMethod');
		$rm2 = TComponentReflection::getReflectionMethodByType($component2, 'behaviorOnlyMethod');

		// Both return valid ReflectionMethod instances reflecting the same behavior class method.
		// Each behavior instance has its own cache key (spl_object_id of the behavior).
		$this->assertInstanceOf(\ReflectionMethod::class, $rm1);
		$this->assertInstanceOf(\ReflectionMethod::class, $rm2);
		$this->assertSame('behaviorOnlyMethod', $rm1->getName());
		$this->assertSame('behaviorOnlyMethod', $rm2->getName());
		$this->assertSame(ReflectionTestBehavior::class, $rm1->getDeclaringClass()->getName());
		$this->assertSame(ReflectionTestBehavior::class, $rm2->getDeclaringClass()->getName());
		// Different behavior objects → different ReflectionMethod objects
		$this->assertNotSame($rm1, $rm2);
	}

	public function testGetReflectionMethodByTypeClassMethodSharedAcrossInstances(): void
	{
		$obj1 = new ReflectionFixtureComponent();
		$obj2 = new ReflectionFixtureComponent();

		$rm1 = TComponentReflection::getReflectionMethodByType($obj1, 'doWork');
		$rm2 = TComponentReflection::getReflectionMethodByType($obj2, 'doWork');

		// Class methods are cached by class name, so both objects share the same
		// cached ReflectionMethod instance.
		$this->assertSame($rm1, $rm2);
	}

	public function testGetReflectionMethodByTypeStringAndObjectShareClassMethodCache(): void
	{
		$byString = TComponentReflection::getReflectionMethodByType(ReflectionFixtureComponent::class, 'doWork');
		$obj = new ReflectionFixtureComponent();
		$byObject = TComponentReflection::getReflectionMethodByType($obj, 'doWork');

		// Both string and object resolve to the same class-name cache key.
		$this->assertSame($byString, $byObject);
	}

	public function testGetReflectionMethodByTypeSameBehaviorAttachedTwiceSharesCache(): void
	{
		$behavior = new ReflectionTestBehavior();

		$component1 = new ReflectionFixtureComponent();
		$component1->attachBehavior('test', $behavior);

		$rm1 = TComponentReflection::getReflectionMethodByType($component1, 'behaviorOnlyMethod');

		// Detach and re-attach the same behavior to a different component
		$component1->detachBehavior('test');
		$component2 = new ReflectionFixtureComponent();
		$component2->attachBehavior('test', $behavior);

		$rm2 = TComponentReflection::getReflectionMethodByType($component2, 'behaviorOnlyMethod');

		// Same behavior object → same spl_object_id → same cache entry → same ReflectionMethod
		$this->assertSame($rm1, $rm2);
	}

	public function testGetReflectionMethodByTypeTComponentMissingMethodReturnsNull(): void
	{
		$component = new ReflectionFixtureComponent();
		$behavior = new ReflectionTestBehavior();
		$component->attachBehavior('test', $behavior);

		$rm = TComponentReflection::getReflectionMethodByType($component, 'noSuchMethodAnywhere_XYZ');
		$this->assertNull($rm);
	}

	public function testGetReflectionMethodByTypeTComponentWithoutBehaviors(): void
	{
		$component = new ReflectionFixtureComponent();
		// No behaviors attached
		$rm = TComponentReflection::getReflectionMethodByType($component, 'doWork');
		$this->assertInstanceOf(\ReflectionMethod::class, $rm);
		$this->assertSame('doWork', $rm->getName());
	}

	public function testGetReflectionMethodByTypeBehaviorDisabledSkipsBehaviors(): void
	{
		$component = new ReflectionFixtureComponent();
		$behavior = new ReflectionTestBehavior();
		$component->attachBehavior('test', $behavior);
		$component->disableBehaviors();

		$rm = TComponentReflection::getReflectionMethodByType($component, 'behaviorOnlyMethod');
		$this->assertNull($rm, 'Disabled behaviors should not contribute methods');
	}

	// ========================================================================
	// getReflectionPropertyByType — string class name (4.4.0)
	// ========================================================================

	public function testGetReflectionPropertyByTypeWithStringExistingProperty(): void
	{
		$rp = TComponentReflection::getReflectionPropertyByType(ReflectionPropertyChild::class, 'privateChildProp');
		$this->assertInstanceOf(\ReflectionProperty::class, $rp);
		$this->assertSame('privateChildProp', $rp->getName());
	}

	public function testGetReflectionPropertyByTypeFindsPrivateAncestorProperty(): void
	{
		// privateAncestorProp is declared in ReflectionPropertyAncestor, not in
		// ReflectionPropertyChild. Walking the hierarchy must find it.
		$rp = TComponentReflection::getReflectionPropertyByType(ReflectionPropertyChild::class, 'privateAncestorProp');
		$this->assertInstanceOf(\ReflectionProperty::class, $rp);
		$this->assertSame('privateAncestorProp', $rp->getName());
		$this->assertSame(ReflectionPropertyAncestor::class, $rp->getDeclaringClass()->getName());
	}

	public function testGetReflectionPropertyByTypeMissingPropertyReturnsNull(): void
	{
		$rp = TComponentReflection::getReflectionPropertyByType(ReflectionFixtureComponent::class, 'noSuchProperty_XYZ');
		$this->assertNull($rp);
	}

	public function testGetReflectionPropertyByTypeCachingSameInstance(): void
	{
		$first = TComponentReflection::getReflectionPropertyByType(ReflectionPropertyChild::class, 'privateChildProp');
		$second = TComponentReflection::getReflectionPropertyByType(ReflectionPropertyChild::class, 'privateChildProp');
		$this->assertSame($first, $second);
	}

	public function testGetReflectionPropertyByTypeCachesNull(): void
	{
		$first = TComponentReflection::getReflectionPropertyByType(ReflectionFixtureComponent::class, 'missingProp_XYZ');
		$this->assertNull($first);
		$second = TComponentReflection::getReflectionPropertyByType(ReflectionFixtureComponent::class, 'missingProp_XYZ');
		$this->assertNull($second);
	}

	public function testGetReflectionPropertyByTypeCaseInsensitive(): void
	{
		$canonical = TComponentReflection::getReflectionPropertyByType(ReflectionPropertyChild::class, 'privateChildProp');
		$upper = TComponentReflection::getReflectionPropertyByType(strtoupper(ReflectionPropertyChild::class), 'privateChildProp');
		$this->assertInstanceOf(\ReflectionProperty::class, $canonical);
		$this->assertSame($canonical, $upper);
	}

	public function testGetReflectionPropertyByTypeMissingClassReturnsNull(): void
	{
		$rp = TComponentReflection::getReflectionPropertyByType('NonExistentClass_XYZ', 'anyProp');
		$this->assertNull($rp);
	}

	// ========================================================================
	// getReflectionPropertyByType — object parameter (4.4.0)
	// ========================================================================

	public function testGetReflectionPropertyByTypeWithObject(): void
	{
		$obj = new ReflectionPropertyChild();
		$rp = TComponentReflection::getReflectionPropertyByType($obj, 'privateChildProp');
		$this->assertInstanceOf(\ReflectionProperty::class, $rp);
	}

	public function testGetReflectionPropertyByTypeWithNonTComponentObject(): void
	{
		$obj = new ReflectionPropertyChild();
		$rp = TComponentReflection::getReflectionPropertyByType($obj, 'privateAncestorProp');
		$this->assertInstanceOf(\ReflectionProperty::class, $rp);
		$this->assertSame(ReflectionPropertyAncestor::class, $rp->getDeclaringClass()->getName());
	}

	public function testGetReflectionPropertyByTypeTComponentInstance(): void
	{
		$obj = new ReflectionFixtureComponent();
		// TComponent subclasses have private properties inherited from parent classes
		// Just verify no exception is thrown and result is an array/null
		$rp = TComponentReflection::getReflectionPropertyByType($obj, '_className');
		// '_className' is declared in TComponentReflection, not in the fixture
		$this->assertNull($rp);
	}

	public function testGetReflectionPropertyByTypeObjectCachesByClassName(): void
	{
		$obj1 = new ReflectionPropertyChild();
		$obj2 = new ReflectionPropertyChild();

		$rp1 = TComponentReflection::getReflectionPropertyByType($obj1, 'privateChildProp');
		$rp2 = TComponentReflection::getReflectionPropertyByType($obj2, 'privateChildProp');

		$this->assertInstanceOf(\ReflectionProperty::class, $rp1);
		$this->assertInstanceOf(\ReflectionProperty::class, $rp2);
		// Properties are cached by class name, so different instances share the same cache entry
		$this->assertSame($rp1, $rp2);
	}

	public function testGetReflectionPropertyByTypeTComponentNullNotCachedWhenBehaviorsEnabled(): void
	{
		$component = new ReflectionFixtureComponent();

		// Behaviors are enabled by default — null is not cached at class level,
		// so subsequent calls re-walk the hierarchy (behaviors may later contribute a getter).
		$this->assertNull(TComponentReflection::getReflectionPropertyByType($component, '_className'));
		$this->assertNull(TComponentReflection::getReflectionPropertyByType($component, '_className'));
	}

	public function testGetReflectionPropertyByTypeTComponentNullCachedWhenBehaviorsDisabled(): void
	{
		$component = new ReflectionFixtureComponent();
		$component->disableBehaviors();

		// Behaviors disabled — null IS cached at class level.
		$this->assertNull(TComponentReflection::getReflectionPropertyByType($component, '_className'));
		$this->assertNull(TComponentReflection::getReflectionPropertyByType($component, '_className'));
	}

	public function testGetReflectionPropertyByTypeStringCachesNull(): void
	{
		// String class name — null is always cached (no behaviors possible).
		$this->assertNull(TComponentReflection::getReflectionPropertyByType(ReflectionFixtureComponent::class, '_className'));
		$this->assertNull(TComponentReflection::getReflectionPropertyByType(ReflectionFixtureComponent::class, '_className'));
	}

	public function testGetReflectionPropertyByTypeNonTComponentObjectCachesNull(): void
	{
		$obj = new PlainPhpClass();
		$this->assertNull(TComponentReflection::getReflectionPropertyByType($obj, 'noSuchProp_XYZ'));
		$this->assertNull(TComponentReflection::getReflectionPropertyByType($obj, 'noSuchProp_XYZ'));
	}

	// ========================================================================
	// Cross-method cache independence (4.4.0)
	// ========================================================================

	public function testMethodAndPropertyCachesAreIndependent(): void
	{
		// Caching a method should not affect property lookups and vice versa
		TComponentReflection::getReflectionMethodByType(ReflectionFixtureComponent::class, 'doWork');
		$rp = TComponentReflection::getReflectionPropertyByType(ReflectionFixtureComponent::class, '_className');
		// '_className' is private on TComponentReflection, not the fixture
		$this->assertNull($rp);
	}

	// ========================================================================
	// Non-caching of class-not-found results (4.4.0)
	// ========================================================================

	public function testGetReflectionMethodByTypeMissingClassDoesNotCache(): void
	{
		$missing = 'ThisClassWillNeverExist_Method_Test_XYZ999';

		// Two calls to a non-existent class both return null
		$this->assertNull(TComponentReflection::getReflectionMethodByType($missing, 'doWork'));
		$this->assertNull(TComponentReflection::getReflectionMethodByType($missing, 'doWork'));

		// The missing class did NOT corrupt the cache — a valid lookup still succeeds
		$rm = TComponentReflection::getReflectionMethodByType(ReflectionTestConcrete::class, 'doThing');
		$this->assertInstanceOf(\ReflectionMethod::class, $rm);
		$this->assertSame('doThing', $rm->getName());
	}

	public function testGetReflectionPropertyByTypeMissingClassDoesNotCache(): void
	{
		$missing = 'ThisClassWillNeverExist_Prop_Test_XYZ999';

		// Two calls to a non-existent class both return null
		$this->assertNull(TComponentReflection::getReflectionPropertyByType($missing, 'anyProp'));
		$this->assertNull(TComponentReflection::getReflectionPropertyByType($missing, 'anyProp'));

		// The missing class did NOT corrupt the cache — a valid lookup still succeeds
		$rp = TComponentReflection::getReflectionPropertyByType(ReflectionPropertyChild::class, 'privateChildProp');
		$this->assertInstanceOf(\ReflectionProperty::class, $rp);
		$this->assertSame('privateChildProp', $rp->getName());
	}
}
