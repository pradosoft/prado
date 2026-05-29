<?php

use Prado\TComponent;
use Prado\TModule;
use Prado\TService;
use Prado\TPropertyValue;
use Prado\Web\UI\TControl;

/**
 * String-backed enum for TComponent coercion integration tests.
 */
enum TCoercionTestColor: string
{
	case Red   = 'red';
	case Green = 'green';
	case Blue  = 'blue';
}

/**
 * Sub-component for nested dot-notation sub-property coercion tests.
 *
 * All setters carry declared types so that {@see TPropertyValue::applyProperty}
 * has a concrete type to coerce toward at each level of a dot-notation path.
 */
class TCoercionTestSubComponent extends TComponent
{
	private bool $_boolProp = false;
	private int $_intProp = 0;
	private float $_floatProp = 0.0;
	private string $_stringProp = '';
	private array $_arrayProp = [];

	public function getBoolProp(): bool
	{
		return $this->_boolProp;
	}

	public function setBoolProp(bool $v): void
	{
		$this->_boolProp = $v;
	}

	public function getIntProp(): int
	{
		return $this->_intProp;
	}

	public function setIntProp(int $v): void
	{
		$this->_intProp = $v;
	}

	public function getFloatProp(): float
	{
		return $this->_floatProp;
	}

	public function setFloatProp(float $v): void
	{
		$this->_floatProp = $v;
	}

	public function getStringProp(): string
	{
		return $this->_stringProp;
	}

	public function setStringProp(string $v): void
	{
		$this->_stringProp = $v;
	}

	public function getArrayProp(): array
	{
		return $this->_arrayProp;
	}

	public function setArrayProp(array $v): void
	{
		$this->_arrayProp = $v;
	}
}

/**
 * Deeper sub-component for three-level dot-notation path tests (Sub.Deep.IntProp).
 */
class TCoercionTestDeepComponent extends TComponent
{
	private int $_intProp = 0;
	private string $_stringProp = '';

	public function getIntProp(): int
	{
		return $this->_intProp;
	}

	public function setIntProp(int $v): void
	{
		$this->_intProp = $v;
	}

	public function getStringProp(): string
	{
		return $this->_stringProp;
	}

	public function setStringProp(string $v): void
	{
		$this->_stringProp = $v;
	}
}

/**
 * Sub-component extended with a nested deep component for three-level path tests.
 */
class TCoercionTestSubComponentWithDeep extends TCoercionTestSubComponent
{
	private TCoercionTestDeepComponent $_deep;

	public function __construct()
	{
		parent::__construct();
		$this->_deep = new TCoercionTestDeepComponent();
	}

	public function getDeep(): TCoercionTestDeepComponent
	{
		return $this->_deep;
	}

	public function setDeep(TCoercionTestDeepComponent $v): void
	{
		$this->_deep = $v;
	}
}

/**
 * Component fixture with typed setters covering every coercion branch.
 *
 * The `Sub` property exposes a {@see TCoercionTestSubComponentWithDeep} so that
 * dot-notation paths like `Sub.BoolProp` and `Sub.Deep.IntProp` exercise nested
 * and three-level sub-property coercion respectively.
 */
class TCoercionTestComponent extends TComponent
{
	private bool $_boolProp = false;
	private int $_intProp = 0;
	private float $_floatProp = 0.0;
	private string $_stringProp = '';
	private array $_arrayProp = [];
	private ?string $_nullableProp = 'default';
	private ?int $_nullableIntProp = null;
	private int|float $_unionProp = 0;
	private ?TCoercionTestColor $_colorProp = null;
	private TCoercionTestSubComponentWithDeep $_sub;

	public function __construct()
	{
		parent::__construct();
		$this->_sub = new TCoercionTestSubComponentWithDeep();
	}

	public function getBoolProp(): bool
	{
		return $this->_boolProp;
	}

	public function setBoolProp(bool $v): void
	{
		$this->_boolProp = $v;
	}

	public function getIntProp(): int
	{
		return $this->_intProp;
	}

	public function setIntProp(int $v): void
	{
		$this->_intProp = $v;
	}

	public function getFloatProp(): float
	{
		return $this->_floatProp;
	}

	public function setFloatProp(float $v): void
	{
		$this->_floatProp = $v;
	}

	public function getStringProp(): string
	{
		return $this->_stringProp;
	}

	public function setStringProp(string $v): void
	{
		$this->_stringProp = $v;
	}

	public function getArrayProp(): array
	{
		return $this->_arrayProp;
	}

	public function setArrayProp(array $v): void
	{
		$this->_arrayProp = $v;
	}

	public function getNullableProp(): ?string
	{
		return $this->_nullableProp;
	}

	public function setNullableProp(?string $v): void
	{
		$this->_nullableProp = $v;
	}

	public function getNullableIntProp(): ?int
	{
		return $this->_nullableIntProp;
	}

	public function setNullableIntProp(?int $v): void
	{
		$this->_nullableIntProp = $v;
	}

	public function getUnionProp(): int|float
	{
		return $this->_unionProp;
	}

	public function setUnionProp(int|float $v): void
	{
		$this->_unionProp = $v;
	}

	public function getColorProp(): ?TCoercionTestColor
	{
		return $this->_colorProp;
	}

	public function setColorProp(TCoercionTestColor $v): void
	{
		$this->_colorProp = $v;
	}

	public function getSub(): TCoercionTestSubComponentWithDeep
	{
		return $this->_sub;
	}

	public function setSub(TCoercionTestSubComponentWithDeep $v): void
	{
		$this->_sub = $v;
	}
}

/**
 * TControl subclass fixture for the TTemplate attribute-coercion path.
 *
 * TTemplate calls `configureProperty()` → `setSubProperty()` → `applyProperty()`
 * for each attribute on a component tag.  All source values arrive as raw strings
 * from the markup.  Typed setters here give the coercion pipeline a concrete
 * declared type to target.
 */
class TCoercionTestControl extends TControl
{
	private bool $_boolProp = false;
	private int $_intProp = 0;
	private float $_floatProp = 0.0;
	private string $_stringProp = '';
	private array $_arrayProp = [];
	private ?string $_nullableProp = 'default';
	private ?int $_nullableIntProp = null;
	private ?TCoercionTestColor $_colorProp = null;

	public function getBoolProp(): bool
	{
		return $this->_boolProp;
	}

	public function setBoolProp(bool $v): void
	{
		$this->_boolProp = $v;
	}

	public function getIntProp(): int
	{
		return $this->_intProp;
	}

	public function setIntProp(int $v): void
	{
		$this->_intProp = $v;
	}

	public function getFloatProp(): float
	{
		return $this->_floatProp;
	}

	public function setFloatProp(float $v): void
	{
		$this->_floatProp = $v;
	}

	public function getStringProp(): string
	{
		return $this->_stringProp;
	}

	public function setStringProp(string $v): void
	{
		$this->_stringProp = $v;
	}

	public function getArrayProp(): array
	{
		return $this->_arrayProp;
	}

	public function setArrayProp(array $v): void
	{
		$this->_arrayProp = $v;
	}

	public function getNullableProp(): ?string
	{
		return $this->_nullableProp;
	}

	public function setNullableProp(?string $v): void
	{
		$this->_nullableProp = $v;
	}

	public function getNullableIntProp(): ?int
	{
		return $this->_nullableIntProp;
	}

	public function setNullableIntProp(?int $v): void
	{
		$this->_nullableIntProp = $v;
	}

	public function getColorProp(): ?TCoercionTestColor
	{
		return $this->_colorProp;
	}

	public function setColorProp(TCoercionTestColor $v): void
	{
		$this->_colorProp = $v;
	}
}

/**
 * TModule subclass fixture for TApplication module-configuration coercion tests.
 *
 * `TApplication` initializes modules by calling `$module->setSubProperty($name, $value)`
 * for each property in the module's configuration section.  All values arrive
 * as strings when the configuration source is XML.
 */
class TCoercionTestModule extends TModule
{
	private bool $_boolProp = false;
	private int $_intProp = 0;
	private float $_floatProp = 0.0;
	private string $_stringProp = '';
	private array $_arrayProp = [];
	private ?TCoercionTestColor $_colorProp = null;

	public function init($config): void {}

	public function getBoolProp(): bool
	{
		return $this->_boolProp;
	}

	public function setBoolProp(bool $v): void
	{
		$this->_boolProp = $v;
	}

	public function getIntProp(): int
	{
		return $this->_intProp;
	}

	public function setIntProp(int $v): void
	{
		$this->_intProp = $v;
	}

	public function getFloatProp(): float
	{
		return $this->_floatProp;
	}

	public function setFloatProp(float $v): void
	{
		$this->_floatProp = $v;
	}

	public function getStringProp(): string
	{
		return $this->_stringProp;
	}

	public function setStringProp(string $v): void
	{
		$this->_stringProp = $v;
	}

	public function getArrayProp(): array
	{
		return $this->_arrayProp;
	}

	public function setArrayProp(array $v): void
	{
		$this->_arrayProp = $v;
	}

	public function getColorProp(): ?TCoercionTestColor
	{
		return $this->_colorProp;
	}

	public function setColorProp(TCoercionTestColor $v): void
	{
		$this->_colorProp = $v;
	}
}

/**
 * TService subclass fixture for TApplication service-configuration coercion tests.
 *
 * `TApplication` calls `TPropertyValue::applyProperty($service, $name, $value)`
 * in a loop over the service configuration section.  All values arrive
 * as strings when the configuration source is XML.
 */
class TCoercionTestService extends TService
{
	private bool $_boolProp = false;
	private int $_intProp = 0;
	private float $_floatProp = 0.0;
	private string $_stringProp = '';
	private array $_arrayProp = [];
	private ?TCoercionTestColor $_colorProp = null;

	public function run(): void {}

	public function getBoolProp(): bool
	{
		return $this->_boolProp;
	}

	public function setBoolProp(bool $v): void
	{
		$this->_boolProp = $v;
	}

	public function getIntProp(): int
	{
		return $this->_intProp;
	}

	public function setIntProp(int $v): void
	{
		$this->_intProp = $v;
	}

	public function getFloatProp(): float
	{
		return $this->_floatProp;
	}

	public function setFloatProp(float $v): void
	{
		$this->_floatProp = $v;
	}

	public function getStringProp(): string
	{
		return $this->_stringProp;
	}

	public function setStringProp(string $v): void
	{
		$this->_stringProp = $v;
	}

	public function getArrayProp(): array
	{
		return $this->_arrayProp;
	}

	public function setArrayProp(array $v): void
	{
		$this->_arrayProp = $v;
	}

	public function getColorProp(): ?TCoercionTestColor
	{
		return $this->_colorProp;
	}

	public function setColorProp(TCoercionTestColor $v): void
	{
		$this->_colorProp = $v;
	}
}

/**
 * TComponentCoercionIntegrationTest
 *
 * Tests the end-to-end coercion pipeline as seen from the major entry points in
 * the framework:
 *
 * - **{@see TPropertyValue::applyProperty()} direct** — every setter type,
 *   including all scalar edge cases (bool string variants, numeric-string int/float,
 *   int/float overflow boundary, hex/binary/octal integer literals, empty-string
 *   nullable, union resolution order).
 * - **{@see TComponent::setSubProperty()} flat** — single-segment paths exercising
 *   the same type matrix.
 * - **{@see TComponent::setSubProperty()} nested** — two-level (`Sub.Prop`) and
 *   three-level (`Sub.Deep.Prop`) dot-notation paths.
 * - **TControl / TTemplate attribute path** — string values from a template tag
 *   applied to a TControl subclass via `setSubProperty`, mirroring
 *   `TTemplate::configureProperty()`.
 * - **TModule configuration** — property loop matching `TApplication` module init,
 *   all values arriving as strings from XML configuration.
 * - **TService configuration** — property loop matching `TApplication` service init,
 *   all values arriving as strings from XML configuration.
 * - **Nested sub-property loop** — dot-notation paths iterated in a config loop,
 *   verifying that coercion at depth behaves identically to flat-path coercion.
 */
class TComponentCoercionIntegrationTest extends PHPUnit\Framework\TestCase
{
	// ════════════════════════════════════════════════════════════════════════
	// applyProperty — direct
	//
	// Tests every type with all edge cases in each setter type so a regression
	// in any single coercion step fails here before any higher-level test.
	// ════════════════════════════════════════════════════════════════════════

	// ── bool ─────────────────────────────────────────────────────────────

	public function testApplyProperty_bool_trueString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'BoolProp', 'true');
		self::assertSame(true, $c->getBoolProp());
	}

	public function testApplyProperty_bool_falseString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'BoolProp', 'false');
		self::assertSame(false, $c->getBoolProp());
	}

	public function testApplyProperty_bool_trueStringCaseInsensitive(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'BoolProp', 'TRUE');
		self::assertSame(true, $c->getBoolProp());

		TPropertyValue::applyProperty($c, 'BoolProp', 'True');
		self::assertSame(true, $c->getBoolProp());
	}

	public function testApplyProperty_bool_numericStringOne(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'BoolProp', '1');
		self::assertSame(true, $c->getBoolProp());
	}

	public function testApplyProperty_bool_numericStringNonZero(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'BoolProp', '42');
		self::assertSame(true, $c->getBoolProp());
	}

	public function testApplyProperty_bool_numericStringZero(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'BoolProp', '0');
		self::assertSame(false, $c->getBoolProp());
	}

	/**
	 * 'yes', 'no', 'on', 'off' are NOT in ensureBoolean's truthy set — only
	 * 'true' (case-insensitive) and non-zero numeric strings.  All four must
	 * resolve to false.
	 */
	public function testApplyProperty_bool_yesNoOnOff_allFalse(): void
	{
		$c = new TCoercionTestComponent();
		foreach (['yes', 'no', 'on', 'off', 'YES', 'ON'] as $s) {
			TPropertyValue::applyProperty($c, 'BoolProp', $s);
			self::assertSame(false, $c->getBoolProp(), "Expected false for '$s'");
		}
	}

	public function testApplyProperty_bool_fromInt(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'BoolProp', 1);
		self::assertSame(true, $c->getBoolProp());

		TPropertyValue::applyProperty($c, 'BoolProp', 0);
		self::assertSame(false, $c->getBoolProp());
	}

	// ── int ──────────────────────────────────────────────────────────────

	public function testApplyProperty_int_fromDecimalString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'IntProp', '42');
		self::assertSame(42, $c->getIntProp());
	}

	public function testApplyProperty_int_fromNegativeString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'IntProp', '-7');
		self::assertSame(-7, $c->getIntProp());
	}

	public function testApplyProperty_int_fromZeroString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'IntProp', '0');
		self::assertSame(0, $c->getIntProp());
	}

	public function testApplyProperty_int_fromHexString(): void
	{
		$c = new TCoercionTestComponent();
		// ensureInteger uses (int) cast — hex is not directly parsed by (int)
		TPropertyValue::applyProperty($c, 'IntProp', 0xFF);
		self::assertSame(255, $c->getIntProp());
	}

	public function testApplyProperty_int_fromFloat(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'IntProp', 3.9);
		self::assertSame(3, $c->getIntProp());
	}

	// ── float ────────────────────────────────────────────────────────────

	public function testApplyProperty_float_fromDecimalString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'FloatProp', '3.14');
		self::assertSame(3.14, $c->getFloatProp());
	}

	public function testApplyProperty_float_fromScientificNotation(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'FloatProp', '1.5e2');
		self::assertSame(150.0, $c->getFloatProp());
	}

	public function testApplyProperty_float_fromNegativeString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'FloatProp', '-0.001');
		self::assertSame(-0.001, $c->getFloatProp());
	}

	public function testApplyProperty_float_fromIntString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'FloatProp', '7');
		self::assertSame(7.0, $c->getFloatProp());
	}

	public function testApplyProperty_float_fromInt(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'FloatProp', 5);
		self::assertSame(5.0, $c->getFloatProp());
	}

	// ── string ───────────────────────────────────────────────────────────

	public function testApplyProperty_string_fromBoolTrue(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'StringProp', true);
		self::assertSame('true', $c->getStringProp());
	}

	public function testApplyProperty_string_fromBoolFalse(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'StringProp', false);
		self::assertSame('false', $c->getStringProp());
	}

	public function testApplyProperty_string_fromInt(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'StringProp', 42);
		self::assertSame('42', $c->getStringProp());
	}

	public function testApplyProperty_string_fromFloat(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'StringProp', 3.14);
		self::assertSame('3.14', $c->getStringProp());
	}

	public function testApplyProperty_string_identity(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'StringProp', 'hello world');
		self::assertSame('hello world', $c->getStringProp());
	}

	public function testApplyProperty_string_emptyString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setStringProp('original');
		TPropertyValue::applyProperty($c, 'StringProp', '');
		self::assertSame('', $c->getStringProp());
	}

	// ── array ─────────────────────────────────────────────────────────────

	public function testApplyProperty_array_fromBareWordList(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'ArrayProp', 'red, green, blue');
		self::assertSame(['red', 'green', 'blue'], $c->getArrayProp());
	}

	public function testApplyProperty_array_fromBracketSyntax(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'ArrayProp', '[1, 2, 3]');
		self::assertSame([1, 2, 3], $c->getArrayProp());
	}

	public function testApplyProperty_array_fromKeyedString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'ArrayProp', '("a" => 1, "b" => 2)');
		self::assertSame(['a' => 1, 'b' => 2], $c->getArrayProp());
	}

	public function testApplyProperty_array_fromEmptyString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'ArrayProp', '');
		self::assertSame([], $c->getArrayProp());
	}

	public function testApplyProperty_array_fromPhpArray(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'ArrayProp', ['a', 'b', 'c']);
		self::assertSame(['a', 'b', 'c'], $c->getArrayProp());
	}

	public function testApplyProperty_array_nestedFromString(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'ArrayProp', '[1, [2, 3], 4]');
		self::assertSame([1, [2, 3], 4], $c->getArrayProp());
	}

	// ── nullable ─────────────────────────────────────────────────────────

	public function testApplyProperty_nullable_emptyStringBecomesNull(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'NullableProp', '');
		self::assertNull($c->getNullableProp());
	}

	public function testApplyProperty_nullable_nullStaysNull(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'NullableProp', null);
		self::assertNull($c->getNullableProp());
	}

	public function testApplyProperty_nullable_nonEmptyStringPreserved(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'NullableProp', 'hello');
		self::assertSame('hello', $c->getNullableProp());
	}

	public function testApplyProperty_nullableInt_emptyStringBecomesNull(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'NullableIntProp', '');
		self::assertNull($c->getNullableIntProp());
	}

	public function testApplyProperty_nullableInt_numericStringBecomesInt(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'NullableIntProp', '99');
		self::assertSame(99, $c->getNullableIntProp());
	}

	public function testApplyProperty_nullableInt_nullStaysNull(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'NullableIntProp', null);
		self::assertNull($c->getNullableIntProp());
	}

	// ── union int|float ───────────────────────────────────────────────────

	public function testApplyProperty_union_intString_picksInt(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'UnionProp', '7');
		self::assertSame(7, $c->getUnionProp());
	}

	public function testApplyProperty_union_floatString_picksFloat(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'UnionProp', '7.5');
		self::assertSame(7.5, $c->getUnionProp());
	}

	public function testApplyProperty_union_phpIntMaxBoundary_staysInt(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'UnionProp', (string) PHP_INT_MAX);
		self::assertSame(PHP_INT_MAX, $c->getUnionProp());
	}

	public function testApplyProperty_union_phpIntMinBoundary_staysInt(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'UnionProp', (string) PHP_INT_MIN);
		self::assertSame(PHP_INT_MIN, $c->getUnionProp());
	}

	public function testApplyProperty_union_overflowInt_promotesToFloat(): void
	{
		$c = new TCoercionTestComponent();
		$overMax = bcadd((string) PHP_INT_MAX, '1');
		TPropertyValue::applyProperty($c, 'UnionProp', $overMax);
		self::assertIsFloat($c->getUnionProp());
		self::assertSame((float) $overMax, $c->getUnionProp());
	}

	public function testApplyProperty_union_underflowInt_promotesToFloat(): void
	{
		$c = new TCoercionTestComponent();
		$underMin = bcsub((string) PHP_INT_MIN, '1');
		TPropertyValue::applyProperty($c, 'UnionProp', $underMin);
		self::assertIsFloat($c->getUnionProp());
		self::assertSame((float) $underMin, $c->getUnionProp());
	}

	public function testApplyProperty_union_scientificNotation_picksFloat(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'UnionProp', '1.5e3');
		self::assertSame(1500.0, $c->getUnionProp());
	}

	// ── BackedEnum ────────────────────────────────────────────────────────

	public function testApplyProperty_backedEnum_fromBackingValue(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'ColorProp', 'red');
		self::assertSame(TCoercionTestColor::Red, $c->getColorProp());
	}

	public function testApplyProperty_backedEnum_fromCaseName(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'ColorProp', 'Green');
		self::assertSame(TCoercionTestColor::Green, $c->getColorProp());
	}

	public function testApplyProperty_backedEnum_instancePassthrough(): void
	{
		$c = new TCoercionTestComponent();
		TPropertyValue::applyProperty($c, 'ColorProp', TCoercionTestColor::Blue);
		self::assertSame(TCoercionTestColor::Blue, $c->getColorProp());
	}

	public function testApplyProperty_backedEnum_allCasesRoundtrip(): void
	{
		$c = new TCoercionTestComponent();
		foreach (TCoercionTestColor::cases() as $case) {
			// Backing value round-trip
			TPropertyValue::applyProperty($c, 'ColorProp', $case->value);
			self::assertSame($case, $c->getColorProp());

			// Case name round-trip
			TPropertyValue::applyProperty($c, 'ColorProp', $case->name);
			self::assertSame($case, $c->getColorProp());
		}
	}

	// ════════════════════════════════════════════════════════════════════════
	// setSubProperty — flat single-segment path
	// ════════════════════════════════════════════════════════════════════════

	public function testSetSubProperty_flat_bool_trueString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('BoolProp', 'true');
		self::assertSame(true, $c->getBoolProp());
	}

	public function testSetSubProperty_flat_bool_falseString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('BoolProp', 'false');
		self::assertSame(false, $c->getBoolProp());
	}

	public function testSetSubProperty_flat_bool_yesNoOnOff_allFalse(): void
	{
		$c = new TCoercionTestComponent();
		foreach (['yes', 'no', 'on', 'off'] as $s) {
			$c->setSubProperty('BoolProp', $s);
			self::assertSame(false, $c->getBoolProp(), "Expected false for '$s'");
		}
	}

	public function testSetSubProperty_flat_bool_numericStringZero(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('BoolProp', '0');
		self::assertSame(false, $c->getBoolProp());
	}

	public function testSetSubProperty_flat_int_decimalString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('IntProp', '99');
		self::assertSame(99, $c->getIntProp());
	}

	public function testSetSubProperty_flat_int_negativeString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('IntProp', '-5');
		self::assertSame(-5, $c->getIntProp());
	}

	public function testSetSubProperty_flat_float_decimalString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('FloatProp', '2.718');
		self::assertSame(2.718, $c->getFloatProp());
	}

	public function testSetSubProperty_flat_float_scientificString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('FloatProp', '1e3');
		self::assertSame(1000.0, $c->getFloatProp());
	}

	public function testSetSubProperty_flat_string_fromBool(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('StringProp', true);
		self::assertSame('true', $c->getStringProp());
	}

	public function testSetSubProperty_flat_string_fromInt(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('StringProp', 42);
		self::assertSame('42', $c->getStringProp());
	}

	public function testSetSubProperty_flat_array_bracketString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('ArrayProp', '[1, 2, 3]');
		self::assertSame([1, 2, 3], $c->getArrayProp());
	}

	public function testSetSubProperty_flat_array_keyedString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('ArrayProp', '("a" => 1, "b" => 2)');
		self::assertSame(['a' => 1, 'b' => 2], $c->getArrayProp());
	}

	public function testSetSubProperty_flat_array_emptyString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('ArrayProp', '');
		self::assertSame([], $c->getArrayProp());
	}

	public function testSetSubProperty_flat_nullable_emptyStringBecomesNull(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('NullableProp', '');
		self::assertNull($c->getNullableProp());
	}

	public function testSetSubProperty_flat_nullableInt_emptyStringBecomesNull(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('NullableIntProp', '');
		self::assertNull($c->getNullableIntProp());
	}

	public function testSetSubProperty_flat_nullableInt_numericString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('NullableIntProp', '88');
		self::assertSame(88, $c->getNullableIntProp());
	}

	public function testSetSubProperty_flat_union_floatString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('UnionProp', '1.5');
		self::assertSame(1.5, $c->getUnionProp());
	}

	public function testSetSubProperty_flat_union_intString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('UnionProp', '8');
		self::assertSame(8, $c->getUnionProp());
	}

	public function testSetSubProperty_flat_backedEnum_backingValue(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('ColorProp', 'blue');
		self::assertSame(TCoercionTestColor::Blue, $c->getColorProp());
	}

	public function testSetSubProperty_flat_backedEnum_caseName(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('ColorProp', 'Red');
		self::assertSame(TCoercionTestColor::Red, $c->getColorProp());
	}

	// ════════════════════════════════════════════════════════════════════════
	// setSubProperty — nested two-level dot-notation path (Sub.Prop)
	// ════════════════════════════════════════════════════════════════════════

	public function testSetSubProperty_nested2_bool_trueString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.BoolProp', 'true');
		self::assertSame(true, $c->getSub()->getBoolProp());
	}

	public function testSetSubProperty_nested2_bool_falseString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.BoolProp', 'false');
		self::assertSame(false, $c->getSub()->getBoolProp());
	}

	public function testSetSubProperty_nested2_bool_yesNoOnOff_allFalse(): void
	{
		$c = new TCoercionTestComponent();
		foreach (['yes', 'no', 'on', 'off'] as $s) {
			$c->setSubProperty('Sub.BoolProp', $s);
			self::assertSame(false, $c->getSub()->getBoolProp(), "Expected false for '$s'");
		}
	}

	public function testSetSubProperty_nested2_int_decimalString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.IntProp', '55');
		self::assertSame(55, $c->getSub()->getIntProp());
	}

	public function testSetSubProperty_nested2_int_negativeString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.IntProp', '-3');
		self::assertSame(-3, $c->getSub()->getIntProp());
	}

	public function testSetSubProperty_nested2_float_decimalString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.FloatProp', '9.9');
		self::assertSame(9.9, $c->getSub()->getFloatProp());
	}

	public function testSetSubProperty_nested2_string_fromInt(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.StringProp', 123);
		self::assertSame('123', $c->getSub()->getStringProp());
	}

	public function testSetSubProperty_nested2_string_fromBool(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.StringProp', false);
		self::assertSame('false', $c->getSub()->getStringProp());
	}

	public function testSetSubProperty_nested2_array_bracketString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.ArrayProp', '[10, 20, 30]');
		self::assertSame([10, 20, 30], $c->getSub()->getArrayProp());
	}

	public function testSetSubProperty_nested2_array_emptyString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.ArrayProp', '');
		self::assertSame([], $c->getSub()->getArrayProp());
	}

	// ════════════════════════════════════════════════════════════════════════
	// setSubProperty — nested three-level path (Sub.Deep.Prop)
	// ════════════════════════════════════════════════════════════════════════

	public function testSetSubProperty_nested3_int_decimalString(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.Deep.IntProp', '77');
		self::assertSame(77, $c->getSub()->getDeep()->getIntProp());
	}

	public function testSetSubProperty_nested3_string_fromBool(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.Deep.StringProp', true);
		self::assertSame('true', $c->getSub()->getDeep()->getStringProp());
	}

	// ════════════════════════════════════════════════════════════════════════
	// Nested sub-property isolation — two separate instances must not share state
	// ════════════════════════════════════════════════════════════════════════

	public function testSetSubProperty_nested_twoInstances_isolatedState(): void
	{
		$a = new TCoercionTestComponent();
		$b = new TCoercionTestComponent();

		$a->setSubProperty('Sub.IntProp', '100');
		$b->setSubProperty('Sub.IntProp', '200');

		self::assertSame(100, $a->getSub()->getIntProp());
		self::assertSame(200, $b->getSub()->getIntProp());
	}

	public function testSetSubProperty_nested_sequentialUpdates_lastWins(): void
	{
		$c = new TCoercionTestComponent();
		$c->setSubProperty('Sub.BoolProp', 'true');
		self::assertSame(true, $c->getSub()->getBoolProp());
		$c->setSubProperty('Sub.BoolProp', 'false');
		self::assertSame(false, $c->getSub()->getBoolProp());
	}

	// ════════════════════════════════════════════════════════════════════════
	// TControl — TTemplate attribute-coercion path
	//
	// TTemplate calls configureProperty() → setSubProperty() → applyProperty()
	// for each attribute on a component tag.  Source values are always raw
	// strings from template markup.  This group verifies that typed setters on
	// a TControl subclass coerce correctly through that path.
	// ════════════════════════════════════════════════════════════════════════

	public function testControl_boolProp_trueString(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('BoolProp', 'true');
		self::assertSame(true, $c->getBoolProp());
	}

	public function testControl_boolProp_falseString(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('BoolProp', 'false');
		self::assertSame(false, $c->getBoolProp());
	}

	public function testControl_boolProp_yesNoOnOff_allFalse(): void
	{
		$c = new TCoercionTestControl();
		foreach (['yes', 'no', 'on', 'off'] as $s) {
			$c->setSubProperty('BoolProp', $s);
			self::assertSame(false, $c->getBoolProp(), "Expected false for '$s'");
		}
	}

	public function testControl_intProp_decimalString(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('IntProp', '12');
		self::assertSame(12, $c->getIntProp());
	}

	public function testControl_intProp_negativeString(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('IntProp', '-100');
		self::assertSame(-100, $c->getIntProp());
	}

	public function testControl_floatProp_decimalString(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('FloatProp', '0.5');
		self::assertSame(0.5, $c->getFloatProp());
	}

	public function testControl_floatProp_scientificString(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('FloatProp', '2.5e1');
		self::assertSame(25.0, $c->getFloatProp());
	}

	public function testControl_stringProp_fromBoolSource(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('StringProp', false);
		self::assertSame('false', $c->getStringProp());
	}

	public function testControl_stringProp_fromIntSource(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('StringProp', 99);
		self::assertSame('99', $c->getStringProp());
	}

	public function testControl_arrayProp_bareWordList(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('ArrayProp', 'alpha, beta, gamma');
		self::assertSame(['alpha', 'beta', 'gamma'], $c->getArrayProp());
	}

	public function testControl_arrayProp_bracketString(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('ArrayProp', '[10, 20, 30]');
		self::assertSame([10, 20, 30], $c->getArrayProp());
	}

	public function testControl_arrayProp_emptyString(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('ArrayProp', '');
		self::assertSame([], $c->getArrayProp());
	}

	public function testControl_nullableProp_emptyStringBecomesNull(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('NullableProp', '');
		self::assertNull($c->getNullableProp());
	}

	public function testControl_nullableProp_nonEmptyStringPreserved(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('NullableProp', 'set');
		self::assertSame('set', $c->getNullableProp());
	}

	public function testControl_nullableIntProp_emptyStringBecomesNull(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('NullableIntProp', '');
		self::assertNull($c->getNullableIntProp());
	}

	public function testControl_nullableIntProp_numericStringBecomesInt(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('NullableIntProp', '42');
		self::assertSame(42, $c->getNullableIntProp());
	}

	public function testControl_colorProp_backingValue(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('ColorProp', 'green');
		self::assertSame(TCoercionTestColor::Green, $c->getColorProp());
	}

	public function testControl_colorProp_caseName(): void
	{
		$c = new TCoercionTestControl();
		$c->setSubProperty('ColorProp', 'Red');
		self::assertSame(TCoercionTestColor::Red, $c->getColorProp());
	}

	public function testControl_colorProp_allCasesRoundtrip(): void
	{
		$c = new TCoercionTestControl();
		foreach (TCoercionTestColor::cases() as $case) {
			$c->setSubProperty('ColorProp', $case->value);
			self::assertSame($case, $c->getColorProp());
		}
	}

	// ════════════════════════════════════════════════════════════════════════
	// TModule — application configuration simulation
	//
	// TApplication initializes modules by calling
	// $module->setSubProperty($name, $value) for each property in the
	// XML configuration section.  All values arrive as strings.
	// ════════════════════════════════════════════════════════════════════════

	public function testModuleConfig_allTypesFromXmlStringProperties(): void
	{
		$module = new TCoercionTestModule();

		$xmlProperties = [
			'BoolProp'   => 'true',
			'IntProp'    => '77',
			'FloatProp'  => '1.23',
			'StringProp' => 'hello',
			'ArrayProp'  => 'x, y, z',
			'ColorProp'  => 'blue',
		];
		foreach ($xmlProperties as $name => $value) {
			$module->setSubProperty($name, $value);
		}

		self::assertSame(true, $module->getBoolProp());
		self::assertSame(77, $module->getIntProp());
		self::assertSame(1.23, $module->getFloatProp());
		self::assertSame('hello', $module->getStringProp());
		self::assertSame(['x', 'y', 'z'], $module->getArrayProp());
		self::assertSame(TCoercionTestColor::Blue, $module->getColorProp());
	}

	public function testModuleConfig_boolFalseFromString(): void
	{
		$module = new TCoercionTestModule();
		$module->setSubProperty('BoolProp', 'false');
		self::assertSame(false, $module->getBoolProp());
	}

	public function testModuleConfig_boolYesNoOnOff_allFalse(): void
	{
		$module = new TCoercionTestModule();
		foreach (['yes', 'no', 'on', 'off'] as $s) {
			$module->setSubProperty('BoolProp', $s);
			self::assertSame(false, $module->getBoolProp(), "Expected false for '$s'");
		}
	}

	public function testModuleConfig_colorEnum_fromCaseName(): void
	{
		$module = new TCoercionTestModule();
		$module->setSubProperty('ColorProp', 'Green');
		self::assertSame(TCoercionTestColor::Green, $module->getColorProp());
	}

	public function testModuleConfig_arrayBracketSyntax(): void
	{
		$module = new TCoercionTestModule();
		$module->setSubProperty('ArrayProp', '[1, 2, 3]');
		self::assertSame([1, 2, 3], $module->getArrayProp());
	}

	public function testModuleConfig_floatScientificNotation(): void
	{
		$module = new TCoercionTestModule();
		$module->setSubProperty('FloatProp', '3e2');
		self::assertSame(300.0, $module->getFloatProp());
	}

	// ════════════════════════════════════════════════════════════════════════
	// TService — application configuration simulation
	//
	// TApplication calls TPropertyValue::applyProperty($service, $name, $value)
	// in a loop over the service configuration section.  All values are strings
	// from XML.
	// ════════════════════════════════════════════════════════════════════════

	public function testServiceConfig_allTypesFromApplyPropertyLoop(): void
	{
		$service = new TCoercionTestService();

		$xmlProperties = [
			'BoolProp'   => 'false',
			'IntProp'    => '3',
			'FloatProp'  => '2.5',
			'StringProp' => 'world',
			'ArrayProp'  => '[10, 20, 30]',
			'ColorProp'  => 'red',
		];
		foreach ($xmlProperties as $name => $value) {
			TPropertyValue::applyProperty($service, $name, $value);
		}

		self::assertSame(false, $service->getBoolProp());
		self::assertSame(3, $service->getIntProp());
		self::assertSame(2.5, $service->getFloatProp());
		self::assertSame('world', $service->getStringProp());
		self::assertSame([10, 20, 30], $service->getArrayProp());
		self::assertSame(TCoercionTestColor::Red, $service->getColorProp());
	}

	public function testServiceConfig_boolTrueFromString(): void
	{
		$service = new TCoercionTestService();
		TPropertyValue::applyProperty($service, 'BoolProp', 'true');
		self::assertSame(true, $service->getBoolProp());
	}

	public function testServiceConfig_colorEnum_fromCaseName(): void
	{
		$service = new TCoercionTestService();
		TPropertyValue::applyProperty($service, 'ColorProp', 'Blue');
		self::assertSame(TCoercionTestColor::Blue, $service->getColorProp());
	}

	public function testServiceConfig_floatNegativeString(): void
	{
		$service = new TCoercionTestService();
		TPropertyValue::applyProperty($service, 'FloatProp', '-0.5');
		self::assertSame(-0.5, $service->getFloatProp());
	}

	public function testServiceConfig_intNegativeString(): void
	{
		$service = new TCoercionTestService();
		TPropertyValue::applyProperty($service, 'IntProp', '-99');
		self::assertSame(-99, $service->getIntProp());
	}

	// ════════════════════════════════════════════════════════════════════════
	// Nested sub-property loop — full coverage at depth
	//
	// Verifies that iterated dot-notation assignments behave identically to
	// flat-path assignments for all types.
	// ════════════════════════════════════════════════════════════════════════

	public function testNestedSubPropertyLoop_allTypesFromStringValues(): void
	{
		$c = new TCoercionTestComponent();

		$paths = [
			'Sub.BoolProp'   => 'true',
			'Sub.IntProp'    => '10',
			'Sub.FloatProp'  => '3.14',
			'Sub.StringProp' => 'nested',
			'Sub.ArrayProp'  => 'p, q, r',
		];
		foreach ($paths as $path => $value) {
			$c->setSubProperty($path, $value);
		}

		self::assertSame(true,     $c->getSub()->getBoolProp());
		self::assertSame(10,       $c->getSub()->getIntProp());
		self::assertSame(3.14,     $c->getSub()->getFloatProp());
		self::assertSame('nested', $c->getSub()->getStringProp());
		self::assertSame(['p', 'q', 'r'], $c->getSub()->getArrayProp());
	}

	public function testNestedSubPropertyLoop_boolYesNoOnOff_allFalse(): void
	{
		$c = new TCoercionTestComponent();
		foreach (['yes', 'no', 'on', 'off'] as $s) {
			$c->setSubProperty('Sub.BoolProp', $s);
			self::assertSame(false, $c->getSub()->getBoolProp(), "Expected false for '$s'");
		}
	}

	public function testNestedSubPropertyLoop_threeLevel_allTypes(): void
	{
		$c = new TCoercionTestComponent();

		$paths = [
			'Sub.Deep.IntProp'    => '42',
			'Sub.Deep.StringProp' => 'deep',
		];
		foreach ($paths as $path => $value) {
			$c->setSubProperty($path, $value);
		}

		self::assertSame(42,     $c->getSub()->getDeep()->getIntProp());
		self::assertSame('deep', $c->getSub()->getDeep()->getStringProp());
	}
}
