<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\ICoercible;
use Prado\IEnumerable;
use Prado\TComponent;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * A custom IEnumerable implementation that does NOT extend TEnumerable.
 * Uses TConstantReflectionTrait only (no Iterator). Verifies that
 * _coerceToClass checks IEnumerable, not TEnumerable.
 */
class TPropertyValueTestDirection implements IEnumerable
{
	use \Prado\Util\Traits\TConstantReflectionTrait;

	const North = 'North';
	const South = 'South';
	const East  = 'East';
	const West  = 'West';
}

/**
 * A custom IEnumerable + Iterator implementation using TConstantReflectionTrait
 * and TArrayCopyIteratorTrait directly, without extending TEnumerable.
 * Verifies that TConstantReflectionTrait::getIteratorArrayCopy() satisfies the
 * abstract contract in TArrayCopyIteratorTrait and provides constants as the
 * iterator backing store.
 */
class TPropertyValueTestSeason implements IEnumerable, \Iterator
{
	use \Prado\Util\Traits\TConstantReflectionTrait,
		\Prado\Util\Traits\TArrayCopyIteratorTrait {
		getIteratorArrayDirect as public;
		setIteratorArrayDirect as public;
	}

	const Spring = 'Spring';
	const Summer = 'Summer';
	const Autumn = 'Autumn';
	const Winter = 'Winter';
}

/**
 * Fixture: backing array that contains a `false` value, used to verify that
 * TArrayIteratorTrait::valid() does not prematurely terminate iteration.
 */
class TPropertyValueTestFlags implements \Iterator
{
	use \Prado\Util\Traits\TArrayCopyIteratorTrait;

	protected function getIteratorArrayCopy(): array
	{
		return ['Enabled' => true, 'Disabled' => false, 'Unknown' => null];
	}
}

/**
 * Fixture: uses both TConstantReflectionTrait and TArrayCopyIteratorTrait but
 * overrides getIteratorArrayCopy() to return a custom array, verifying that the
 * class-level definition takes precedence over TConstantReflectionTrait's
 * constant-backed implementation.
 */
class TPropertyValueTestCustomIterator implements IEnumerable, \Iterator
{
	use \Prado\Util\Traits\TConstantReflectionTrait;
	use \Prado\Util\Traits\TArrayCopyIteratorTrait;

	const A = 'A';
	const B = 'B';

	protected function getIteratorArrayCopy(): array
	{
		return ['X' => 'x', 'Y' => 'y'];
	}
}

/** Backed enum used by coerceToType / applyProperty tests. */
enum TPropertyValueTestColor: string
{
	case Red   = 'red';
	case Green = 'green';
	case Blue  = 'blue';
}

/** Int-backed enum used to test F-10 (ensureEnum type guard) and the step-10 int path. */
enum TPropertyValueTestPriority: int
{
	case Low  = 1;
	case High = 2;
}

/**
 * Non-backed UnitEnum fixture, used to verify that the step-3 enum-validation
 * path handles pure UnitEnums (no `tryFrom`, name lookup only).
 */
enum TPropertyValueTestStatus
{
	case Active;
	case Pending;
	case Closed;
}

/**
 * IEnumerable fixture where constant NAME differs from constant VALUE.
 * Used to verify the validate-name-not-value semantic shared by both
 * {@see TPropertyValue::ensureEnum()} and the coercion path
 * ({@see TPropertyValue::coerceToType()} / `_coerceUnionType()`): an
 * any-casing input that matches a constant resolves to the canonical
 * NAME (`'Alpha'`), not the constant's value (`'a'`).  Name→value
 * translation lives inside the enum class via the trait's
 * `valueOfConstant()` helper, called separately by callers that need
 * the value.
 */
class TPropertyValueTestCodeEnum implements IEnumerable
{
	use \Prado\Util\Traits\TConstantReflectionTrait;

	const Alpha = 'a';
	const Beta  = 'b';
}

/**
 * Fixture: a plain ICoercible class.  Accepts:
 * - a "x,y" string (regex-parsed),
 * - an ['x' => …, 'y' => …] array.
 * Declines everything else by returning null.  Used as the canonical ICoercible
 * exercise for both the single-class path and the union path.  Note: identity
 * pass-through is handled by the call site, so coerceFromValue() never sees a
 * Point instance.
 */
class TPropertyValueTestPoint implements ICoercible
{
	public function __construct(public readonly int $x, public readonly int $y)
	{
	}

	public static function coerceFromValue(mixed $value): ?static
	{
		if (is_array($value) && isset($value['x'], $value['y'])) {
			return new static((int) $value['x'], (int) $value['y']);
		}
		if (is_string($value) && preg_match('/^(-?\d+)\s*,\s*(-?\d+)$/', $value, $m)) {
			return new static((int) $m[1], (int) $m[2]);
		}
		return null;
	}
}

/**
 * Fixture: a second ICoercible class used to verify union-member ordering.
 * Recognizes only "lo-hi" range strings; declines everything else.
 */
class TPropertyValueTestRange implements ICoercible
{
	public function __construct(public readonly int $lo, public readonly int $hi)
	{
	}

	public static function coerceFromValue(mixed $value): ?static
	{
		if (is_string($value) && preg_match('/^(-?\d+)-(-?\d+)$/', $value, $m)) {
			return new static((int) $m[1], (int) $m[2]);
		}
		return null;
	}
}

/**
 * Fixture: an ICoercible whose coerceFromValue() ALWAYS throws if invoked.
 * Used to prove that the call site's identity pass-through truly short-circuits
 * before the factory runs — an instance input must not reach the factory at all.
 */
class TPropertyValueTestNeverCalled implements ICoercible
{
	public static function coerceFromValue(mixed $value): ?static
	{
		throw new \LogicException('coerceFromValue() must not be invoked for an identity pass-through.');
	}
}

/**
 * Fixture: an ICoercible whose coerceFromValue() always returns null.  Used to
 * verify that the union chain falls through to subsequent steps when every
 * coercer declines.
 */
class TPropertyValueTestDecliner implements ICoercible
{
	public static function coerceFromValue(mixed $value): ?static
	{
		return null;
	}
}

/**
 * Fixture: a second always-declining coercer.  Paired with
 * {@see TPropertyValueTestDecliner} to assemble unions of two distinct
 * declining members (PHP forbids duplicate types in a union).
 */
class TPropertyValueTestDecliner2 implements ICoercible
{
	public static function coerceFromValue(mixed $value): ?static
	{
		return null;
	}
}

/**
 * Fixture: ICoercible that throws on a shape-recognized but semantically
 * invalid input.  Verifies the documented contract that an out-of-range value
 * should throw {@see TInvalidDataValueException} rather than return null.
 */
class TPropertyValueTestPickyCoercer implements ICoercible
{
	public function __construct(public readonly int $value)
	{
	}

	public static function coerceFromValue(mixed $value): ?static
	{
		if (is_int($value) || (is_string($value) && ctype_digit($value))) {
			$n = (int) $value;
			if ($n < 0 || $n > 100) {
				throw new TInvalidDataValueException('propertyvalue_value_invalid', $n);
			}
			return new static($n);
		}
		return null;
	}
}

/**
 * Fixture: BackedEnum that ALSO implements ICoercible.  Verifies that
 * ICoercible (step 2) wins for inputs it claims, while the enum-name
 * validation step (step 3) handles the rest when ICoercible declines.
 */
enum TPropertyValueTestCoercibleEnum: string implements ICoercible
{
	case Red = 'red';
	case Green = 'green';
	case Blue = 'blue';

	public static function coerceFromValue(mixed $value): ?static
	{
		// Accept hex codes; decline anything else (let name lookup handle it).
		return match ($value) {
			'#FF0000' => self::Red,
			'#00FF00' => self::Green,
			'#0000FF' => self::Blue,
			default => null,
		};
	}
}

/**
 * Fixture: IEnumerable that ALSO implements ICoercible.  Parallels the
 * BackedEnum case above — ICoercible runs first, name lookup picks up the rest.
 */
class TPropertyValueTestCoercibleDirection implements IEnumerable, ICoercible
{
	use \Prado\Util\Traits\TConstantReflectionTrait;

	const North = 'N';
	const South = 'S';
	const East  = 'E';
	const West  = 'W';

	public string $tag = '';

	public static function coerceFromValue(mixed $value): ?static
	{
		// Accept a "coerced:..." marker that the enum step would never match.
		if (is_string($value) && str_starts_with($value, 'coerced:')) {
			$i = new static();
			$i->tag = substr($value, 8);
			return $i;
		}
		return null;
	}
}

/**
 */
class TPropertyValueTest extends PHPUnit\Framework\TestCase
{
	// ════════════════════════════════════════════════════════════════════════
	// Constants
	// ════════════════════════════════════════════════════════════════════════

	/**
	 * TYPE_* string constants represent the canonical PHP type names used by
	 * coerceToType() and ensureEnum().
	 */
	public function testTypeConstants(): void
	{
		self::assertSame('bool',     TPropertyValue::TYPE_BOOL);
		self::assertSame('int',      TPropertyValue::TYPE_INT);
		self::assertSame('float',    TPropertyValue::TYPE_FLOAT);
		self::assertSame('string',   TPropertyValue::TYPE_STRING);
		self::assertSame('array',    TPropertyValue::TYPE_ARRAY);
		self::assertSame('iterable', TPropertyValue::TYPE_ITERABLE);
		self::assertSame('object',   TPropertyValue::TYPE_OBJECT);
		self::assertSame('mixed',    TPropertyValue::TYPE_MIXED);
		self::assertSame('null',     TPropertyValue::TYPE_NULL);
	}

	/**
	 * BOOL_TRUE and BOOL_FALSE must equal the canonical string representations
	 * that ensureString() produces for bool values.
	 */
	public function testBoolStringConstants(): void
	{
		self::assertSame('true',  TPropertyValue::BOOL_TRUE);
		self::assertSame('false', TPropertyValue::BOOL_FALSE);
	}

	/**
	 * COLOR_RED, COLOR_GREEN, and COLOR_BLUE must equal the string keys that
	 * ensureHexColor() expects in named-key color arrays.
	 */
	public function testColorConstants(): void
	{
		self::assertSame('red',   TPropertyValue::COLOR_RED);
		self::assertSame('green', TPropertyValue::COLOR_GREEN);
		self::assertSame('blue',  TPropertyValue::COLOR_BLUE);
	}


	public function testEnsureBoolean()
	{
		self::assertEquals(true, TPropertyValue::ensureBoolean(true));
		self::assertEquals(true, TPropertyValue::ensureBoolean('true'));
		self::assertEquals(true, TPropertyValue::ensureBoolean('TRue'));
		self::assertEquals(true, TPropertyValue::ensureBoolean(1));
		self::assertEquals(true, TPropertyValue::ensureBoolean(0.001));
		self::assertEquals(true, TPropertyValue::ensureBoolean(100));
		self::assertEquals(true, TPropertyValue::ensureBoolean('1'));
		self::assertEquals(true, TPropertyValue::ensureBoolean('0.001'));
		self::assertEquals(true, TPropertyValue::ensureBoolean('100'));
		self::assertEquals(true, TPropertyValue::ensureBoolean(['value']));
		self::assertEquals(true, TPropertyValue::ensureBoolean(new stdClass()));

		self::assertEquals(false, TPropertyValue::ensureBoolean(false));
		self::assertEquals(false, TPropertyValue::ensureBoolean('false'));
		self::assertEquals(false, TPropertyValue::ensureBoolean('FAlse'));
		self::assertEquals(false, TPropertyValue::ensureBoolean(0));
		self::assertEquals(false, TPropertyValue::ensureBoolean('0'));
		self::assertEquals(false, TPropertyValue::ensureBoolean('value'));
		self::assertEquals(false, TPropertyValue::ensureBoolean(null));
	}

	public function testEnsureBooleanEdgeCases()
	{
		// Empty string — not 'true', not numeric → false
		self::assertFalse(TPropertyValue::ensureBoolean(''));

		// Float 0.0 — non-string, (bool)0.0 = false
		self::assertFalse(TPropertyValue::ensureBoolean(0.0));

		// String '0.0' — is_numeric('0.0') true, but '0.0' == 0 (numeric compare) → false
		self::assertFalse(TPropertyValue::ensureBoolean('0.0'));

		// String '0.1' — is_numeric true, 0.1 != 0 → true
		self::assertTrue(TPropertyValue::ensureBoolean('0.1'));

		// Negative int — non-string, (bool)-1 = true
		self::assertTrue(TPropertyValue::ensureBoolean(-1));

		// Negative float — non-string, (bool)-0.5 = true
		self::assertTrue(TPropertyValue::ensureBoolean(-0.5));

		// Negative string — is_numeric '-1' true, -1 != 0 → true
		self::assertTrue(TPropertyValue::ensureBoolean('-1'));

		// Negative numeric string with decimal — is_numeric '-0.5' true, -0.5 != 0 → true
		self::assertTrue(TPropertyValue::ensureBoolean('-0.5'));

		// Empty array — non-string, (bool)[] = false
		self::assertFalse(TPropertyValue::ensureBoolean([]));

		// Non-empty array — non-string, (bool)[0] = true
		self::assertTrue(TPropertyValue::ensureBoolean([0]));

		// String 'TRUE' (all-caps) — strcasecmp → true
		self::assertTrue(TPropertyValue::ensureBoolean('TRUE'));

		// String '0' — is_numeric true, == 0 → false (already in base test, confirm)
		self::assertFalse(TPropertyValue::ensureBoolean('0'));

		// PHP's is_numeric() accepts surrounding whitespace, so '  1  ' is numeric
		// and != 0 → true
		self::assertTrue(TPropertyValue::ensureBoolean('  1  '));
	}

	/**
	 * BOOL_TRUE / BOOL_FALSE must round-trip through ensureBoolean().
	 * The upper-case variant of BOOL_TRUE must also be accepted.
	 */
	public function testBoolStringConstants_ensureBooleanRoundTrip(): void
	{
		self::assertTrue( TPropertyValue::ensureBoolean(TPropertyValue::BOOL_TRUE));
		self::assertFalse(TPropertyValue::ensureBoolean(TPropertyValue::BOOL_FALSE));
		self::assertTrue(TPropertyValue::ensureBoolean(strtoupper(TPropertyValue::BOOL_TRUE)));
	}


	public function testEnsureString()
	{
		$value = 'myLiteral';
		$literal = new TJavaScriptLiteral($value);

		self::assertEquals($value, TPropertyValue::ensureString($literal));
		self::assertEquals($value, TPropertyValue::ensureString($value));

		self::assertEquals('true', TPropertyValue::ensureString(true));
		self::assertEquals('false', TPropertyValue::ensureString(false));

		self::assertEquals('0', TPropertyValue::ensureString(0));
		self::assertEquals('', TPropertyValue::ensureString(null));
		self::assertEquals('4.8', TPropertyValue::ensureString(4.8));
	}

	public function testEnsureStringEdgeCases()
	{
		// PHP_INT_MAX and PHP_INT_MIN — large integers round-trip through string
		self::assertSame((string) PHP_INT_MAX, TPropertyValue::ensureString(PHP_INT_MAX));
		self::assertSame((string) PHP_INT_MIN, TPropertyValue::ensureString(PHP_INT_MIN));

		// Negative integer
		self::assertSame('-5', TPropertyValue::ensureString(-5));

		// Float zero — PHP casts 0.0 to '0'
		self::assertSame('0', TPropertyValue::ensureString(0.0));

		// Negative float
		self::assertSame('-1.5', TPropertyValue::ensureString(-1.5));

		// Special IEEE 754 floats
		self::assertSame('INF', TPropertyValue::ensureString(INF));
		self::assertSame('-INF', TPropertyValue::ensureString(-INF));
		self::assertSame('NAN', TPropertyValue::ensureString(NAN));

		// Empty string — passes through
		self::assertSame('', TPropertyValue::ensureString(''));

		// Integer 1 — (string)1 = '1', not the bool path
		self::assertSame('1', TPropertyValue::ensureString(1));

		// Object with __toString
		$obj = new class {
			public function __toString(): string
			{
				return 'stringified';
			}
		};
		self::assertSame('stringified', TPropertyValue::ensureString($obj));
	}

	/**
	 * ensureString(bool) must produce the canonical BOOL_TRUE / BOOL_FALSE constants,
	 * not PHP's raw (string) cast ('1' / '').
	 */
	public function testBoolStringConstants_ensureStringProducesConstants(): void
	{
		self::assertSame(TPropertyValue::BOOL_TRUE,  TPropertyValue::ensureString(true));
		self::assertSame(TPropertyValue::BOOL_FALSE, TPropertyValue::ensureString(false));
	}

	public function testEnsureString_jsLiteral_passedThroughUnchanged(): void
	{
		// F-01: ensureString must return a TJavaScriptLiteral as-is (identity, not string cast)
		// so that downstream JS renderers receive the opaque literal object rather than a
		// double-encoded string.  Before the fix the return type was declared `string` but
		// the object was returned anyway; the widened `string|\Stringable` return type now
		// accurately reflects this path.
		$lit = new \Prado\Web\Javascripts\TJavaScriptLiteral('alert(1)');
		self::assertSame($lit, TPropertyValue::ensureString($lit));
	}

	public function testEnsureInteger()
	{
		self::assertEquals(0, TPropertyValue::ensureInteger(null));
		self::assertEquals(0, TPropertyValue::ensureInteger(''));
		self::assertEquals(0, TPropertyValue::ensureInteger([]));
		self::assertEquals(1, TPropertyValue::ensureInteger(['value']));
		self::assertEquals(1, TPropertyValue::ensureInteger(['value', 'v2']));
		self::assertEquals(0, TPropertyValue::ensureInteger(0));
		self::assertEquals(0, TPropertyValue::ensureInteger(0.0001));
		self::assertEquals(1, TPropertyValue::ensureInteger(1.8));
		self::assertEquals(1, TPropertyValue::ensureInteger(1.0001));
		self::assertEquals(1, TPropertyValue::ensureInteger(1.5001));
		self::assertEquals(1, TPropertyValue::ensureInteger(1.99999));

		self::assertEquals(0, TPropertyValue::ensureInteger('0'));
		self::assertEquals(0, TPropertyValue::ensureInteger('0.0001'));
		self::assertEquals(1, TPropertyValue::ensureInteger('1.8'));
		self::assertEquals(1, TPropertyValue::ensureInteger('1.0001'));
		self::assertEquals(1, TPropertyValue::ensureInteger('1.5001'));
		self::assertEquals(1, TPropertyValue::ensureInteger('1.99999'));
	}

	public function testEnsureIntegerEdgeCases()
	{
		// PHP_INT_MAX and PHP_INT_MIN round-trip
		self::assertSame(PHP_INT_MAX, TPropertyValue::ensureInteger(PHP_INT_MAX));
		self::assertSame(PHP_INT_MIN, TPropertyValue::ensureInteger(PHP_INT_MIN));

		// Boolean coercion
		self::assertSame(1, TPropertyValue::ensureInteger(true));
		self::assertSame(0, TPropertyValue::ensureInteger(false));

		// Negative int
		self::assertSame(-5, TPropertyValue::ensureInteger(-5));

		// Negative float truncates toward zero
		self::assertSame(-3, TPropertyValue::ensureInteger(-3.7));
		self::assertSame(-1, TPropertyValue::ensureInteger(-1.9999));

		// Negative string
		self::assertSame(-7, TPropertyValue::ensureInteger('-7'));
		self::assertSame(-3, TPropertyValue::ensureInteger('-3.9'));

		// Plain numeric string
		self::assertSame(42, TPropertyValue::ensureInteger('42'));

		// Large int string
		self::assertSame(PHP_INT_MAX, TPropertyValue::ensureInteger((string) PHP_INT_MAX));
	}
	
	public function testEnsureFloat()
	{
		self::assertEquals(0.0, TPropertyValue::ensureFloat(null));
		self::assertEquals(0.0, TPropertyValue::ensureFloat(''));
		self::assertEquals(0.0, TPropertyValue::ensureFloat([]));
		self::assertEquals(1.0, TPropertyValue::ensureFloat(['value']));
		self::assertEquals(1.0, TPropertyValue::ensureFloat(['value', 'v2']));
		self::assertEquals(0.0, TPropertyValue::ensureFloat(0));
		self::assertEquals(0.0001, TPropertyValue::ensureFloat(0.0001));
		self::assertEquals(1.8, TPropertyValue::ensureFloat(1.8));
		self::assertEquals(1.99999, TPropertyValue::ensureFloat(1.99999));

		self::assertEquals(0, TPropertyValue::ensureFloat('0'));
		self::assertEquals(0.0001, TPropertyValue::ensureFloat('0.0001'));
		self::assertEquals(1.8, TPropertyValue::ensureFloat('1.8'));
		self::assertEquals(1.99999, TPropertyValue::ensureFloat('1.99999'));
	}

	public function testEnsureFloatEdgeCases()
	{
		// Boolean coercion
		self::assertSame(1.0, TPropertyValue::ensureFloat(true));
		self::assertSame(0.0, TPropertyValue::ensureFloat(false));

		// Negative values
		self::assertSame(-1.5, TPropertyValue::ensureFloat(-1.5));
		self::assertSame(-1.5, TPropertyValue::ensureFloat('-1.5'));

		// Scientific notation string
		self::assertSame(100.0, TPropertyValue::ensureFloat('1.0e2'));
		self::assertSame(0.001, TPropertyValue::ensureFloat('1e-3'));

		// PHP_FLOAT_MAX round-trip
		self::assertSame(PHP_FLOAT_MAX, TPropertyValue::ensureFloat(PHP_FLOAT_MAX));

		// PHP_FLOAT_MIN (smallest positive float)
		self::assertSame(PHP_FLOAT_MIN, TPropertyValue::ensureFloat(PHP_FLOAT_MIN));

		// INF and -INF
		self::assertTrue(is_infinite(TPropertyValue::ensureFloat(INF)));
		self::assertGreaterThan(0.0, TPropertyValue::ensureFloat(INF));
		self::assertTrue(is_infinite(TPropertyValue::ensureFloat(-INF)));
		self::assertLessThan(0.0, TPropertyValue::ensureFloat(-INF));

		// NAN
		self::assertTrue(is_nan(TPropertyValue::ensureFloat(NAN)));

		// Large integer preserves value as float
		self::assertSame((float) PHP_INT_MAX, TPropertyValue::ensureFloat(PHP_INT_MAX));
	}
	
	public function testEnsureArraySmoke(): void
	{
		// At-a-glance baseline for the most common shapes; the more specific
		// tests below exercise each branch in detail.
		self::assertSame([], TPropertyValue::ensureArray(null));
		self::assertSame([], TPropertyValue::ensureArray(''));
		self::assertSame([], TPropertyValue::ensureArray([]));
		self::assertSame([0 => 0], TPropertyValue::ensureArray(0));
		self::assertSame([0 => 1], TPropertyValue::ensureArray(1));
		self::assertSame(['value'], TPropertyValue::ensureArray('value'));
		self::assertSame(['value'], TPropertyValue::ensureArray(' value '));
		self::assertSame([], TPropertyValue::ensureArray('()'));
		self::assertSame(['my', 'prop'], TPropertyValue::ensureArray('("my", "prop")'));
	}

	// ── ensureArray: parsed-literal branch — string wrapped in `(...)`/`[...]` ──

	public function testEnsureArrayParseEmptyParens()
	{
		// '()' → empty array literal → []
		self::assertSame([], TPropertyValue::ensureArray('()'));
	}

	public function testEnsureArrayParseParensWithWhitespace()
	{
		// '( )' → empty array literal with internal whitespace → []
		self::assertSame([], TPropertyValue::ensureArray('( )'));

		// '(   )' → same
		self::assertSame([], TPropertyValue::ensureArray('(   )'));
	}

	public function testEnsureArrayParseOuterWhitespaceTrimmed()
	{
		// Leading/trailing whitespace on the whole string is trimmed before the
		// paren check, so '  ("a", "b")  ' enters the parsed-literal branch.
		self::assertSame(['a', 'b'], TPropertyValue::ensureArray('  ("a", "b")  '));
	}

	public function testEnsureArrayParseDoubleQuotedStrings()
	{
		// Double-quoted string elements
		self::assertSame(['my', 'prop'], TPropertyValue::ensureArray('("my", "prop")'));
		self::assertSame(['a', 'b', 'c'], TPropertyValue::ensureArray('("a", "b", "c")'));
	}

	public function testEnsureArrayParseSingleQuotedStrings()
	{
		// Single-quoted string elements
		self::assertSame(['my', 'prop'], TPropertyValue::ensureArray("('my', 'prop')"));
		self::assertSame(['hello', 'world'], TPropertyValue::ensureArray("('hello', 'world')"));
	}

	public function testEnsureArrayParseIntegerElements()
	{
		// Integer elements
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('(1, 2, 3)'));
		self::assertSame([0, 100, -5], TPropertyValue::ensureArray('(0, 100, -5)'));
	}

	public function testEnsureArrayParseFloatElements()
	{
		// Float elements
		self::assertSame([1.5, 2.5], TPropertyValue::ensureArray('(1.5, 2.5)'));
		self::assertSame([-0.5, 3.14], TPropertyValue::ensureArray('(-0.5, 3.14)'));
	}

	public function testEnsureArrayParseMixedTypes()
	{
		// Mixed PHP types in one expression
		self::assertSame([1, 'two', 3.0], TPropertyValue::ensureArray('(1, "two", 3.0)'));
	}

	public function testEnsureArrayParseBooleanAndNull()
	{
		// PHP keyword tokens true / false / null (case-insensitive)
		self::assertSame([true, false, null], TPropertyValue::ensureArray('(true, false, null)'));
	}

	public function testEnsureArrayParseAssociativeStringKeys()
	{
		// Associative array with string keys
		self::assertSame(['x' => 1, 'y' => 2], TPropertyValue::ensureArray('("x" => 1, "y" => 2)'));
	}

	public function testEnsureArrayParseAssociativeIntegerKeys()
	{
		// Explicit integer keys
		self::assertSame([5 => 'a', 10 => 'b'], TPropertyValue::ensureArray('(5 => "a", 10 => "b")'));
	}

	public function testEnsureArrayIntegerKey_formFeedAndVerticalTabBeforeArrow(): void
	{
		// F-05: _consumeKey's whitespace scan between an integer key literal and the `=>`
		// arrow previously only checked space/tab/\n/\r, omitting \f (form-feed) and
		// \v (vertical-tab).  The regex validator accepts them (PCRE \s matches all six ASCII
		// whitespace chars), so the validator/parser pair was inconsistent: the validator
		// accepted the input but the extractor failed to skip \f/\v and could not locate `=>`,
		// causing the key to be lost.
		self::assertSame([5 => 'a'], TPropertyValue::ensureArray("(5\f=> \"a\")"));
		self::assertSame([5 => 'a'], TPropertyValue::ensureArray("(5\v=> \"a\")"));
		self::assertSame([5 => 'a', 10 => 'b'], TPropertyValue::ensureArray("(5\f=> \"a\", 10\v=> \"b\")"));
	}

	public function testEnsureArrayParseSingleElement()
	{
		// Single element
		self::assertSame(['only'], TPropertyValue::ensureArray('("only")'));
		self::assertSame([42], TPropertyValue::ensureArray('(42)'));
	}

	public function testEnsureArrayParseNestedArrays()
	{
		// Nested arrays — use the PHP 8 short `[]` form at the inner level.
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('([1, 2], [3, 4])'));
		self::assertSame([['a', 'b'], ['c']], TPropertyValue::ensureArray('(["a", "b"], ["c"])'));
		// Parser also accepts `(...)` nested inside `(...)` and vice versa.
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('((1, 2), (3, 4))'));
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('[(1, 2), [3, 4]]'));
	}

	public function testEnsureArrayParseEmptyStringElement()
	{
		// A single empty string element
		self::assertSame([''], TPropertyValue::ensureArray('("")'));
	}

	public function testEnsureArrayParseComplexExpression()
	{
		// A realistic config-style expression with integers, strings, and nested array
		self::assertSame(
			[11, 'abc', 'xyz', [12, 'bcd', 'wxy', '']],
			TPropertyValue::ensureArray('(11, "abc", "xyz", [12, "bcd", "wxy", ""])')
		);
	}

	// ── ensureArray: bracket `[...]` syntax (PHP 8 short form) ───────────────

	public function testEnsureArrayBracketEmpty()
	{
		self::assertSame([], TPropertyValue::ensureArray('[]'));
		self::assertSame([], TPropertyValue::ensureArray('[ ]'));
		self::assertSame([], TPropertyValue::ensureArray('  [   ]  '));
	}

	public function testEnsureArrayBracketScalars()
	{
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('[1, 2, 3]'));
		self::assertSame(['a', 'b'], TPropertyValue::ensureArray('["a", "b"]'));
		self::assertSame([1.5, -0.5], TPropertyValue::ensureArray('[1.5, -0.5]'));
		self::assertSame([true, false, null], TPropertyValue::ensureArray('[true, false, null]'));
	}

	public function testEnsureArrayBracketAssociative()
	{
		self::assertSame(['x' => 1, 'y' => 2], TPropertyValue::ensureArray('["x" => 1, "y" => 2]'));
		self::assertSame([5 => 'a', 10 => 'b'], TPropertyValue::ensureArray('[5 => "a", 10 => "b"]'));
	}

	public function testEnsureArrayBracketNested()
	{
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('[[1, 2], [3, 4]]'));
		self::assertSame([[[1]]], TPropertyValue::ensureArray('[[[1]]]'));
	}

	public function testEnsureArrayTrailingComma()
	{
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('(1, 2, 3,)'));
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('[1, 2, 3,]'));
	}

	public function testEnsureArrayScientificFloat()
	{
		self::assertSame([1.0e5, 2.5e-3], TPropertyValue::ensureArray('[1e5, 2.5e-3]'));
	}

	public function testEnsureArrayCaseInsensitiveKeywords()
	{
		// PHP itself treats true/false/null as case-insensitive keywords.
		self::assertSame([true, false, null], TPropertyValue::ensureArray('[TRUE, False, NULL]'));
	}

	// ── ensureArray: malformed bracketed input falls back to single element ──

	public function testEnsureArrayMalformedFallsBackToSingleElement()
	{
		// Stray comma — neither PHP nor our parser accepts an empty element.
		self::assertSame(['(1, ,2)'], TPropertyValue::ensureArray('(1, ,2)'));
		// Mismatched delimiters (open `(`, close `]`) — first/last chars don't
		// pair, and the auto-wrap `((1, 2])` still fails to balance.
		self::assertSame(['(1, 2]'], TPropertyValue::ensureArray('(1, 2]'));
		// Note: `("abc)` and `(foo)` are no longer fallbacks under bare-word.
		// `(foo)` resolves to `['foo']` and `("abc)` to `['"abc']` (the
		// literal `"` has stayed as part of the bare-word string).
	}

	// ── ensureArray: extended numeric literal forms ──────────────────────────

	public function testEnsureArrayHexIntegers()
	{
		self::assertSame([255, 16, 0xFF], TPropertyValue::ensureArray('[0xFF, 0X10, 0xff]'));
		self::assertSame([-255, 0xCAFE], TPropertyValue::ensureArray('[-0xFF, 0xCAFE]'));
	}

	public function testEnsureArrayBinaryIntegers()
	{
		self::assertSame([5, 0b1010_0011], TPropertyValue::ensureArray('[0b101, 0B10100011]'));
	}

	public function testEnsureArrayOctalIntegers()
	{
		// Modern explicit-prefix form.
		self::assertSame([15, 15], TPropertyValue::ensureArray('[0o17, 0O17]'));
		// PHP 7's leading-zero octal form has been intentionally dropped
		// because the silent meaning-shift (`017` reading as 15, not 17)
		// has been a long-standing footgun — PHP 8.1 has introduced `0o`
		// specifically to disambiguate.  Leading-zero literals have been
		// read as decimal here.
		self::assertSame([17, 123], TPropertyValue::ensureArray('[017, 0123]'));
		// The auto-wrap path has also dropped legacy octal — `'017'` without
		// brackets goes through the same int regex once wrapped in `(...)`.
		self::assertSame([17],  TPropertyValue::ensureArray('017'));
		self::assertSame([123], TPropertyValue::ensureArray('0123'));
		self::assertSame([17, 123], TPropertyValue::ensureArray('017, 0123'));
	}

	public function testEnsureArrayUnderscoredIntegers()
	{
		self::assertSame([1000, 1_000_000], TPropertyValue::ensureArray('[1_000, 1_000_000]'));
		self::assertSame([0xFFFF, 0b1010_0011], TPropertyValue::ensureArray('[0xFF_FF, 0b1010_0011]'));
	}

	public function testEnsureArrayUnderscoredFloats()
	{
		self::assertSame([1000.5, 1.5e10], TPropertyValue::ensureArray('[1_000.5, 1.5e1_0]'));
		self::assertSame([1000.55], TPropertyValue::ensureArray('[1_000.5_5]'));
	}

	public function testEnsureArrayPrefixedAsKey()
	{
		// Prefixed integer literals are valid array keys.
		self::assertSame([255 => 'a', 5 => 'b'], TPropertyValue::ensureArray('[0xFF => "a", 0b101 => "b"]'));
	}

	public function testEnsureArrayMalformedUnderscoreBecomesBareWord()
	{
		// Underscores adjacent to a base prefix or another underscore are
		// rejected by PHP's numeric grammar — under bare-word, the malformed
		// token has resolved to a string element instead of falling back.
		self::assertSame(['0x_FF'], TPropertyValue::ensureArray('[0x_FF]'));
		self::assertSame(['1__0'], TPropertyValue::ensureArray('[1__0]'));
		self::assertSame(['1_'], TPropertyValue::ensureArray('[1_]'));
	}

	// ── ensureArray: auto-wrap of unbracketed element lists ──────────────────
	//
	// An unbracketed string is treated as if it were already wrapped in `(...)`
	// before parsing — the function's job is to put the value into an array,
	// the parser is just the processing pass after that wrapping.

	public function testEnsureArrayAutoWrapSingleScalar()
	{
		self::assertSame([1], TPropertyValue::ensureArray('1'));
		self::assertSame([1.5], TPropertyValue::ensureArray('1.5'));
		self::assertSame([true], TPropertyValue::ensureArray('true'));
		self::assertSame([false], TPropertyValue::ensureArray('false'));
		self::assertSame([null], TPropertyValue::ensureArray('null'));
		self::assertSame(['hello'], TPropertyValue::ensureArray('"hello"'));
		self::assertSame(['hello'], TPropertyValue::ensureArray("'hello'"));
	}

	public function testEnsureArrayAutoWrapBareList()
	{
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('1, 2, 3'));
		self::assertSame(['a', 'b'], TPropertyValue::ensureArray('"a", "b"'));
		self::assertSame([1, 'two', 3.0], TPropertyValue::ensureArray('1, "two", 3.0'));
		self::assertSame([true, false, null], TPropertyValue::ensureArray('true, false, null'));
	}

	public function testEnsureArrayAutoWrapAssociative()
	{
		self::assertSame(['x' => 1, 'y' => 2], TPropertyValue::ensureArray('"x" => 1, "y" => 2'));
		self::assertSame([5 => 'a', 10 => 'b'], TPropertyValue::ensureArray('5 => "a", 10 => "b"'));
	}

	public function testEnsureArrayAutoWrapExtendedNumerics()
	{
		self::assertSame([255, 5, 15], TPropertyValue::ensureArray('0xFF, 0b101, 0o17'));
		self::assertSame([1000, 0xFFFF], TPropertyValue::ensureArray('1_000, 0xFF_FF'));
	}

	public function testEnsureArrayAutoWrapWithNestedBrackets()
	{
		// Bare top-level list with bracketed elements (mix of (...) and [...])
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('[1, 2], [3, 4]'));
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('(1, 2), [3, 4]'));
	}

	public function testEnsureArrayMalformedStructureFallsBack()
	{
		// Structural problems — unbalanced brackets, stray inner parens —
		// fall back to a single-element wrapping of the original input.
		self::assertSame(['(unbalanced'], TPropertyValue::ensureArray('(unbalanced'));
		self::assertSame(['unbalanced)'], TPropertyValue::ensureArray('unbalanced)'));
		self::assertSame(['[1, 2'], TPropertyValue::ensureArray('[1, 2'));
		self::assertSame(['a(b)c'], TPropertyValue::ensureArray('a(b)c'));
	}

	// ── ensureArray: quoted strings preserve internal commas ─────────────────

	public function testEnsureArrayQuotedStringPreservesInternalCommas()
	{
		// Single-quoted string containing a comma — one element, comma kept.
		self::assertSame(['hello, world'], TPropertyValue::ensureArray("'hello, world'"));
		// Double-quoted, same behavior.
		self::assertSame(['hello, world'], TPropertyValue::ensureArray('"hello, world"'));
		// Two adjacent quoted strings, each holding a comma, separated by a
		// top-level comma — two elements, internal commas kept.
		self::assertSame(['a, b', 'c, d'], TPropertyValue::ensureArray("'a, b', 'c, d'"));
		// Quoted key + quoted value, both holding commas.
		self::assertSame(['x, y' => '1, 2'], TPropertyValue::ensureArray('"x, y" => "1, 2"'));
	}

	public function testEnsureArrayDoubleQuotedEscapeSequences()
	{
		// Double-quoted strings have decoded the PHP-equivalent escape set:
		// \n, \r, \t, \v, \f, \e, \0, \\, \", \$ — anything else has been
		// preserved verbatim (no exception, matching PHP literal behavior).
		self::assertSame(["a\nb"], TPropertyValue::ensureArray('["a\nb"]'));
		self::assertSame(["tab\there"], TPropertyValue::ensureArray('["tab\there"]'));
		// Less common control escapes — \r carriage return, \v vertical tab,
		// \f form feed, \e escape (ESC, \x1b).  All decode to the same byte
		// PHP's double-quoted string literal produces.
		self::assertSame(["cr\rlf"],     TPropertyValue::ensureArray('["cr\rlf"]'));
		self::assertSame(["v\vtab"],     TPropertyValue::ensureArray('["v\vtab"]'));
		self::assertSame(["f\ffeed"],    TPropertyValue::ensureArray('["f\ffeed"]'));
		self::assertSame(["esc\x1bend"], TPropertyValue::ensureArray('["esc\eend"]'));
		self::assertSame(["back\\slash"], TPropertyValue::ensureArray('["back\\\\slash"]'));
		self::assertSame(['quote"in'], TPropertyValue::ensureArray('["quote\"in"]'));
		self::assertSame(['$x'], TPropertyValue::ensureArray('["\$x"]'));
		self::assertSame(["null\0byte"], TPropertyValue::ensureArray('["null\0byte"]'));
		// Unknown escape preserved verbatim (backslash + char).
		self::assertSame(['\q'], TPropertyValue::ensureArray('["\q"]'));
	}

	public function testEnsureArraySingleQuotedEscapeSequences()
	{
		// Single-quoted strings have decoded only \\ and \' — every other
		// `\X` has been preserved as a literal backslash followed by the
		// next character, matching PHP `'...'` semantics.
		self::assertSame(["it's"], TPropertyValue::ensureArray("['it\\'s']"));
		self::assertSame(['path\\to'], TPropertyValue::ensureArray("['path\\\\to']"));
		// `\n` inside single quotes is two literal chars: backslash and n.
		self::assertSame(['a\\nb'], TPropertyValue::ensureArray("['a\\nb']"));
		// `\t` likewise stays as backslash-t.
		self::assertSame(['x\\ty'], TPropertyValue::ensureArray("['x\\ty']"));
	}

	public function testEnsureArrayEscapeSequencesInKeys()
	{
		// Quoted strings work as keys too — escape decoding applies there.
		self::assertSame(["a\nb" => 1], TPropertyValue::ensureArray('["a\nb" => 1]'));
		self::assertSame(['it\'s' => 'fine'], TPropertyValue::ensureArray("['it\\'s' => 'fine']"));
	}

	public function testEnsureArrayDeepNesting()
	{
		// 10 levels deep — guards against catastrophic PCRE backtracking
		// and against any per-call recursion overhead.
		self::assertSame([[[[[[[[[[1]]]]]]]]]], TPropertyValue::ensureArray('[[[[[[[[[[1]]]]]]]]]]'));
		// Mixed `(...)` / `[...]` / `array(...)` at each level.
		self::assertSame([[[[1]]]], TPropertyValue::ensureArray('[(array([1]))]'));
	}

	public function testEnsureArrayDeepNestingUnderStrictGrammar()
	{
		// Strict grammar has accepted the same deep nesting through `[...]`
		// and `array(...)` only (no bare `(...)`).
		$strict = TPropertyValue::ARRAY_STRICT_GRAMMAR;
		self::assertSame([[[[[[[[[[1]]]]]]]]]], TPropertyValue::ensureArray('[[[[[[[[[[1]]]]]]]]]]', $strict));
		self::assertSame([[[1]]], TPropertyValue::ensureArray('[array([1])]', $strict));
		// Strict rejects nested bare `(...)` and falls back to single-element.
		self::assertSame(['[(array([1]))]'], TPropertyValue::ensureArray('[(array([1]))]', $strict));
	}

	public function testEnsureArrayUnicodeAndMultibyte()
	{
		// Multi-byte content inside quoted strings has been preserved
		// byte-for-byte (the parser has been byte-oriented).
		self::assertSame(['héllo', '日本'], TPropertyValue::ensureArray('["héllo", "日本"]'));
		// Bare-word with multi-byte content.
		self::assertSame(['café', 'naïve'], TPropertyValue::ensureArray('café, naïve'));
	}

	public function testEnsureArrayLargeNumericLiterals()
	{
		// Numbers that overflow PHP's int range have been parsed via the
		// `(int)` cast, which saturates at PHP_INT_MAX/MIN on 64-bit.
		self::assertSame([PHP_INT_MAX], TPropertyValue::ensureArray('[' . PHP_INT_MAX . ']'));
		// Hex up to and beyond int width.
		self::assertSame([0x7FFFFFFFFFFFFFFF], TPropertyValue::ensureArray('[0x7FFFFFFFFFFFFFFF]'));
	}

	public function testEnsureArrayDeeplyNestedUnclosedFallsBack()
	{
		// Validator catches the unbalanced opener at every depth and falls
		// back to a single-element wrapping of the original input.
		self::assertSame(['[[[1'], TPropertyValue::ensureArray('[[[1'));
		self::assertSame(['((((1))'], TPropertyValue::ensureArray('((((1))'));
		self::assertSame(['[array(1)'], TPropertyValue::ensureArray('[array(1)'));
	}

	// ── ensureArray: bare-word (unquoted string) elements ────────────────────
	//
	// Intentional divergence from PHP literal syntax: any token that doesn't
	// parse as a quoted string, number, or reserved keyword has become a
	// bare-word string.  This has supported XML/CSS-style attribute values
	// such as `<com:TControl colors="red, green, blue"/>`.

	public function testEnsureArrayBareWordList()
	{
		self::assertSame(['red', 'green', 'blue'], TPropertyValue::ensureArray('red, green, blue'));
		self::assertSame(['a', 'b', 'c'], TPropertyValue::ensureArray('a, b, c'));
		// Same content with explicit brackets.
		self::assertSame(['red', 'green', 'blue'], TPropertyValue::ensureArray('[red, green, blue]'));
		// Hyphenated identifiers (common in CSS-style values).
		self::assertSame(['on-hover', 'on-click', 'on-focus'], TPropertyValue::ensureArray('on-hover, on-click, on-focus'));
	}

	public function testEnsureArrayBareWordPreservesInternalSpaces()
	{
		self::assertSame(['string a', 'string b', 'string c'], TPropertyValue::ensureArray('string a, string b, string c'));
		self::assertSame(['hello world'], TPropertyValue::ensureArray('hello world'));
		// Leading/trailing whitespace inside each element has been trimmed.
		self::assertSame(['red', 'green', 'blue'], TPropertyValue::ensureArray('  red  ,  green  ,  blue  '));
	}

	public function testEnsureArrayBareWordMixedWithQuoted()
	{
		// Quoted strings have taken priority over bare-word for the spans
		// they cover, and a final bare-word has been parsed normally.
		self::assertSame(['string a', 'string b', 'string c'], TPropertyValue::ensureArray('["string a", \'string b\', string c]'));
		self::assertSame(['a', 'b c', 'd'], TPropertyValue::ensureArray('"a", b c, \'d\''));
	}

	public function testEnsureArrayBareWordKeepsNumbersNumeric()
	{
		// Numbers have stayed numeric — only non-numeric tokens have become
		// bare-word strings.
		self::assertSame([1, 'two', 3.5, 'four'], TPropertyValue::ensureArray('1, two, 3.5, four'));
	}

	public function testEnsureArrayBareWordReservedKeywordPriority()
	{
		// `null` / `true` / `false` (case-insensitive) have outranked bare-
		// word so a comma-separated list of keywords has produced PHP scalars.
		self::assertSame([true, false, null, 'red'], TPropertyValue::ensureArray('true, false, null, red'));
		self::assertSame([true, false, null], TPropertyValue::ensureArray('TRUE, False, NULL'));
		// A full identifier that has merely *started* with a keyword has not
		// matched the keyword (word-boundary check) and so has remained bare.
		self::assertSame(['truely', 'nullable', 'falsely'], TPropertyValue::ensureArray('truely, nullable, falsely'));
	}

	public function testEnsureArrayBareWordMistypedNumberIsString()
	{
		// Mistyped numbers have intentionally fallen through to bare-word,
		// matching the user's intuition that anything that isn't strictly
		// numeric has been a string.
		self::assertSame(['1abc'], TPropertyValue::ensureArray('1abc'));
		self::assertSame(['1.5xyz'], TPropertyValue::ensureArray('1.5xyz'));
		// CSS-style "value with unit".
		self::assertSame(['width: 100auto'], TPropertyValue::ensureArray('width: 100auto'));
		// Hex-looking but invalid: Z isn't a hex digit.
		self::assertSame(['0xZZ'], TPropertyValue::ensureArray('0xZZ'));
	}

	public function testEnsureArrayBareWordAsKey()
	{
		self::assertSame(['foo' => 'bar'], TPropertyValue::ensureArray('foo => bar'));
		self::assertSame(['name' => 'Alice', 'role' => 'admin'], TPropertyValue::ensureArray('name => Alice, role => admin'));
		// Bare-word keys may carry internal spaces, same as bare-word values.
		self::assertSame(['user name' => 'Alice Smith'], TPropertyValue::ensureArray('user name => Alice Smith'));
	}

	public function testEnsureArrayBareWordFromUnterminatedQuote()
	{
		// An unterminated quote has no longer been a fallback — bare-word
		// has picked it up with the literal quote character kept in the
		// resulting string.
		self::assertSame(['"abc'], TPropertyValue::ensureArray('("abc)'));
		self::assertSame(["'abc"], TPropertyValue::ensureArray("('abc)"));
	}

	public function testEnsureArrayBareWordSingleElement()
	{
		// Previously a fallback; now resolves cleanly via bare-word.
		self::assertSame(['foo'], TPropertyValue::ensureArray('(foo)'));
		self::assertSame(['hello world'], TPropertyValue::ensureArray('(hello world)'));
	}

	// ── ensureArray: array(...) keyword form (PHP literal) ───────────────────

	public function testEnsureArrayAcceptsArrayKeyword()
	{
		// Loose grammar has accepted the PHP `array(...)` keyword form at any
		// depth and freely mixed with `(...)` / `[...]`.  Case-insensitive.
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('array(1, 2, 3)'));
		self::assertSame([1, 2], TPropertyValue::ensureArray('Array(1, 2)'));
		self::assertSame([1, 2], TPropertyValue::ensureArray('ARRAY(1, 2)'));
		self::assertSame([1, 2], TPropertyValue::ensureArray('array (1, 2)'));
		self::assertSame([[1], [2]], TPropertyValue::ensureArray('[array(1), [2]]'));
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('[(1, 2), array(3, 4)]'));
		// Surrounding whitespace at the top level exercises _skipArrayKeyword
		// when the parser entry sees `(?&ws)` before the `array` token.
		self::assertSame([1, 2], TPropertyValue::ensureArray('   array  (1, 2)   '));
		self::assertSame([1, 2], TPropertyValue::ensureArray("\tarray\t(1, 2)\t"));
		// Nested `array(array(...))` — recursive use of the keyword form.
		self::assertSame([[1, 2]], TPropertyValue::ensureArray('array(array(1, 2))'));
		// Three-deep mixed: array → [ → array → scalar.
		self::assertSame([[[1, 2]]], TPropertyValue::ensureArray('array([array(1, 2)])'));
	}

	// ── ensureArray: strict grammar ──────────────────────────────────────────
	//
	// ARRAY_STRICT_GRAMMAR has restricted the parser to the PHP-literal
	// grammar — `[...]` or `array(...)` only, no bare-word strings, no legacy
	// octal, no auto-wrap of unbracketed input.

	public function testEnsureArrayStrictGrammarAcceptsPhpLiterals()
	{
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('[1, 2, 3]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('array(1, 2, 3)', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		self::assertSame([1, 2], TPropertyValue::ensureArray('Array(1, 2)', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		self::assertSame([[1], [2]], TPropertyValue::ensureArray('[array(1), [2]]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		self::assertSame([1 => 'a', 2 => 'b'], TPropertyValue::ensureArray('[1 => "a", 2 => "b"]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		self::assertSame([255, 5, 15], TPropertyValue::ensureArray('[0xFF, 0b101, 0o17]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		// Trailing commas allowed (PHP literal supports them).
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('[1, 2, 3,]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
	}

	public function testEnsureArrayStrictGrammarRejectsBareParens()
	{
		// `(...)` alone is not a PHP array literal — strict has fallen back.
		self::assertSame(['(1, 2, 3)'], TPropertyValue::ensureArray('(1, 2, 3)', TPropertyValue::ARRAY_STRICT_GRAMMAR));
	}

	// ── ensureArray: ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN ───────────────────
	//
	// The ALLOW_BARE_PAREN flag (combined with ARRAY_STRICT_GRAMMAR) has
	// extended the strict grammar to also accept `(...)` as a valid array
	// delimiter at every depth, while still prohibiting bare-word strings,
	// legacy octal, and auto-wrap of unbracketed input.

	public function testEnsureArrayAllowBareParenAcceptsParenForm(): void
	{
		$flags = TPropertyValue::ARRAY_STRICT_GRAMMAR | TPropertyValue::ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN;
		// Bare-paren `(...)` form that strict-only mode would reject.
		self::assertSame([1, 2, 3],    TPropertyValue::ensureArray('(1, 2, 3)', $flags));
		self::assertSame(['a', 'b'],   TPropertyValue::ensureArray('("a", "b")', $flags));
		self::assertSame([],           TPropertyValue::ensureArray('()', $flags));
		// Trailing comma.
		self::assertSame([1, 2],       TPropertyValue::ensureArray('(1, 2,)', $flags));
		// `[...]` and `array(...)` still work.
		self::assertSame([1, 2, 3],    TPropertyValue::ensureArray('[1, 2, 3]', $flags));
		self::assertSame([1, 2],       TPropertyValue::ensureArray('array(1, 2)', $flags));
	}

	public function testEnsureArrayAllowBareParenFreeMixing(): void
	{
		$flags = TPropertyValue::ARRAY_STRICT_GRAMMAR | TPropertyValue::ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN;
		// `(...)`, `[...]`, and `array(...)` freely mixed at every depth.
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('([1, 2], [3, 4])', $flags));
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('[(1, 2), array(3, 4)]', $flags));
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('((1, 2), (3, 4))', $flags));
	}

	public function testEnsureArrayAllowBareParenStillRejectsBareWords(): void
	{
		$flags = TPropertyValue::ARRAY_STRICT_GRAMMAR | TPropertyValue::ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN;
		// Bare-word strings are still rejected — the scalar subset remains strict.
		self::assertSame(['(red, green)'], TPropertyValue::ensureArray('(red, green)', $flags));
		self::assertSame(['[foo, bar]'],   TPropertyValue::ensureArray('[foo, bar]', $flags));
	}

	public function testEnsureArrayAllowBareParenStillRejectsLegacyOctal(): void
	{
		$flags = TPropertyValue::ARRAY_STRICT_GRAMMAR | TPropertyValue::ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN;
		// Legacy octal (leading zero) is still rejected under strict scalar rules.
		self::assertSame(['(017)'], TPropertyValue::ensureArray('(017)', $flags));
		self::assertSame(['[017]'], TPropertyValue::ensureArray('[017]', $flags));
		// Modern octal still accepted.
		self::assertSame([15], TPropertyValue::ensureArray('(0o17)', $flags));
	}

	public function testEnsureArrayAllowBareParenStillRejectsAutoWrap(): void
	{
		$flags = TPropertyValue::ARRAY_STRICT_GRAMMAR | TPropertyValue::ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN;
		// Unbracketed bare element lists are NOT auto-wrapped — same as plain STRICT.
		self::assertSame(['1, 2, 3'],  TPropertyValue::ensureArray('1, 2, 3', $flags));
		self::assertSame(['"a", "b"'], TPropertyValue::ensureArray('"a", "b"', $flags));
	}

	public function testEnsureArrayAllowBareParenWithoutStrictHasNoEffect(): void
	{
		// ALLOW_BARE_PAREN has no effect when ARRAY_STRICT_GRAMMAR is absent —
		// the loose grammar already accepts `(...)`.
		$flags = TPropertyValue::ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN;
		self::assertSame([1, 2, 3],  TPropertyValue::ensureArray('(1, 2, 3)', $flags));
		self::assertSame(['a', 'b'], TPropertyValue::ensureArray('("a", "b")', $flags));
		// Auto-wrap still fires in loose mode.
		self::assertSame([1, 2, 3],  TPropertyValue::ensureArray('1, 2, 3', $flags));
	}

	public function testEnsureArrayAllowBareParenWithStrictErrors(): void
	{
		// When combined with ARRAY_STRICT_ERRORS, a rejected input still throws.
		$flags = TPropertyValue::ARRAY_STRICT_GRAMMAR
			| TPropertyValue::ARRAY_STRICT_GRAMMAR_ALLOW_BARE_PAREN
			| TPropertyValue::ARRAY_STRICT_ERRORS;
		// Valid forms — no throw.
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('(1, 2, 3)', $flags));
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('[1, 2, 3]', $flags));
		// Bare word in paren — rejected (no bare words in strict scalar mode).
		try {
			TPropertyValue::ensureArray('(red, green)', $flags);
			self::fail('Expected TInvalidDataValueException for bare word under strict+paren+errors');
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testEnsureArrayStrictGrammarRejectsBareWord()
	{
		// Bare-word strings have been silently fallen back to single element.
		self::assertSame(['[red, green]'], TPropertyValue::ensureArray('[red, green]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		self::assertSame(['red'], TPropertyValue::ensureArray('red', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		self::assertSame(['hello world'], TPropertyValue::ensureArray('hello world', TPropertyValue::ARRAY_STRICT_GRAMMAR));
	}

	public function testEnsureArrayStrictGrammarRejectsAutoWrap()
	{
		// Bare element lists without brackets have not been auto-wrapped.
		self::assertSame(['1, 2, 3'], TPropertyValue::ensureArray('1, 2, 3', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		self::assertSame(['"a", "b"'], TPropertyValue::ensureArray('"a", "b"', TPropertyValue::ARRAY_STRICT_GRAMMAR));
	}

	public function testEnsureArrayStrictGrammarRejectsLegacyOctal()
	{
		// PHP's leading-zero octal form (`017` = 15) applied only to source-code integer
		// literals, never to string coercion.  PHP 8.1 deprecated it in source and
		// introduced `0o17` for disambiguation.  The strict parser rejects the leading-zero
		// form as ambiguous legacy syntax regardless of PHP version.
		self::assertSame(['[017]'], TPropertyValue::ensureArray('[017]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		self::assertSame(['[0123]'], TPropertyValue::ensureArray('[0123]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		// Decimal with leading zero — also rejected.
		self::assertSame(['[019]'], TPropertyValue::ensureArray('[019]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		// Plain zero still works.
		self::assertSame([0], TPropertyValue::ensureArray('[0]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
		// Modern octal still works.
		self::assertSame([15], TPropertyValue::ensureArray('[0o17]', TPropertyValue::ARRAY_STRICT_GRAMMAR));
	}

	// ── ensureArray: strict errors ───────────────────────────────────────────
	//
	// ARRAY_STRICT_ERRORS has converted the silent fallback into a thrown
	// TInvalidDataValueException.

	public function testEnsureArrayStrictErrorsLooseGrammarStillParsesBareWord()
	{
		// Without strict grammar, bare-word still parses — no throw.
		self::assertSame(['red', 'green'], TPropertyValue::ensureArray('red, green', TPropertyValue::ARRAY_STRICT_ERRORS));
		self::assertSame(['hello world'], TPropertyValue::ensureArray('hello world', TPropertyValue::ARRAY_STRICT_ERRORS));
	}

	public function testEnsureArrayStrictErrorsThrowsOnStructuralFailure()
	{
		try {
			TPropertyValue::ensureArray('(1, ,2)', TPropertyValue::ARRAY_STRICT_ERRORS);
			self::fail('Expected TInvalidDataValueException for stray comma');
		} catch (TInvalidDataValueException $e) {
			self::assertInstanceOf(TInvalidDataValueException::class, $e);
		}
		try {
			TPropertyValue::ensureArray('(unbalanced', TPropertyValue::ARRAY_STRICT_ERRORS);
			self::fail('Expected TInvalidDataValueException for unbalanced bracket');
		} catch (TInvalidDataValueException $e) {
			self::assertInstanceOf(TInvalidDataValueException::class, $e);
		}
		// Auto-wrap can't save an unbalanced opener inside a `[...]` input —
		// `'[1, 2'` wrapped becomes `'([1, 2)'`, still unbalanced, still
		// rejected by both grammars.  ARRAY_STRICT_ERRORS converts the
		// silent fallback into a thrown exception.
		try {
			TPropertyValue::ensureArray('[1, 2', TPropertyValue::ARRAY_STRICT_ERRORS);
			self::fail('Expected TInvalidDataValueException for unparseable bracketed input under loose grammar');
		} catch (TInvalidDataValueException $e) {
			self::assertInstanceOf(TInvalidDataValueException::class, $e);
		}
	}

	public function testEnsureArrayStrictBothThrowsOnNonPhpLiteral()
	{
		$strict = TPropertyValue::ARRAY_STRICT_GRAMMAR | TPropertyValue::ARRAY_STRICT_ERRORS;
		// Valid PHP literals still parse.
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('[1, 2, 3]', $strict));
		self::assertSame([1, 2], TPropertyValue::ensureArray('array(1, 2)', $strict));
		// Anything PHP itself would reject throws.
		foreach (['[red]', '(1, 2)', '1, 2', '[017]'] as $bad) {
			try {
				TPropertyValue::ensureArray($bad, $strict);
				self::fail("Expected throw for input: $bad");
			} catch (TInvalidDataValueException $e) {
			}
		}
	}

	// ── ensureArray: fallback / passthrough branch — plain strings ──────────

	public function testEnsureArrayStringNotParens()
	{
		// A plain string with no surrounding parens is wrapped in an array
		self::assertSame(['value'], TPropertyValue::ensureArray('value'));
		self::assertSame(['hello world'], TPropertyValue::ensureArray('hello world'));
	}

	public function testEnsureArrayStringWithLeadingParenOnly()
	{
		// Starts with '(' but does NOT end with ')' → not a balanced literal;
		// auto-wrap can't save it either, so falls back to single-element string.
		self::assertSame(['(partial'], TPropertyValue::ensureArray('(partial'));
	}

	public function testEnsureArrayStringWithTrailingParenOnly()
	{
		// Ends with ')' but does NOT start with '(' → plain string
		self::assertSame(['partial)'], TPropertyValue::ensureArray('partial)'));
	}

	public function testEnsureArrayStringParenInsideMiddle()
	{
		// Parens are not at the outer boundary → plain string
		self::assertSame(['a(b)c'], TPropertyValue::ensureArray('a(b)c'));
	}

	public function testEnsureArraySingleCharParens()
	{
		// Single-char strings: '(' or ')' aren't a balanced literal and the
		// wrap-then-parse retry also fails — single-element fallback wins.
		self::assertSame(['('], TPropertyValue::ensureArray('('));
		self::assertSame([')'], TPropertyValue::ensureArray(')'));
	}

	public function testEnsureArrayStringTrimmedBeforeParenCheck()
	{
		// After trim, 'value' = 'value' → plain string
		self::assertSame(['value'], TPropertyValue::ensureArray(' value '));

		// After trim, '' = '' → empty array
		self::assertSame([], TPropertyValue::ensureArray('   '));
	}

	// ── ensureArray: non-string branch — (array) cast ─────────────────────────

	public function testEnsureArrayNonStringNull()
	{
		// (array) null → []
		self::assertSame([], TPropertyValue::ensureArray(null));
	}

	public function testEnsureArrayNonStringBooleans()
	{
		// (array) true → [true]
		self::assertSame([true], TPropertyValue::ensureArray(true));
		// (array) false → [false]
		self::assertSame([false], TPropertyValue::ensureArray(false));
	}

	public function testEnsureArrayNonStringIntegers()
	{
		self::assertSame([0], TPropertyValue::ensureArray(0));
		self::assertSame([1], TPropertyValue::ensureArray(1));
		self::assertSame([-5], TPropertyValue::ensureArray(-5));
	}

	public function testEnsureArrayNonStringFloat()
	{
		self::assertSame([1.5], TPropertyValue::ensureArray(1.5));
		self::assertSame([0.0], TPropertyValue::ensureArray(0.0));
	}

	public function testEnsureArrayNonStringArrayPassThrough()
	{
		// (array) array → same array
		self::assertSame([], TPropertyValue::ensureArray([]));
		self::assertSame(['a', 'b'], TPropertyValue::ensureArray(['a', 'b']));
		self::assertSame(['key' => 'val'], TPropertyValue::ensureArray(['key' => 'val']));
	}

	public function testEnsureArrayNonStringObject()
	{
		// (array) stdClass → public properties become associative keys
		$obj = new stdClass();
		$obj->foo = 'bar';
		$obj->num = 42;
		self::assertSame(['foo' => 'bar', 'num' => 42], TPropertyValue::ensureArray($obj));
	}

	public function testEnsureArrayNonStringObjectEmpty()
	{
		// (array) empty stdClass → []
		self::assertSame([], TPropertyValue::ensureArray(new stdClass()));
	}
	
	public function testEnsureObject()
	{
		self::assertEquals(new stdClass(), TPropertyValue::ensureObject(null));
		$obj = new stdClass();
		$obj->scalar = '';
		self::assertEquals($obj, TPropertyValue::ensureObject(''));
		self::assertEquals(new stdClass(), TPropertyValue::ensureObject([]));
		$obj->scalar = 0;
		self::assertEquals($obj, TPropertyValue::ensureObject(0));
		$obj->scalar = 1;
		self::assertEquals($obj, TPropertyValue::ensureObject(1));
		$obj->scalar = 'value';
		self::assertEquals($obj, TPropertyValue::ensureObject('value'));
		$obj = new stdClass();
		$obj->key = 'Prop';
		self::assertEquals($obj, TPropertyValue::ensureObject(['key' => 'Prop']));
		self::assertSame($obj, TPropertyValue::ensureObject($obj));
	}

	public function testEnsureObjectEdgeCases()
	{
		// Boolean inputs — PHP wraps scalars in stdClass{$scalar = value}
		$obj = new stdClass();
		$obj->scalar = true;
		self::assertEquals($obj, TPropertyValue::ensureObject(true));

		$obj->scalar = false;
		self::assertEquals($obj, TPropertyValue::ensureObject(false));

		// Float input
		$obj->scalar = 1.5;
		self::assertEquals($obj, TPropertyValue::ensureObject(1.5));

		// Negative int
		$obj->scalar = -3;
		self::assertEquals($obj, TPropertyValue::ensureObject(-3));

		// Array with multiple string keys
		$source = ['a' => 1, 'b' => 2, 'c' => 3];
		$expected = (object) $source;
		self::assertEquals($expected, TPropertyValue::ensureObject($source));

		// Existing non-stdClass object passes through as-is via (object) cast
		// — (object) on a non-stdClass returns the same instance
		$custom = new class {
			public int $x = 42;
		};
		self::assertSame($custom, TPropertyValue::ensureObject($custom));
	}
	
	public function testEnsureEnum()
	{
		//Multiple ways to operate.
		// $enums = ['value', 'value2', 'value3']
		self::assertEquals('value', TPropertyValue::ensureEnum('value', ['value', 'value2']));
		self::assertEquals('value2', TPropertyValue::ensureEnum('value2', ['value', 'value2']));
		try {
			self::assertEquals('value3', TPropertyValue::ensureEnum('value3', ['value', 'value2']));
			self::fail('failed to throw TInvalidDataValueException for value not in array');
		} catch (TInvalidDataValueException $e) {
		}
		try {
			self::assertEquals('Value', TPropertyValue::ensureEnum('Value', ['value', 'value2']));
			self::fail('failed to throw TInvalidDataValueException for value not in array');
		} catch (TInvalidDataValueException $e) {
		}

		// $enums = Class, look at class constant
		self::assertEquals('Off', TPropertyValue::ensureEnum('Off', \Prado\TApplicationMode::class));
		self::assertEquals('Debug', TPropertyValue::ensureEnum('Debug', \Prado\TApplicationMode::class));
		self::assertEquals('Normal', TPropertyValue::ensureEnum('Normal', \Prado\TApplicationMode::class));
		self::assertEquals('Performance', TPropertyValue::ensureEnum('Performance', \Prado\TApplicationMode::class));
		try {
			self::assertEquals('value', TPropertyValue::ensureEnum('value', \Prado\TApplicationMode::class));
			self::fail('failed to throw TInvalidDataValueException for value not in array');
		} catch (TInvalidDataValueException $e) {
		}

		// $enums = Class, look at class constant
		self::assertEquals('CLASS_FILE_EXT', TPropertyValue::ensureEnum('CLASS_FILE_EXT', \Prado\Prado::class));

		// more than one $enum param no function.
		self::assertEquals('Off', TPropertyValue::ensureEnum('Off', 'Off', 'Debug', 'Normal', 'Performance'));
		self::assertEquals('Debug', TPropertyValue::ensureEnum('Debug', 'Off', 'Debug', 'Normal', 'Performance'));
		self::assertEquals('Normal', TPropertyValue::ensureEnum('Normal', 'Off', 'Debug', 'Normal', 'Performance'));
		self::assertEquals('Performance', TPropertyValue::ensureEnum('Performance', 'Off', 'Debug', 'Normal', 'Performance'));
		try {
			self::assertEquals('value', TPropertyValue::ensureEnum('value', 'Off', 'Debug', 'Normal', 'Performance'));
			self::fail('failed to throw TInvalidDataValueException for value not in array');
		} catch (TInvalidDataValueException $e) {
		}
	}

	public function testEnsureEnumEdgeCases()
	{
		// ── Array form — strict comparison ────────────────────────────────────

		// Single-element array: only one valid value
		self::assertEquals('only', TPropertyValue::ensureEnum('only', ['only']));

		// Strict type check: string '1' does NOT match int 1 in array
		try {
			TPropertyValue::ensureEnum('1', [1, 2, 3]);
			self::fail('Expected TInvalidDataValueException for type mismatch');
		} catch (TInvalidDataValueException $e) {
		}

		// Empty array: always throws regardless of value
		try {
			TPropertyValue::ensureEnum('anything', []);
			self::fail('Expected TInvalidDataValueException for empty enum array');
		} catch (TInvalidDataValueException $e) {
		}

		// Exception message contains the invalid value
		try {
			TPropertyValue::ensureEnum('bad', ['good', 'ok']);
			self::fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			self::assertStringContainsString('bad', $e->getMessage());
		}

		// ── Variadic form (func_num_args > 2 or non-string second arg) ───────

		// Single-value variadic form: $value matches the trailing varargs.
		self::assertEquals('only', TPropertyValue::ensureEnum('only', 'only'));

		// Variadic form with many values
		self::assertEquals('z', TPropertyValue::ensureEnum('z', 'a', 'b', 'c', 'z'));

		// Variadic form throws when value absent
		try {
			TPropertyValue::ensureEnum('missing', 'a', 'b', 'c');
			self::fail('Expected TInvalidDataValueException for variadic miss');
		} catch (TInvalidDataValueException $e) {
		}

		// ── Class-constant form — case-insensitive, returns canonical constant NAME ──

		// Case-insensitive: 'debug' (lowercase) resolves to 'Debug' (the canonical name).
		// TApplicationMode has name == value, so the same string doubles as the value.
		self::assertEquals('Debug', TPropertyValue::ensureEnum('debug', \Prado\TApplicationMode::class));

		// Exception message names valid constants
		try {
			TPropertyValue::ensureEnum('invalid', \Prado\TApplicationMode::class);
			self::fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			self::assertStringContainsString('Off', $e->getMessage());
		}

		// ── Reflection cache: repeated calls do not throw ────────────────────
		// TComponentReflection::getReflectionClassByType() caches internally;
		// repeated ensureEnum calls for the same class reuse that cached instance.
		self::assertEquals('Off', TPropertyValue::ensureEnum('Off', \Prado\TApplicationMode::class));
		self::assertEquals('Debug', TPropertyValue::ensureEnum('Debug', \Prado\TApplicationMode::class));
	}
	
	// ── ensureEnum — class form with case-insensitive constant-name lookup ──

	public function testEnsureEnum_iEnumerable_caseInsensitiveName(): void
	{
		// Case-sensitive fast path matches; case-insensitive slow path
		// matches too.  TPropertyValueTestDirection has North='North', etc.
		// (name == value), so the canonical name returned doubles as the
		// constant value here.
		self::assertSame('North', TPropertyValue::ensureEnum('North', TPropertyValueTestDirection::class));
		self::assertSame('North', TPropertyValue::ensureEnum('north', TPropertyValueTestDirection::class));
		self::assertSame('South', TPropertyValue::ensureEnum('SOUTH', TPropertyValueTestDirection::class));
		self::assertSame('East',  TPropertyValue::ensureEnum('east',  TPropertyValueTestDirection::class));
		self::assertSame('West',  TPropertyValue::ensureEnum('wEsT',  TPropertyValueTestDirection::class));
	}

	public function testEnsureEnum_returnsCanonicalNameNotValue(): void
	{
		// When constant name differs from value (TCodeEnum has `const Alpha = 'a'`),
		// ensureEnum returns the canonical NAME (with original casing preserved),
		// NOT the value.  Pair with {@see TPropertyValue::ensureEnumValue()} for
		// the value-translation step.
		self::assertSame('Alpha', TPropertyValue::ensureEnum('Alpha', TPropertyValueTestCodeEnum::class));
		self::assertSame('Alpha', TPropertyValue::ensureEnum('alpha', TPropertyValueTestCodeEnum::class));
		self::assertSame('Alpha', TPropertyValue::ensureEnum('ALPHA', TPropertyValueTestCodeEnum::class));
		self::assertSame('Beta',  TPropertyValue::ensureEnum('Beta',  TPropertyValueTestCodeEnum::class));
		self::assertSame('Beta',  TPropertyValue::ensureEnum('BETA',  TPropertyValueTestCodeEnum::class));
	}

	public function testEnsureEnum_invalidName_throwsWithConstantListing(): void
	{
		// The error message lists every constant of the class so the caller
		// can see the valid set.
		try {
			TPropertyValue::ensureEnum('NotAMode', \Prado\TApplicationMode::class);
			self::fail('Expected TInvalidDataValueException for invalid constant');
		} catch (TInvalidDataValueException $e) {
			self::assertStringContainsString('NotAMode', $e->getMessage());
			self::assertStringContainsString('Debug',    $e->getMessage());
			self::assertStringContainsString('Normal',   $e->getMessage());
		}
	}

	// ── ensureEnumValue — case-insensitive name → constant VALUE ────────────

	public function testEnsureEnumValue_iEnumerable_returnsConstantValue(): void
	{
		// ensureEnumValue resolves the input name (case-insensitively) to the
		// constant's VALUE.  For `const Alpha = 'a'`, any casing of `'Alpha'`
		// resolves to `'a'`.  Companion to {@see ensureEnum()} which returns
		// the canonical name.
		self::assertSame('a', TPropertyValue::ensureEnumValue('Alpha', TPropertyValueTestCodeEnum::class));
		self::assertSame('a', TPropertyValue::ensureEnumValue('alpha', TPropertyValueTestCodeEnum::class));
		self::assertSame('a', TPropertyValue::ensureEnumValue('ALPHA', TPropertyValueTestCodeEnum::class));
		self::assertSame('b', TPropertyValue::ensureEnumValue('Beta',  TPropertyValueTestCodeEnum::class));
		self::assertSame('b', TPropertyValue::ensureEnumValue('BETA',  TPropertyValueTestCodeEnum::class));
	}

	public function testEnsureEnumValue_iEnumerable_nameEqualsValueNoOpDistinction(): void
	{
		// When name == value, ensureEnumValue and ensureEnum return the same
		// string — the distinction only matters for fixtures where name ≠ value.
		self::assertSame('North', TPropertyValue::ensureEnumValue('north', TPropertyValueTestDirection::class));
		self::assertSame('South', TPropertyValue::ensureEnumValue('SOUTH', TPropertyValueTestDirection::class));
		self::assertSame('East',  TPropertyValue::ensureEnumValue('EAST',  TPropertyValueTestDirection::class));
	}

	public function testEnsureEnumValue_invalidName_throws(): void
	{
		try {
			TPropertyValue::ensureEnumValue('not-a-constant', TPropertyValueTestCodeEnum::class);
			self::fail('Expected TInvalidDataValueException for invalid IEnumerable name');
		} catch (TInvalidDataValueException $e) {
			self::assertStringContainsString('not-a-constant', $e->getMessage());
			self::assertStringContainsString('Alpha', $e->getMessage());
			self::assertStringContainsString('Beta',  $e->getMessage());
		}
	}

	// ── ensureEnum — BackedEnum / UnitEnum support ──────────────────────────

	public function testEnsureEnum_backedEnum_returnsCaseName(): void
	{
		// PHP enum cases are class constants — they flow through the same
		// reflection-based path.  Returns the case name as a string (not
		// the case instance).
		self::assertSame('Red',   TPropertyValue::ensureEnum('Red',   TPropertyValueTestColor::class));
		self::assertSame('Red',   TPropertyValue::ensureEnum('red',   TPropertyValueTestColor::class));
		self::assertSame('Red',   TPropertyValue::ensureEnum('RED',   TPropertyValueTestColor::class));
		self::assertSame('Green', TPropertyValue::ensureEnum('green', TPropertyValueTestColor::class));
		self::assertSame('Blue',  TPropertyValue::ensureEnum('blue',  TPropertyValueTestColor::class));
		// Int-backed enum — name lookup works the same way.
		self::assertSame('Low',  TPropertyValue::ensureEnum('low',  TPropertyValueTestPriority::class));
		self::assertSame('High', TPropertyValue::ensureEnum('HIGH', TPropertyValueTestPriority::class));
	}

	public function testEnsureEnum_unitEnum_returnsCaseName(): void
	{
		// Non-backed UnitEnum cases are also class constants.
		self::assertSame('Active',  TPropertyValue::ensureEnum('active',  TPropertyValueTestStatus::class));
		self::assertSame('Pending', TPropertyValue::ensureEnum('PENDING', TPropertyValueTestStatus::class));
		self::assertSame('Closed',  TPropertyValue::ensureEnum('Closed',  TPropertyValueTestStatus::class));
	}

	public function testEnsureEnum_enumInstance_unwrappedToName(): void
	{
		// An already-typed enum instance passes through as its ->name.
		self::assertSame('Red',    TPropertyValue::ensureEnum(TPropertyValueTestColor::Red,    TPropertyValueTestColor::class));
		self::assertSame('High',   TPropertyValue::ensureEnum(TPropertyValueTestPriority::High, TPropertyValueTestPriority::class));
		self::assertSame('Active', TPropertyValue::ensureEnum(TPropertyValueTestStatus::Active, TPropertyValueTestStatus::class));
	}

	// ── ensureEnum — object as $enums ───────────────────────────────────────

	public function testEnsureEnum_objectAsEnums(): void
	{
		// When $enums is an object, its class is used for resolution.
		$instance = new TPropertyValueTestDirection();
		self::assertSame('East', TPropertyValue::ensureEnum('east', $instance));
		// Invalid value still throws.
		$this->expectException(TInvalidDataValueException::class);
		TPropertyValue::ensureEnum('InvalidDirection', $instance);
	}

	// ── ensureEnum — extras (list of permitted values) ──────────────────────

	public function testEnsureEnum_withExtras_classResolvesFirst(): void
	{
		// A valid class constant is resolved before extras are consulted.
		self::assertSame(
			'North',
			TPropertyValue::ensureEnum('North', TPropertyValueTestDirection::class, null, false, 'Auto')
		);
	}

	public function testEnsureEnum_withExtras_classFailsFallsToExtras(): void
	{
		// 'Auto' is not a constant in TApplicationMode but matches the extra.
		self::assertSame(
			'Auto',
			TPropertyValue::ensureEnum('Auto', \Prado\TApplicationMode::class, 'Auto')
		);
		self::assertFalse(
			TPropertyValue::ensureEnum(false, TPropertyValueTestDirection::class, null, false)
		);
		self::assertSame(
			0,
			TPropertyValue::ensureEnum(0, TPropertyValueTestDirection::class, null, false, 0, -1)
		);
	}

	public function testEnsureEnum_withExtras_nullExtra(): void
	{
		self::assertNull(TPropertyValue::ensureEnum(null, TPropertyValueTestDirection::class, null));
		// null is permitted, but false is not → throws.
		try {
			TPropertyValue::ensureEnum(false, TPropertyValueTestDirection::class, null);
			self::fail('Expected TInvalidDataValueException for unmatched value with null extra');
		} catch (TInvalidDataValueException $e) {
			self::assertStringContainsString('NULL', $e->getMessage());
		}
	}

	public function testEnsureEnum_withExtras_arrayShape(): void
	{
		// Extras may be passed as a single array or variadically — the two
		// shapes produce identical results.
		$cls = TPropertyValueTestDirection::class;
		self::assertSame('Auto',  TPropertyValue::ensureEnum('Auto', $cls, ['Auto', null, 0]));
		self::assertSame('Auto',  TPropertyValue::ensureEnum('Auto', $cls, 'Auto', null, 0));
		self::assertNull(TPropertyValue::ensureEnum(null, $cls, [null, false]));
		self::assertNull(TPropertyValue::ensureEnum(null, $cls, null, false));
		self::assertSame(-1, TPropertyValue::ensureEnum(-1, $cls, [-1, 0, 'Auto']));
		// A valid class constant still resolves first regardless of extras shape.
		self::assertSame('North', TPropertyValue::ensureEnum('North', $cls, ['Auto']));
		self::assertSame('North', TPropertyValue::ensureEnum('North', $cls, 'Auto'));
	}

	public function testEnsureEnum_withExtras_allFail_errorIncludesExtras(): void
	{
		try {
			TPropertyValue::ensureEnum(
				'NoSuchValue',
				TPropertyValueTestDirection::class,
				null,
				false,
				0,
				-1,
				'Auto'
			);
			self::fail('Expected throw when neither class nor extras match');
		} catch (TInvalidDataValueException $e) {
			$msg = $e->getMessage();
			self::assertStringContainsString('North',  $msg);
			self::assertStringContainsString('South',  $msg);
			self::assertStringContainsString('NULL',   $msg);
			self::assertStringContainsString('false',  $msg);
			// Int-keyed string extras surface as the bare name (no var_export quoting).
			self::assertStringContainsString('Auto',   $msg);
		}
	}

	// ── ensureEnumValue — BackedEnum / UnitEnum support ─────────────────────

	public function testEnsureEnumValue_backedEnum_returnsBackingValue(): void
	{
		// BackedEnum case constants are unwrapped to their ->value.
		self::assertSame('red',   TPropertyValue::ensureEnumValue('Red',   TPropertyValueTestColor::class));
		self::assertSame('red',   TPropertyValue::ensureEnumValue('red',   TPropertyValueTestColor::class));
		self::assertSame('green', TPropertyValue::ensureEnumValue('Green', TPropertyValueTestColor::class));
		self::assertSame('blue',  TPropertyValue::ensureEnumValue('BLUE',  TPropertyValueTestColor::class));
		// Int-backed enum.
		self::assertSame(1, TPropertyValue::ensureEnumValue('Low',  TPropertyValueTestPriority::class));
		self::assertSame(2, TPropertyValue::ensureEnumValue('high', TPropertyValueTestPriority::class));
	}

	public function testEnsureEnumValue_unitEnum_returnsCaseObject(): void
	{
		// Non-backed UnitEnum has no underlying value — the case object
		// (which IS the constant slot's content) is returned as-is.
		self::assertSame(TPropertyValueTestStatus::Active,  TPropertyValue::ensureEnumValue('active',  TPropertyValueTestStatus::class));
		self::assertSame(TPropertyValueTestStatus::Pending, TPropertyValue::ensureEnumValue('PENDING', TPropertyValueTestStatus::class));
	}

	public function testEnsureEnumValue_enumInstance_unwrapped(): void
	{
		// Instance pass-through: BackedEnum → ->value, UnitEnum → the case.
		self::assertSame('red', TPropertyValue::ensureEnumValue(TPropertyValueTestColor::Red,    TPropertyValueTestColor::class));
		self::assertSame(2,     TPropertyValue::ensureEnumValue(TPropertyValueTestPriority::High, TPropertyValueTestPriority::class));
		self::assertSame(TPropertyValueTestStatus::Active, TPropertyValue::ensureEnumValue(TPropertyValueTestStatus::Active, TPropertyValueTestStatus::class));
	}

	// ── ensureEnumValue — object as $enums ──────────────────────────────────

	public function testEnsureEnumValue_objectAsEnums(): void
	{
		$instance = new TPropertyValueTestDirection();
		self::assertSame('East', TPropertyValue::ensureEnumValue('east', $instance));
	}

	// ── ensureEnumValue — $extras key/value map ─────────────────────────────

	public function testEnsureEnumValue_extras_mapKeyToValue(): void
	{
		// $extras is a case-insensitive key→value map; matched keys resolve
		// to their mapped values.  Distinct from ensureEnum()'s extras
		// (list of permitted values).
		$extras = ['Auto' => 'auto', 'System' => 0, 'Default' => null];
		self::assertSame('auto', TPropertyValue::ensureEnumValue('Auto',    TPropertyValueTestDirection::class, $extras));
		self::assertSame('auto', TPropertyValue::ensureEnumValue('auto',    TPropertyValueTestDirection::class, $extras));
		self::assertSame('auto', TPropertyValue::ensureEnumValue('AUTO',    TPropertyValueTestDirection::class, $extras));
		self::assertSame(0,      TPropertyValue::ensureEnumValue('system',  TPropertyValueTestDirection::class, $extras));
		self::assertNull(TPropertyValue::ensureEnumValue('default', TPropertyValueTestDirection::class, $extras));
	}

	public function testEnsureEnumValue_extras_classResolvesFirst(): void
	{
		// A valid constant name is resolved before the extras map is consulted.
		$extras = ['North' => 'overridden', 'Auto' => 'auto'];
		self::assertSame('North', TPropertyValue::ensureEnumValue('North', TPropertyValueTestDirection::class, $extras));
		self::assertSame('auto',  TPropertyValue::ensureEnumValue('Auto',  TPropertyValueTestDirection::class, $extras));
	}

	public function testEnsureEnumValue_extras_allFail_errorIncludesExtraKeys(): void
	{
		try {
			TPropertyValue::ensureEnumValue(
				'NoSuchKey',
				TPropertyValueTestDirection::class,
				['Auto' => 'auto', 'Custom' => 99]
			);
			self::fail('Expected throw when neither class nor extras match');
		} catch (TInvalidDataValueException $e) {
			$msg = $e->getMessage();
			self::assertStringContainsString('NoSuchKey', $msg);
			self::assertStringContainsString('North',  $msg);
			self::assertStringContainsString('Auto',   $msg);
			self::assertStringContainsString('Custom', $msg);
		}
	}

	// ── ensureEnum / ensureEnumValue — unified extras semantics ─────────────

	/**
	 * Both methods accept the same extras shape and share matching rules; only
	 * the return value differs.  String-keyed entries are case-insensitive
	 * aliases on the KEY: ensureEnum returns the key (canonical name),
	 * ensureEnumValue returns the mapped value.
	 */
	public function testEnsureEnum_extras_stringKeyed_returnsCanonicalKey(): void
	{
		$cls = TPropertyValueTestDirection::class;
		// Same extras drive both methods to symmetric but distinct results.
		$extras = ['Auto' => 'auto', 'System' => 0, 'Default' => null];
		self::assertSame('Auto',   TPropertyValue::ensureEnum('auto',    $cls, $extras));
		self::assertSame('Auto',   TPropertyValue::ensureEnum('AUTO',    $cls, $extras));
		self::assertSame('System', TPropertyValue::ensureEnum('system',  $cls, $extras));
		self::assertSame('Default',TPropertyValue::ensureEnum('default', $cls, $extras));
		// And ensureEnumValue on the same extras returns the mapped values.
		self::assertSame('auto',   TPropertyValue::ensureEnumValue('auto',    $cls, $extras));
		self::assertSame(0,        TPropertyValue::ensureEnumValue('system',  $cls, $extras));
		self::assertNull(TPropertyValue::ensureEnumValue('default', $cls, $extras));
	}

	/**
	 * Int-keyed string extras are case-insensitive names whose string is its
	 * own value — both methods return the string itself on match.  This is
	 * the unified extension that lets `ensureEnum($v, $cls, 'Auto')` accept
	 * any casing of `'Auto'`.
	 */
	public function testEnsureEnum_extras_intKeyedString_caseInsensitive(): void
	{
		$cls = TPropertyValueTestDirection::class;
		// Variadic form.
		self::assertSame('Auto', TPropertyValue::ensureEnum('AUTO', $cls, 'Auto'));
		self::assertSame('Auto', TPropertyValue::ensureEnum('auto', $cls, 'Auto'));
		// Array form.
		self::assertSame('Auto', TPropertyValue::ensureEnum('AUTO', $cls, ['Auto']));
		// Mixed with sentinels — string match wins over sentinels for string $value.
		self::assertSame('Auto', TPropertyValue::ensureEnum('aUtO', $cls, [null, false, 'Auto']));
		// ensureEnumValue does the same thing for int-keyed strings.
		self::assertSame('Auto', TPropertyValue::ensureEnumValue('auto', $cls, ['Auto']));
		self::assertSame('Auto', TPropertyValue::ensureEnumValue('AUTO', $cls, ['Auto', null, false]));
	}

	/**
	 * Non-string $value never satisfies a string-form extra (int-keyed string
	 * OR string-keyed alias) — case-insensitive matching is string-only by
	 * design, so sentinels and aliases stay cleanly separated.
	 */
	public function testEnsureEnum_extras_nonStringValue_doesNotMatchStringForms(): void
	{
		$cls = TPropertyValueTestDirection::class;
		foreach ([null, false, true, 0, 1, -1] as $nonString) {
			// int-keyed string 'Auto' cannot satisfy a non-string $value.
			try {
				TPropertyValue::ensureEnum($nonString, $cls, ['Auto']);
				self::fail('Non-string value should not match int-keyed string extra: ' . var_export($nonString, true));
			} catch (TInvalidDataValueException $e) {
				self::assertInstanceOf(TInvalidDataValueException::class, $e);
			}
			// String-keyed alias 'Auto' => 'auto' cannot either.
			try {
				TPropertyValue::ensureEnum($nonString, $cls, ['Auto' => 'auto']);
				self::fail('Non-string value should not match string-keyed alias extra: ' . var_export($nonString, true));
			} catch (TInvalidDataValueException $e) {
				self::assertInstanceOf(TInvalidDataValueException::class, $e);
			}
		}
	}

	// ── ensureEnum — scalar sentinels in extras (TWebColor|false pattern) ───

	/**
	 * Properties typed as `TWebColor|false`, `TWebColor|null`, `TWebColor|int`,
	 * etc., rely on extras to admit scalar sentinels alongside the enum's
	 * constants without a separate branch.  This covers each of the six common
	 * scalar sentinels (null, false, true, -1, 0, 1) as an extras member that
	 * matches its corresponding $value input.
	 */
	public function testEnsureEnum_extras_scalarSentinels_eachMatchesItself(): void
	{
		$cls = TPropertyValueTestDirection::class;
		self::assertNull(TPropertyValue::ensureEnum(null,  $cls, null));
		self::assertFalse(TPropertyValue::ensureEnum(false, $cls, false));
		self::assertTrue(TPropertyValue::ensureEnum(true,  $cls, true));
		self::assertSame(-1, TPropertyValue::ensureEnum(-1, $cls, -1));
		self::assertSame(0,  TPropertyValue::ensureEnum(0,  $cls, 0));
		self::assertSame(1,  TPropertyValue::ensureEnum(1,  $cls, 1));
		// Same matrix in array-shape extras.
		self::assertNull(TPropertyValue::ensureEnum(null,  $cls, [null]));
		self::assertFalse(TPropertyValue::ensureEnum(false, $cls, [false]));
		self::assertTrue(TPropertyValue::ensureEnum(true,  $cls, [true]));
		self::assertSame(-1, TPropertyValue::ensureEnum(-1, $cls, [-1]));
		self::assertSame(0,  TPropertyValue::ensureEnum(0,  $cls, [0]));
		self::assertSame(1,  TPropertyValue::ensureEnum(1,  $cls, [1]));
		// All six sentinels coexist in a single extras list.
		$all = [null, false, true, -1, 0, 1];
		self::assertNull(TPropertyValue::ensureEnum(null,  $cls, $all));
		self::assertFalse(TPropertyValue::ensureEnum(false, $cls, $all));
		self::assertTrue(TPropertyValue::ensureEnum(true,  $cls, $all));
		self::assertSame(-1, TPropertyValue::ensureEnum(-1, $cls, $all));
		self::assertSame(0,  TPropertyValue::ensureEnum(0,  $cls, $all));
		self::assertSame(1,  TPropertyValue::ensureEnum(1,  $cls, $all));
		// Class constants still resolve ahead of the extras list.
		self::assertSame('North', TPropertyValue::ensureEnum('North', $cls, $all));
	}

	/**
	 * Strict equality guarantee: extras use `in_array(..., true)` so look-alike
	 * scalars do not cross-match.  `TWebColor|false` must reject `0`; an int-
	 * extras `1` must reject `true`; etc.  Locked in to prevent regression to
	 * loose comparison.
	 */
	public function testEnsureEnum_extras_scalarSentinels_strictNoCrossMatch(): void
	{
		$cls = TPropertyValueTestDirection::class;
		// Each pair: (extras-member, list of look-alike inputs that MUST be rejected).
		$matrix = [
			['extra' => false, 'bogus' => [0, '0', '', null]],
			['extra' => true,  'bogus' => [1, '1', 'true']],
			['extra' => null,  'bogus' => [false, 0, '']],
			['extra' => 0,     'bogus' => [false, null, '0']],
			['extra' => 1,     'bogus' => [true, '1']],
			['extra' => -1,    'bogus' => ['-1', true]],
		];
		foreach ($matrix as $row) {
			foreach ($row['bogus'] as $bogus) {
				try {
					TPropertyValue::ensureEnum($bogus, $cls, $row['extra']);
					self::fail(
						var_export($row['extra'], true) . ' extra must not cross-match '
						. var_export($bogus, true)
					);
				} catch (TInvalidDataValueException $e) {
					self::assertInstanceOf(TInvalidDataValueException::class, $e);
				}
			}
		}
	}

	// ── ensureEnumValue — non-existent class ────────────────────────────────

	/**
	 * Symmetric to ensureEnum's non-existent-class test: an unreflectable class
	 * name leaves the reflection cache holding null and triggers the extras-only
	 * fall-through, which throws when extras is also empty.
	 */
	public function testEnsureEnumValue_nonExistentClass_throwsInvalidDataValueException(): void
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		TPropertyValue::ensureEnumValue('Foo', 'TPropertyValueNonExistentClass99999XYZ');
	}

	// ── ensureEnumValue — mixed-shape extras (alias map + sentinel list) ────

	/**
	 * String-keyed extras act as a case-insensitive alias map (key compared to
	 * $value, mapped value returned); int-keyed extras act as a strict-equality
	 * permitted list (value compared to $value, returned on match).  The two
	 * shapes may be mixed freely in one array — covering sentinels like null,
	 * false, 0 without requiring synthetic keys.
	 */
	public function testEnsureEnumValue_mixedExtras_aliasMapAndSentinelList(): void
	{
		$cls = TPropertyValueTestDirection::class;
		// String-keyed alias still resolves a string $value.
		self::assertSame('auto', TPropertyValue::ensureEnumValue('Auto', $cls, ['Auto' => 'auto']));
		// Int-keyed sentinels are matched against $value with ===.
		self::assertNull(TPropertyValue::ensureEnumValue(null,   $cls, [null]));
		self::assertFalse(TPropertyValue::ensureEnumValue(false, $cls, [false]));
		self::assertTrue(TPropertyValue::ensureEnumValue(true,   $cls, [true]));
		self::assertSame(-1, TPropertyValue::ensureEnumValue(-1,  $cls, [-1]));
		self::assertSame(0,  TPropertyValue::ensureEnumValue(0,   $cls, [0]));
		self::assertSame(1,  TPropertyValue::ensureEnumValue(1,   $cls, [1]));
		// Mixed: alias map + sentinel list in one array.
		$mixed = ['Auto' => 'auto', null, false, -1];
		self::assertSame('auto', TPropertyValue::ensureEnumValue('Auto',  $cls, $mixed));
		self::assertSame('auto', TPropertyValue::ensureEnumValue('AUTO',  $cls, $mixed));
		self::assertNull(TPropertyValue::ensureEnumValue(null,  $cls, $mixed));
		self::assertFalse(TPropertyValue::ensureEnumValue(false, $cls, $mixed));
		self::assertSame(-1, TPropertyValue::ensureEnumValue(-1,  $cls, $mixed));
		// Class constants still resolve ahead of the extras list.
		self::assertSame('North', TPropertyValue::ensureEnumValue('north', $cls, $mixed));
	}

	/**
	 * Strict-equality guarantee for int-keyed extras: a `false` sentinel must
	 * not satisfy `0`, a `null` sentinel must not satisfy `false`, etc.
	 * Symmetric to the same matrix on ensureEnum.
	 */
	public function testEnsureEnumValue_intKeyedExtras_strictNoCrossMatch(): void
	{
		$cls = TPropertyValueTestDirection::class;
		$matrix = [
			['extra' => false, 'bogus' => [0, '0', '']],
			['extra' => true,  'bogus' => [1, '1']],
			['extra' => null,  'bogus' => [false, 0, '']],
			['extra' => 0,     'bogus' => [false, '0']],
			['extra' => 1,     'bogus' => [true, '1']],
		];
		foreach ($matrix as $row) {
			foreach ($row['bogus'] as $bogus) {
				try {
					TPropertyValue::ensureEnumValue($bogus, $cls, [$row['extra']]);
					self::fail(
						var_export($row['extra'], true) . ' int-keyed extra must not cross-match '
						. var_export($bogus, true)
					);
				} catch (TInvalidDataValueException $e) {
					self::assertInstanceOf(TInvalidDataValueException::class, $e);
				}
			}
		}
	}

	// ── ensureEnum — mixed-shape extras only normalize single-array form ────

	/**
	 * The single-array normalization fires only when extras is exactly one
	 * array argument.  Mixing a leading scalar with a trailing array leaves
	 * the array as a literal extras member (and matches nothing in strict
	 * equality unless $value is also an array).
	 */
	public function testEnsureEnum_mixedShapeExtras_doNotNormalize(): void
	{
		$cls = TPropertyValueTestDirection::class;
		// Two-arg form: ['North', ['extra1','extra2']] — the inner array is literal.
		// 'North' resolves on the class lookup before extras are considered.
		self::assertSame('North', TPropertyValue::ensureEnum('North', $cls, 'leading', ['trailing']));
		// The literal array IS findable when $value is the same array (strict ==).
		self::assertSame(['trailing'], TPropertyValue::ensureEnum(['trailing'], $cls, 'leading', ['trailing']));
		// And the leading scalar is still findable.
		self::assertSame('leading', TPropertyValue::ensureEnum('leading', $cls, 'leading', ['trailing']));
	}

	public function testEnsureNullIfEmpty()
	{
		self::assertNull(TPropertyValue::ensureNullIfEmpty(''));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(""));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(null));
		self::assertNull(TPropertyValue::ensureNullIfEmpty([]));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(false));
		self::assertNull(TPropertyValue::ensureNullIfEmpty('0'));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(0));

		self::assertEquals(true, TPropertyValue::ensureNullIfEmpty(true));
		self::assertEquals('value', TPropertyValue::ensureNullIfEmpty('value'));
		self::assertEquals('11', TPropertyValue::ensureNullIfEmpty('11'));
		self::assertEquals(11, TPropertyValue::ensureNullIfEmpty(11));
		self::assertEquals([11], TPropertyValue::ensureNullIfEmpty([11]));
		self::assertEquals(new stdClass(), TPropertyValue::ensureNullIfEmpty(new stdClass()));
	}

	public function testEnsureNullIfEmptyEdgeCases()
	{
		// ── Values that PHP empty() considers empty → null ──────────────────

		// Float zero — empty(0.0) is true
		self::assertNull(TPropertyValue::ensureNullIfEmpty(0.0));

		// Negative zero is still zero — empty(-0) is true
		self::assertNull(TPropertyValue::ensureNullIfEmpty(-0));

		// ── The '0.0' vs '0' distinction — key PHP empty() edge case ────────

		// String '0' → empty() returns true → null  (confirmed in base test)
		self::assertNull(TPropertyValue::ensureNullIfEmpty('0'));

		// String '0.0' → empty() returns FALSE (not a PHP-empty string)
		self::assertSame('0.0', TPropertyValue::ensureNullIfEmpty('0.0'));

		// String '00' → empty() returns false → '00'
		self::assertSame('00', TPropertyValue::ensureNullIfEmpty('00'));

		// String '0.00' → empty() returns false → '0.00'
		self::assertSame('0.00', TPropertyValue::ensureNullIfEmpty('0.00'));

		// ── Values that PHP empty() considers non-empty → returned as-is ─────

		// String 'false' (not bool false) → non-empty → returned
		self::assertSame('false', TPropertyValue::ensureNullIfEmpty('false'));

		// String '0 ' (zero with space) → not '0' → non-empty → returned
		self::assertSame('0 ', TPropertyValue::ensureNullIfEmpty('0 '));

		// Negative number → non-empty → returned
		self::assertSame(-1, TPropertyValue::ensureNullIfEmpty(-1));
		self::assertSame(-0.1, TPropertyValue::ensureNullIfEmpty(-0.1));

		// Non-empty array (even containing empty/falsy values) → returned
		self::assertSame([0], TPropertyValue::ensureNullIfEmpty([0]));
		self::assertSame([null], TPropertyValue::ensureNullIfEmpty([null]));
		self::assertSame([false], TPropertyValue::ensureNullIfEmpty([false]));
		self::assertSame([''], TPropertyValue::ensureNullIfEmpty(['']));

		// Positive float → returned
		self::assertSame(0.1, TPropertyValue::ensureNullIfEmpty(0.1));
	}
	
	
	// ════════════════════════════════════════════════════════════════════════
	// Helpers
	// ════════════════════════════════════════════════════════════════════════

	/** Returns the ReflectionType of a closure's first parameter. */
	private function typeOf(\Closure $fn): \ReflectionType
	{
		return (new \ReflectionFunction($fn))->getParameters()[0]->getType();
	}

	// ════════════════════════════════════════════════════════════════════════
	// coerceToType — null type passthrough
	// ════════════════════════════════════════════════════════════════════════

	public function testCoerceToTypeNullTypePassesThrough(): void
	{
		self::assertSame('hello', TPropertyValue::coerceToType('hello', null));
		self::assertSame(42,      TPropertyValue::coerceToType(42,      null));
		self::assertNull(         TPropertyValue::coerceToType(null,    null));
	}

	// ════════════════════════════════════════════════════════════════════════
	// coerceToType — named types (scalar)
	// ════════════════════════════════════════════════════════════════════════

	public function testCoerceToTypeBool(): void
	{
		$t = $this->typeOf(fn(bool $x) => $x);
		self::assertSame(true,  TPropertyValue::coerceToType('true',  $t));
		self::assertSame(false, TPropertyValue::coerceToType('false', $t));
		self::assertSame(true,  TPropertyValue::coerceToType('True',  $t));
		self::assertSame(true,  TPropertyValue::coerceToType(true,    $t));
		self::assertSame(false, TPropertyValue::coerceToType(false,   $t));
		self::assertSame(true,  TPropertyValue::coerceToType(1,       $t));
		self::assertSame(false, TPropertyValue::coerceToType(0,       $t));
	}

	public function testCoerceToTypeInt(): void
	{
		$t = $this->typeOf(fn(int $x) => $x);
		self::assertSame(42,  TPropertyValue::coerceToType('42',  $t));
		self::assertSame(0,   TPropertyValue::coerceToType('0',   $t));
		self::assertSame(-5,  TPropertyValue::coerceToType('-5',  $t));
		self::assertSame(7,   TPropertyValue::coerceToType(7,     $t));
		self::assertSame(1,   TPropertyValue::coerceToType(1.9,   $t)); // truncation
	}

	public function testCoerceToTypeFloat(): void
	{
		$t = $this->typeOf(fn(float $x) => $x);
		self::assertSame(3.14, TPropertyValue::coerceToType('3.14', $t));
		self::assertSame(0.0,  TPropertyValue::coerceToType('0',    $t));
		self::assertSame(-1.5, TPropertyValue::coerceToType(-1.5,   $t));
		self::assertSame(42.0, TPropertyValue::coerceToType(42,     $t));
	}

	public function testCoerceToTypeString(): void
	{
		$t = $this->typeOf(fn(string $x) => $x);
		self::assertSame('hello', TPropertyValue::coerceToType('hello', $t));
		// ensureString — bool-aware, not just (string) cast
		self::assertSame('true',  TPropertyValue::coerceToType(true,    $t));
		self::assertSame('false', TPropertyValue::coerceToType(false,   $t));
		self::assertSame('42',    TPropertyValue::coerceToType(42,      $t));
		self::assertSame('',      TPropertyValue::coerceToType('',      $t));
	}

	public function testCoerceToTypeArray(): void
	{
		$t = $this->typeOf(fn(array $x) => $x);
		self::assertSame(['a', 'b'], TPropertyValue::coerceToType('("a", "b")', $t));
		self::assertSame(['val'],    TPropertyValue::coerceToType('val',        $t));
		self::assertSame([1, 2],     TPropertyValue::coerceToType([1, 2],       $t));
		self::assertSame([],         TPropertyValue::coerceToType('',           $t));
	}

	public function testCoerceToTypeMixedPassesThrough(): void
	{
		$t = $this->typeOf(fn(mixed $x) => $x);
		self::assertSame('hello', TPropertyValue::coerceToType('hello', $t));
		self::assertSame(99,      TPropertyValue::coerceToType(99,      $t));
		self::assertNull(         TPropertyValue::coerceToType(null,    $t));
	}

	public function testCoerceToTypeIterable(): void
	{
		// iterable shares the same match arm as array — ensureArray() is called.
		$t = $this->typeOf(fn(iterable $x) => $x);
		self::assertSame(['a', 'b'], TPropertyValue::coerceToType('("a", "b")', $t));
		self::assertSame(['val'],    TPropertyValue::coerceToType('val',        $t));
		self::assertSame([1, 2],    TPropertyValue::coerceToType([1, 2],       $t));
		self::assertSame([],        TPropertyValue::coerceToType('',           $t));
		self::assertNull(           TPropertyValue::coerceToType(null,         $t));
	}

	public function testCoerceToTypeObject(): void
	{
		// ensureObject() casts the value with (object).
		$t = $this->typeOf(fn(object $x) => $x);
		$obj = new \stdClass();
		// An existing object passes through as the same instance.
		self::assertSame($obj, TPropertyValue::coerceToType($obj, $t));
		// An array is cast to stdClass with matching properties.
		$fromArray = TPropertyValue::coerceToType(['k' => 'v'], $t);
		self::assertInstanceOf(\stdClass::class, $fromArray);
		self::assertSame('v', $fromArray->k);
		// null triggers the unconditional null branch before the match.
		self::assertNull(TPropertyValue::coerceToType(null, $t));
	}

	public function testCoerceToTypeNullToNonNullableNamedTypeReturnsNull(): void
	{
		// The first branch in the named-type path is `if ($value === null ...)` with no
		// allowsNull() guard — null is returned for any named type, nullable or not.
		// Enforcement of the actual type constraint is left to the setter boundary.
		self::assertNull(TPropertyValue::coerceToType(null, $this->typeOf(fn(bool   $x) => $x)));
		self::assertNull(TPropertyValue::coerceToType(null, $this->typeOf(fn(int    $x) => $x)));
		self::assertNull(TPropertyValue::coerceToType(null, $this->typeOf(fn(float  $x) => $x)));
		self::assertNull(TPropertyValue::coerceToType(null, $this->typeOf(fn(string $x) => $x)));
		self::assertNull(TPropertyValue::coerceToType(null, $this->typeOf(fn(array  $x) => $x)));
		self::assertNull(TPropertyValue::coerceToType(null, $this->typeOf(fn(object $x) => $x)));
	}

	public function testCoerceToTypeEmptyStringToNonNullableTypes(): void
	{
		// Empty string does NOT trigger the null branch for non-nullable types (no allowsNull()),
		// so it flows to the match arms and is coerced via the appropriate ensure* helper.
		self::assertSame(false, TPropertyValue::coerceToType('', $this->typeOf(fn(bool  $x) => $x)));
		self::assertSame(0,     TPropertyValue::coerceToType('', $this->typeOf(fn(int   $x) => $x)));
		self::assertSame(0.0,   TPropertyValue::coerceToType('', $this->typeOf(fn(float $x) => $x)));
		self::assertSame([],    TPropertyValue::coerceToType('', $this->typeOf(fn(array $x) => $x)));
		// string '' → '' unchanged (still a valid string value).
		self::assertSame('',    TPropertyValue::coerceToType('', $this->typeOf(fn(string $x) => $x)));
	}

	public function testCoerceToTypeNonEnumerableClassPassesThrough(): void
	{
		// _coerceToClass returns $value unchanged for classes that are neither
		// BackedEnum nor IEnumerable.
		$t = $this->typeOf(fn(\stdClass $x) => $x);
		self::assertSame('whatever', TPropertyValue::coerceToType('whatever', $t));
		$obj = new \stdClass();
		self::assertSame($obj, TPropertyValue::coerceToType($obj, $t));
	}

	// ════════════════════════════════════════════════════════════════════════
	// coerceToType — nullable named types
	// ════════════════════════════════════════════════════════════════════════

	public function testCoerceToTypeNullableBool(): void
	{
		$t = $this->typeOf(fn(?bool $x) => $x);
		self::assertNull(         TPropertyValue::coerceToType('',    $t)); // empty → null
		self::assertNull(         TPropertyValue::coerceToType(null,  $t));
		self::assertSame(true,    TPropertyValue::coerceToType('true', $t));
		self::assertSame(false,   TPropertyValue::coerceToType('false', $t));
	}

	public function testCoerceToTypeNullableInt(): void
	{
		$t = $this->typeOf(fn(?int $x) => $x);
		self::assertNull(     TPropertyValue::coerceToType('',   $t));
		self::assertNull(     TPropertyValue::coerceToType(null, $t));
		self::assertSame(5,   TPropertyValue::coerceToType('5',  $t));
		self::assertSame(-3,  TPropertyValue::coerceToType('-3', $t));
	}

	public function testCoerceToTypeNullableString(): void
	{
		$t = $this->typeOf(fn(?string $x) => $x);
		// empty string on a nullable string → null (config semantics: blank attr = absent)
		self::assertNull(         TPropertyValue::coerceToType('',     $t));
		self::assertNull(         TPropertyValue::coerceToType(null,   $t));
		self::assertSame('hello', TPropertyValue::coerceToType('hello', $t));
	}

	public function testCoerceToTypeNullableFloat(): void
	{
		$t = $this->typeOf(fn(?float $x) => $x);
		self::assertNull(      TPropertyValue::coerceToType('',    $t));
		self::assertNull(      TPropertyValue::coerceToType(null,  $t));
		self::assertSame(1.5,  TPropertyValue::coerceToType('1.5', $t));
		self::assertSame(0.0,  TPropertyValue::coerceToType(0,     $t));
		self::assertSame(-2.5, TPropertyValue::coerceToType(-2.5,  $t));
	}

	public function testCoerceToTypeNullableArray(): void
	{
		$t = $this->typeOf(fn(?array $x) => $x);
		self::assertNull(            TPropertyValue::coerceToType('',          $t));
		self::assertNull(            TPropertyValue::coerceToType(null,        $t));
		self::assertSame(['x'],      TPropertyValue::coerceToType('x',         $t));
		self::assertSame(['a', 'b'], TPropertyValue::coerceToType('("a", "b")', $t));
		self::assertSame([1, 2],     TPropertyValue::coerceToType([1, 2],      $t));
	}

	public function testCoerceToTypeNullableObject(): void
	{
		$t = $this->typeOf(fn(?object $x) => $x);
		self::assertNull(TPropertyValue::coerceToType('',   $t));
		self::assertNull(TPropertyValue::coerceToType(null, $t));
		// An existing object is returned as the same instance.
		$obj = new \stdClass();
		self::assertSame($obj, TPropertyValue::coerceToType($obj, $t));
	}

	public function testCoerceToTypeIntersectionTypePassesThrough(): void
	{
		// ReflectionIntersectionType (A&B) reaches the final `return $value` in
		// coerceToType — no conversion is attempted.
		$t = $this->typeOf(fn(\Countable&\Iterator $x) => $x);
		// Conforming instance passes through.
		$obj = new \ArrayObject([1, 2, 3]);
		self::assertSame($obj, TPropertyValue::coerceToType($obj, $t));
		// Even a non-conforming value is returned unchanged (type enforcement at caller).
		self::assertSame('hello', TPropertyValue::coerceToType('hello', $t));
	}

	// ════════════════════════════════════════════════════════════════════════
	// coerceToType — backed enum
	// ════════════════════════════════════════════════════════════════════════

	public function testCoerceToTypeBackedEnum(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestColor $x) => $x);
		self::assertSame(TPropertyValueTestColor::Red,   TPropertyValue::coerceToType('red',   $t));
		self::assertSame(TPropertyValueTestColor::Green, TPropertyValue::coerceToType('green', $t));
		self::assertSame(TPropertyValueTestColor::Blue,  TPropertyValue::coerceToType('blue',  $t));
		// Invalid value: tryFrom returns null → original string falls back
		self::assertSame('purple', TPropertyValue::coerceToType('purple', $t));
	}

	public function testCoerceToTypeNullableBackedEnum(): void
	{
		$t = $this->typeOf(fn(?TPropertyValueTestColor $x) => $x);
		self::assertNull(TPropertyValue::coerceToType('', $t));
		self::assertNull(TPropertyValue::coerceToType(null, $t));
		self::assertSame(TPropertyValueTestColor::Red, TPropertyValue::coerceToType('red', $t));
	}

	public function testCoerceToTypeBackedEnumCaseInsensitiveCaseName(): void
	{
		// The case-name scan is case-insensitive: any casing of the PHP case name
		// resolves to the correct enum case even when the backing value differs.
		// TPropertyValueTestColor has Red='red', Green='green', Blue='blue'.
		$t = $this->typeOf(fn(TPropertyValueTestColor $x) => $x);
		// Exact case name (original exact-match behavior preserved)
		self::assertSame(TPropertyValueTestColor::Red,   TPropertyValue::coerceToType('Red',   $t));
		self::assertSame(TPropertyValueTestColor::Green, TPropertyValue::coerceToType('Green', $t));
		self::assertSame(TPropertyValueTestColor::Blue,  TPropertyValue::coerceToType('Blue',  $t));
		// All-caps — not a backing value, resolved by case-insensitive case-name scan
		self::assertSame(TPropertyValueTestColor::Red,   TPropertyValue::coerceToType('RED',   $t));
		self::assertSame(TPropertyValueTestColor::Green, TPropertyValue::coerceToType('GREEN', $t));
		self::assertSame(TPropertyValueTestColor::Blue,  TPropertyValue::coerceToType('BLUE',  $t));
		// Mixed-case
		self::assertSame(TPropertyValueTestColor::Red,   TPropertyValue::coerceToType('rEd',   $t));
		self::assertSame(TPropertyValueTestColor::Blue,  TPropertyValue::coerceToType('bLuE',  $t));
		// Unresolvable value still passes through
		self::assertSame('purple', TPropertyValue::coerceToType('purple', $t));
	}

	// ════════════════════════════════════════════════════════════════════════
	// coerceToType — TEnumerable subclasses
	// ════════════════════════════════════════════════════════════════════════

	public function testCoerceToTypeTEnumerableValidConstantName(): void
	{
		// TApplicationMode follows the convention const Off = 'Off', so name === value.
		// valueOfConstant('Off') returns 'Off'.
		$t = $this->typeOf(fn(\Prado\TApplicationMode $x) => $x);
		self::assertSame('Off',         TPropertyValue::coerceToType('Off',         $t));
		self::assertSame('Debug',       TPropertyValue::coerceToType('Debug',       $t));
		self::assertSame('Normal',      TPropertyValue::coerceToType('Normal',      $t));
		self::assertSame('Performance', TPropertyValue::coerceToType('Performance', $t));
	}

	public function testCoerceToTypeTEnumerableCaseInsensitiveName(): void
	{
		// valueOfConstant($value, false) accepts any casing of the constant name.
		// TApplicationMode has const Off = 'Off', so 'off' → 'Off', 'DEBUG' → 'Debug'.
		$t = $this->typeOf(fn(\Prado\TApplicationMode $x) => $x);
		self::assertSame('Off',         TPropertyValue::coerceToType('off',         $t));
		self::assertSame('Debug',       TPropertyValue::coerceToType('debug',       $t));
		self::assertSame('Normal',      TPropertyValue::coerceToType('NORMAL',      $t));
		self::assertSame('Performance', TPropertyValue::coerceToType('performance', $t));
	}

	public function testCoerceToTypeTEnumerableInvalidValuePassesThrough(): void
	{
		// An unrecognised value is returned unchanged so the TypeError surfaces at
		// the setter boundary rather than silently coercing to something unexpected.
		$t = $this->typeOf(fn(\Prado\TApplicationMode $x) => $x);
		self::assertSame('NotAMode', TPropertyValue::coerceToType('NotAMode', $t));
	}

	public function testCoerceToTypeNullableTEnumerableEmptyStringToNull(): void
	{
		$t = $this->typeOf(fn(?\Prado\TApplicationMode $x) => $x);
		self::assertNull(TPropertyValue::coerceToType('',   $t));
		self::assertNull(TPropertyValue::coerceToType(null, $t));
		self::assertSame('Debug', TPropertyValue::coerceToType('Debug', $t));
	}

	public function testCoerceToTypeUnionTEnumerableInUnion(): void
	{
		// TApplicationMode|null — valid constant name passes through, invalid falls back.
		$t = $this->typeOf(fn(\Prado\TApplicationMode|null $x) => $x);
		self::assertSame('Normal', TPropertyValue::coerceToType('Normal', $t));
		self::assertNull(TPropertyValue::coerceToType('', $t));
	}

	// ════════════════════════════════════════════════════════════════════════
	// IEnumerable — custom implementor (does not extend TEnumerable)
	// ════════════════════════════════════════════════════════════════════════

	public function testIEnumerableIsInterface(): void
	{
		$ref = new \ReflectionClass(IEnumerable::class);
		self::assertTrue($ref->isInterface());
	}

	public function testTEnumerableImplementsIEnumerable(): void
	{
		self::assertInstanceOf(IEnumerable::class, new \Prado\TApplicationMode());
		self::assertTrue(is_a(\Prado\TApplicationMode::class, IEnumerable::class, true));
	}

	public function testCustomIEnumerableNotExtendingTEnumerable(): void
	{
		// TPropertyValueTestDirection implements IEnumerable but does NOT extend TEnumerable.
		// _coerceToClass must still handle it via the IEnumerable check.
		self::assertFalse(is_a(TPropertyValueTestDirection::class, \Prado\TEnumerable::class, true));
		self::assertTrue(is_a(TPropertyValueTestDirection::class, IEnumerable::class, true));
	}

	public function testCoerceToTypeCustomIEnumerableValidName(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestDirection $x) => $x);
		self::assertSame('North', TPropertyValue::coerceToType('North', $t));
		self::assertSame('South', TPropertyValue::coerceToType('South', $t));
		self::assertSame('East',  TPropertyValue::coerceToType('East',  $t));
		self::assertSame('West',  TPropertyValue::coerceToType('West',  $t));
	}

	public function testCoerceToTypeCustomIEnumerableCaseInsensitiveName(): void
	{
		// valueOfConstant($value, false) is case-insensitive: any casing of the
		// constant name is accepted and resolves to the canonical constant value.
		// TPropertyValueTestDirection has North='North', South='South', etc.
		$t = $this->typeOf(fn(TPropertyValueTestDirection $x) => $x);
		self::assertSame('North', TPropertyValue::coerceToType('north', $t));
		self::assertSame('South', TPropertyValue::coerceToType('SOUTH', $t));
		self::assertSame('East',  TPropertyValue::coerceToType('east',  $t));
		self::assertSame('West',  TPropertyValue::coerceToType('wEsT',  $t));
	}

	public function testCoerceToTypeCodeEnumCaseInsensitiveName(): void
	{
		// When the constant name differs from the constant value (`const Alpha = 'a'`),
		// the coercion has validated the input string against the constant *name*
		// and returned the canonical name (case-corrected) — *not* the constant
		// value.  Any name→value translation has stayed inside the enum class
		// itself (e.g. via `TPropertyValueTestCodeEnum::valueOfConstant()`).
		// Compare {@see testEnsureEnum_iEnumerable_returnsCanonicalValue} which
		// is a different entry point and intentionally returns the value.
		$t = $this->typeOf(fn(TPropertyValueTestCodeEnum $x) => $x);
		self::assertSame('Alpha', TPropertyValue::coerceToType('alpha', $t));
		self::assertSame('Alpha', TPropertyValue::coerceToType('ALPHA', $t));
		self::assertSame('Beta',  TPropertyValue::coerceToType('beta',  $t));
		self::assertSame('Beta',  TPropertyValue::coerceToType('BETA',  $t));
	}

	public function testCoerceToTypeCustomIEnumerableInvalidPassesThrough(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestDirection $x) => $x);
		self::assertSame('NorthEast', TPropertyValue::coerceToType('NorthEast', $t));
	}

	public function testCoerceToTypeBackedEnumNonStringNonIntPassesThrough(): void
	{
		// _coerceToClass only calls tryFrom() when value is string or int.
		// Any other PHP type passes through unchanged — the setter boundary enforces
		// the type contract.
		$t = $this->typeOf(fn(TPropertyValueTestColor $x) => $x);
		self::assertSame(3.14,         TPropertyValue::coerceToType(3.14,         $t));
		self::assertSame(true,         TPropertyValue::coerceToType(true,         $t));
		self::assertSame(['not-enum'],  TPropertyValue::coerceToType(['not-enum'],  $t));
	}

	public function testCoerceToTypeIEnumerableNonStringPassesThrough(): void
	{
		// _coerceToClass only calls valueOfConstant() when value is string.
		// Non-strings pass through so the setter boundary can enforce the type.
		$t = $this->typeOf(fn(TPropertyValueTestDirection $x) => $x);
		self::assertSame(42,   TPropertyValue::coerceToType(42,   $t));
		self::assertSame(true, TPropertyValue::coerceToType(true, $t));
		self::assertSame(3.14, TPropertyValue::coerceToType(3.14, $t));
	}

	// ════════════════════════════════════════════════════════════════════════
	// TArrayIteratorTrait / TArrayCopyIteratorTrait / TReflectionCacheTrait
	// ════════════════════════════════════════════════════════════════════════

	public function testTConstantReflectionTraitStaticMethods(): void
	{
		// Basic happy-path coverage for all four static reflection methods.
		self::assertTrue(TPropertyValueTestSeason::hasConstant('Spring'));
		self::assertFalse(TPropertyValueTestSeason::hasConstant('Monsoon'));
		self::assertSame('Summer', TPropertyValueTestSeason::valueOfConstant('Summer'));
		self::assertNull(TPropertyValueTestSeason::valueOfConstant('Monsoon'));
		self::assertSame('Winter', TPropertyValueTestSeason::constantOfValue('Winter'));
		self::assertNull(TPropertyValueTestSeason::constantOfValue('Monsoon'));
		self::assertTrue(TPropertyValueTestSeason::hasConstantValue('Autumn'));
		self::assertFalse(TPropertyValueTestSeason::hasConstantValue('Monsoon'));
	}

	public function testTConstantReflectionTraitCaseInsensitive(): void
	{
		self::assertTrue(TPropertyValueTestSeason::hasConstant('spring', false));
		self::assertFalse(TPropertyValueTestSeason::hasConstant('spring', true));
		self::assertTrue(TPropertyValueTestSeason::hasConstantValue('spring', false));
		self::assertFalse(TPropertyValueTestSeason::hasConstantValue('spring', true));
		self::assertSame('Spring', TPropertyValueTestSeason::valueOfConstant('spring', false));
		self::assertNull(TPropertyValueTestSeason::valueOfConstant('spring', true));
		self::assertSame('Spring', TPropertyValueTestSeason::constantOfValue('spring', false));
		self::assertNull(TPropertyValueTestSeason::constantOfValue('spring', true));
	}

	public function testTConstantReflectionTraitPrefixAffix(): void
	{
		// Only constants whose name starts with 'S' should match.
		self::assertTrue(TPropertyValueTestSeason::hasConstant('Spring', 'S'));
		self::assertTrue(TPropertyValueTestSeason::hasConstant('Summer', 'S'));
		self::assertFalse(TPropertyValueTestSeason::hasConstant('Autumn', 'S'));
		self::assertSame('Spring', TPropertyValueTestSeason::valueOfConstant('Spring', 'S'));
		self::assertNull(TPropertyValueTestSeason::valueOfConstant('Autumn', 'S'));
		self::assertTrue(TPropertyValueTestSeason::hasConstantValue('Spring', 'S'));
		self::assertFalse(TPropertyValueTestSeason::hasConstantValue('Autumn', 'S'));
		self::assertSame('Spring', TPropertyValueTestSeason::constantOfValue('Spring', 'S'));
		self::assertNull(TPropertyValueTestSeason::constantOfValue('Autumn', 'S'));
	}

	public function testTConstantReflectionTraitSuffixAffix(): void
	{
		// Only constants whose name ends with 'er' should match (Summer, Winter).
		self::assertTrue(TPropertyValueTestSeason::hasConstant('Summer', '*er'));
		self::assertTrue(TPropertyValueTestSeason::hasConstant('Winter', '*er'));
		self::assertFalse(TPropertyValueTestSeason::hasConstant('Spring', '*er'));
		self::assertFalse(TPropertyValueTestSeason::hasConstant('Autumn', '*er'));
		self::assertSame('Summer', TPropertyValueTestSeason::constantOfValue('Summer', '*er'));
		self::assertNull(TPropertyValueTestSeason::constantOfValue('Spring', '*er'));
	}

	public function testTConstantReflectionTraitEmptyAffixFallsBackToPlainMatch(): void
	{
		// An empty string as $caseOrAffix must not trigger an offset warning and
		// must behave as a plain (no-affix) match.
		self::assertTrue(TPropertyValueTestSeason::hasConstant('Spring', ''));
		self::assertFalse(TPropertyValueTestSeason::hasConstant('Monsoon', ''));
		self::assertSame('Spring', TPropertyValueTestSeason::valueOfConstant('Spring', ''));
		self::assertNull(TPropertyValueTestSeason::valueOfConstant('Monsoon', ''));
	}

	public function testTArrayIteratorTraitLazyLoadsOnFirstAccess(): void
	{
		// No constructor on TPropertyValueTestSeason — store starts null.
		// Accessing the iterator must trigger getIteratorArrayCopy().
		$season = new TPropertyValueTestSeason();
		self::assertNull($season->getIteratorArrayDirect());
		$season->rewind(); // triggers loadIteratorArray()
		self::assertNotNull($season->getIteratorArrayDirect());
	}

	public function testTArrayIteratorTraitIteratesConstants(): void
	{
		$season = new TPropertyValueTestSeason();
		$collected = [];
		foreach ($season as $name => $value) {
			$collected[$name] = $value;
		}
		self::assertSame([
			'Spring' => 'Spring',
			'Summer' => 'Summer',
			'Autumn' => 'Autumn',
			'Winter' => 'Winter',
		], $collected);
	}

	public function testTArrayIteratorTraitRewinds(): void
	{
		$season = new TPropertyValueTestSeason();
		foreach ($season as $name => $value) {}
		$first = null;
		foreach ($season as $name => $value) {
			$first = $name;
			break;
		}
		self::assertSame('Spring', $first);
	}

	public function testSetIteratorArrayDirectOverridesLazyLoad(): void
	{
		$season = new TPropertyValueTestSeason();
		$season->setIteratorArrayDirect(['Dry' => 'Dry', 'Wet' => 'Wet']);
		$collected = [];
		foreach ($season as $name => $value) {
			$collected[$name] = $value;
		}
		self::assertSame(['Dry' => 'Dry', 'Wet' => 'Wet'], $collected);
	}

	public function testSetIteratorArrayDirectNullResetsLazyLoad(): void
	{
		$season = new TPropertyValueTestSeason();
		foreach ($season as $_) {} // populates store
		self::assertNotNull($season->getIteratorArrayDirect());
		$season->setIteratorArrayDirect(null); // reset
		self::assertNull($season->getIteratorArrayDirect());
		// Next access re-populates from getIteratorArrayCopy()
		$season->rewind();
		self::assertNotNull($season->getIteratorArrayDirect());
	}

	public function testTEnumerableLazyLoadsOnFirstIteratorAccess(): void
	{
		// TEnumerable has no constructor — store starts null and is populated
		// only on the first iterator access.
		$mode = new \Prado\TApplicationMode();
		$prop = new \ReflectionProperty(\Prado\TEnumerable::class, '_iterator_array');
		$prop->setAccessible(true);
		self::assertNull($prop->getValue($mode));
		$mode->rewind(); // triggers lazy load
		self::assertNotNull($prop->getValue($mode));
	}

	public function testTReflectionCacheTraitReturnsSameInstance(): void
	{
		// getReflectionClass() must return the identical cached object on every call
		// and must reflect the calling class via late static binding.
		$r1 = TPropertyValueTestSeason::getReflectionClass();
		$r2 = TPropertyValueTestSeason::getReflectionClass();
		self::assertSame($r1, $r2);
		self::assertSame(TPropertyValueTestSeason::class, $r1->getName());
		// A different class gets its own cached entry, not the same instance.
		$r3 = TPropertyValueTestDirection::getReflectionClass();
		self::assertNotSame($r1, $r3);
		self::assertSame(TPropertyValueTestDirection::class, $r3->getName());
	}

	public function testGetIteratorArrayOverrideIsUsed(): void
	{
		// TPropertyValueTestCustomIterator overrides getIteratorArrayCopy() to return
		// ['X' => 'x', 'Y' => 'y'] even though it has constants A and B.
		$obj = new TPropertyValueTestCustomIterator();
		$collected = [];
		foreach ($obj as $k => $v) {
			$collected[$k] = $v;
		}
		self::assertSame(['X' => 'x', 'Y' => 'y'], $collected);
		// Static reflection still sees the declared constants, not the iterator array.
		self::assertTrue(TPropertyValueTestCustomIterator::hasConstant('A'));
		self::assertFalse(TPropertyValueTestCustomIterator::hasConstant('X'));
	}

	public function testValidHandlesFalseValueInArray(): void
	{
		// valid() must not terminate early when the current value is false.
		$flags = new TPropertyValueTestFlags();
		$collected = [];
		foreach ($flags as $k => $v) {
			$collected[$k] = $v;
		}
		self::assertCount(3, $collected);
		self::assertArrayHasKey('Enabled', $collected);
		self::assertArrayHasKey('Disabled', $collected);
		self::assertArrayHasKey('Unknown', $collected);
		self::assertFalse($collected['Disabled']);
	}

	public function testValidReturnsFalseAfterExhaustion(): void
	{
		$season = new TPropertyValueTestSeason();
		foreach ($season as $_) {}
		// Pointer is now past the end — valid() must return false.
		self::assertFalse($season->valid());
		// key() and current() return null / false at end-of-array.
		self::assertNull($season->key());
		self::assertFalse($season->current());
	}

	public function testLoadIteratorArrayIsIdempotent(): void
	{
		// A second iteration must not call getIteratorArrayCopy() again;
		// the backing store populated on the first iteration is reused after rewind.
		$season = new TPropertyValueTestSeason();
		iterator_to_array($season);
		$storeAfterFirst = $season->getIteratorArrayDirect();
		$season->rewind();
		self::assertSame($storeAfterFirst, $season->getIteratorArrayDirect());
	}

	public function testTEnumerableClassUsesTraitChain(): void
	{
		// TEnumerable must use both TConstantReflectionTrait and TArrayCopyIteratorTrait.
		$traits = (new \ReflectionClass(\Prado\TEnumerable::class))->getTraitNames();
		self::assertContains(\Prado\Util\Traits\TConstantReflectionTrait::class, $traits);
		self::assertContains(\Prado\Util\Traits\TArrayCopyIteratorTrait::class, $traits);
	}

	// ════════════════════════════════════════════════════════════════════════
	// coerceToType — union types (heuristic chain)
	// ════════════════════════════════════════════════════════════════════════

	public function testCoerceToTypeUnionStringMemberPassesThrough(): void
	{
		// When string is in the union the value is already valid — no coercion
		$t = $this->typeOf(fn(int|string $x) => $x);
		self::assertSame('42',    TPropertyValue::coerceToType('42',    $t));
		self::assertSame('hello', TPropertyValue::coerceToType('hello', $t));
	}

	public function testCoerceToTypeUnionNullableCollapsesToNull(): void
	{
		$t = $this->typeOf(fn(int|null $x) => $x);
		self::assertNull(TPropertyValue::coerceToType('',   $t));
		self::assertNull(TPropertyValue::coerceToType(null, $t));
		self::assertSame(42, TPropertyValue::coerceToType('42', $t));
	}

	public function testCoerceToTypeUnionSingleNonNullDelegates(): void
	{
		// int|null with non-empty value → acts like plain int
		$t = $this->typeOf(fn(int|null $x) => $x);
		self::assertSame(7, TPropertyValue::coerceToType('7', $t));
	}

	public function testCoerceToTypeUnionArrayNotationWins(): void
	{
		// Array notation detected before numeric check
		$t = $this->typeOf(fn(int|array $x) => $x);
		self::assertSame(['a', 'b'], TPropertyValue::coerceToType('("a", "b")', $t));
		// Numeric: no array notation → int
		self::assertSame(42, TPropertyValue::coerceToType('42', $t));
	}

	public function testCoerceToTypeUnionArrayKeywordNotationWins(): void
	{
		// Bug CU1: step 6 (array notation) used to detect only `(...)` and
		// `[...]`, missing the PHP `array(...)` keyword form.  All three
		// must be recognized.
		$t = $this->typeOf(fn(int|array $x) => $x);
		self::assertSame([1, 2, 3], TPropertyValue::coerceToType('array(1, 2, 3)', $t));
		self::assertSame(['a', 'b'], TPropertyValue::coerceToType('Array("a", "b")', $t));
		self::assertSame([1, 2], TPropertyValue::coerceToType('array (1, 2)', $t));
		// `(...)` and `[...]` still work.
		self::assertSame(['x', 'y'], TPropertyValue::coerceToType('("x", "y")', $t));
		self::assertSame(['x', 'y'], TPropertyValue::coerceToType('["x", "y"]', $t));
	}

	public function testCoerceToTypeUnionBoolLiteralWins(): void
	{
		$t = $this->typeOf(fn(bool|int $x) => $x);
		self::assertSame(true,  TPropertyValue::coerceToType('true',  $t));
		self::assertSame(false, TPropertyValue::coerceToType('false', $t));
		// Numeric: not a bool literal → int (first type after bool)
		self::assertSame(42, TPropertyValue::coerceToType('42', $t));
	}

	public function testCoerceToTypeUnionIntFloat(): void
	{
		$t = $this->typeOf(fn(int|float $x) => $x);
		// No decimal → int
		self::assertSame(42,   TPropertyValue::coerceToType('42',   $t));
		// Decimal present → float
		self::assertSame(3.14, TPropertyValue::coerceToType('3.14', $t));
	}

	public function testCoerceToTypeUnionBackedEnum(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestColor|null $x) => $x);
		self::assertSame(TPropertyValueTestColor::Blue, TPropertyValue::coerceToType('blue', $t));
		self::assertNull(TPropertyValue::coerceToType('', $t));
		// Invalid enum value: falls back to fallback type (null handled, enum fails) → original
		self::assertSame('magenta', TPropertyValue::coerceToType('magenta', $t));
	}

	public function testCoerceToTypeUnionFallbackUsesTypeCoerceOrder(): void
	{
		// 'not-numeric' matches no heuristic → fallback (step 10).
		// TYPE_COERCE_ORDER places int before array, so ensureInteger('not-numeric') = 0,
		// matching PHP non-strict behavior (implicit string→int yields 0 with a notice).
		$t = $this->typeOf(fn(int|array $x) => $x);
		self::assertSame(0, TPropertyValue::coerceToType('not-numeric', $t));
		// array-only union: no int → array is first in order → single-element array.
		$ta = $this->typeOf(fn(array|object $x) => $x);
		self::assertSame(['not-numeric'], TPropertyValue::coerceToType('not-numeric', $ta));
	}

	public function testCoerceToTypeUnionNullWithoutNullMemberReturnsNull(): void
	{
		// The `$value === null` check in _coerceUnionType (step 1) is unconditional —
		// null is returned even when `null` is not a member of the union.
		$t = $this->typeOf(fn(bool|int $x) => $x);
		self::assertNull(TPropertyValue::coerceToType(null, $t));
	}

	public function testCoerceToTypeUnionEmptyStringNoNullInUnion(): void
	{
		// Empty string with no `null` member: the null short-circuit does not fire.
		// Neither array notation, bool literal, nor numeric apply to '', so the fallback
		// (step 10) sorts by TYPE_COERCE_ORDER.  int (position 0) comes before bool
		// (position 3); ensureInteger('') = 0.
		$t = $this->typeOf(fn(bool|int $x) => $x);
		self::assertSame(0, TPropertyValue::coerceToType('', $t));
	}

	public function testCoerceToTypeUnionStringMemberCoercesNonStringViaEnsureString(): void
	{
		// Step 4: when `string` is a union member and the value is NOT already a string,
		// ensureString() is called (bool→'true'/'false', int→string, etc.).
		$t = $this->typeOf(fn(string|int $x) => $x);
		self::assertSame('true',  TPropertyValue::coerceToType(true,  $t));
		self::assertSame('false', TPropertyValue::coerceToType(false, $t));
		self::assertSame('42',    TPropertyValue::coerceToType(42,    $t));
	}

	public function testCoerceToTypeUnionIterableInUnion(): void
	{
		// `iterable` in a union sets $hasArray=true, so array-notation strings (step 6)
		// are still parsed correctly.  Step 6 only fires for '(...)' / '[...]' / 'array(...)'
		// notation, so 'true'/'false' fall through to step 7 (bool literals) and are coerced to bool.
		$t = $this->typeOf(fn(iterable|bool $x) => $x);
		self::assertSame(['a', 'b'], TPropertyValue::coerceToType('("a", "b")', $t));
		self::assertSame(true,       TPropertyValue::coerceToType('true',       $t));
		self::assertSame(false,      TPropertyValue::coerceToType('false',      $t));
	}

	public function testCoerceToTypeUnionFloatOnlyNumeric(): void
	{
		// Step 8: when `float` is present but `int` is not, any numeric string → float.
		// Bool literals win step 7 before the numeric check (step 8).
		$t = $this->typeOf(fn(float|bool $x) => $x);
		self::assertSame(3.14, TPropertyValue::coerceToType('3.14', $t));
		self::assertSame(42.0, TPropertyValue::coerceToType('42',   $t));
		self::assertSame(true, TPropertyValue::coerceToType('true', $t));
	}

	public function testCoerceToTypeUnionScientificNotation(): void
	{
		// Step 8 (numeric shape): scientific notation ('1e5', '1E+3') contains no '.' but still represents
		// a float.  Without the stripos('e') check the str_contains('.') branch returned
		// (int)'1e5', whereas PHP non-strict mode promotes these strings to float.
		$t = $this->typeOf(fn(int|float $x) => $x);
		self::assertSame(100000.0,  TPropertyValue::coerceToType('1e5',    $t));  // lowercase e
		self::assertSame(100000.0,  TPropertyValue::coerceToType('1E5',    $t));  // uppercase E
		self::assertSame(1500.0,    TPropertyValue::coerceToType('1.5e3',  $t));  // dot + e
		self::assertSame(0.001,     TPropertyValue::coerceToType('1e-3',   $t));  // negative exponent
		self::assertSame(3000.0,    TPropertyValue::coerceToType('3e+3',   $t));  // explicit + exponent
		// Plain integers still resolve to int (no dot, no e)
		self::assertSame(42,        TPropertyValue::coerceToType('42',     $t));
		self::assertSame(-7,        TPropertyValue::coerceToType('-7',     $t));
		// Plain float (dot, no e) still resolves to float
		self::assertSame(3.14,      TPropertyValue::coerceToType('3.14',   $t));
	}

	public function testCoerceToTypeUnionLargeIntStringPromotesToFloat(): void
	{
		// F-08: a numeric string that exceeds PHP_INT_MAX (or is below PHP_INT_MIN) must
		// promote to float when both int and float are in the union (step 8 — numeric
		// shape), matching PHP's own non-strict coercion rules.  Before the fix, (int)$s
		// saturated silently at PHP_INT_MAX/MIN instead of returning the float representation.
		$t = $this->typeOf(fn(int|float $x) => $x);
		// Values that fit in int — must stay int
		self::assertSame(PHP_INT_MAX, TPropertyValue::coerceToType((string) PHP_INT_MAX, $t));
		self::assertSame(PHP_INT_MIN, TPropertyValue::coerceToType((string) PHP_INT_MIN, $t));
		// Values that overflow int — must promote to float
		$overMax = bcadd((string) PHP_INT_MAX, '1');
		$underMin = bcsub((string) PHP_INT_MIN, '1');
		self::assertIsFloat(TPropertyValue::coerceToType($overMax,  $t));
		self::assertIsFloat(TPropertyValue::coerceToType($underMin, $t));
		self::assertSame((float) $overMax,  TPropertyValue::coerceToType($overMax,  $t));
		self::assertSame((float) $underMin, TPropertyValue::coerceToType($underMin, $t));
	}

	public function testCoerceToTypeUnionBackedEnumInMultiMemberUnion(): void
	{
		// Step 9 (non-builtin class lookup) is only reached when multiple non-null
		// members exist (otherwise the single-non-null optimization delegates
		// directly).  A valid enum backing value is coerced; an invalid one
		// passes through unchanged.
		$t = $this->typeOf(fn(TPropertyValueTestColor|int $x) => $x);
		self::assertSame(TPropertyValueTestColor::Blue, TPropertyValue::coerceToType('blue', $t));
		// '99' is numeric → step 8 (numeric shape) → int, step 9 not reached.
		self::assertSame(99, TPropertyValue::coerceToType('99', $t));
	}

	public function testCoerceToTypeUnionIntBackedEnumFromPhpInt(): void
	{
		// F-16: step 9 previously called _coerceToClass($strValue, …) exclusively, so a PHP
		// int value was stringified to e.g. '1' before reaching tryFrom().  For int-backed
		// enums tryFrom('1') returns null (type mismatch), silently failing the coercion.
		// The fix tries _coerceToClass($value, …) first so the original PHP int reaches
		// tryFrom(1) and resolves correctly.
		//
		// Union uses bool (not string/int/float) so that:
		//   - step 4 is skipped (string absent)
		//   - step 5 Pass A/B are skipped (int/float absent, bool doesn't match an int)
		//   - step 8 is skipped (no int/float in union)
		// …forcing the PHP int to reach step 9 where _coerceToClass is called.
		$t = $this->typeOf(fn(TPropertyValueTestPriority|bool $x) => $x);
		// PHP int → int-backed enum via original-value path in step 9
		self::assertSame(TPropertyValueTestPriority::Low,  TPropertyValue::coerceToType(1, $t));
		self::assertSame(TPropertyValueTestPriority::High, TPropertyValue::coerceToType(2, $t));
		// Unknown int → no enum case, no bool literal → step 10 picks bool (lower TYPE_COERCE_ORDER
		// index than a non-builtin class); ensureBoolean(99) = true (non-zero numeric).
		self::assertSame(true, TPropertyValue::coerceToType(99, $t));
	}

	public function testCoerceToTypeUnionEnumValidatesNameWithCaseCorrection(): void
	{
		// Step 2 has validated the input string against TCodeEnum's constant
		// names case-insensitively and returned the *canonical name* — the
		// constant's value (`'a'` for `const Alpha = 'a'`) has NOT been
		// returned here; any name→value translation has stayed inside the
		// enum class.
		$t = $this->typeOf(fn(TPropertyValueTestCodeEnum|int $x) => $x);
		self::assertSame('Alpha', TPropertyValue::coerceToType('Alpha', $t));
		self::assertSame('Alpha', TPropertyValue::coerceToType('alpha', $t));
		self::assertSame('Alpha', TPropertyValue::coerceToType('ALPHA', $t));
		self::assertSame('Beta',  TPropertyValue::coerceToType('Beta',  $t));
		self::assertSame('Beta',  TPropertyValue::coerceToType('beta',  $t));
		// Unrecognized name: step 2 misses, fallback chain runs through to
		// step 10 (fallback) — int is first in TYPE_COERCE_ORDER, so
		// ensureInteger('Unknown') = 0.
		self::assertSame(0, TPropertyValue::coerceToType('Unknown', $t));
	}

	public function testCoerceToTypeUnionObjectInstancePreservedByNativeTypeShortCircuit(): void
	{
		// Step 3 (pre-string short-circuit): when $value is a non-string object
		// whose PHP type matches a non-builtin union member, it is returned
		// without any conversion.  Uses a plain stdClass (not an enum) to
		// confirm the general object path.
		$t = $this->typeOf(fn(\stdClass|int $x) => $x);
		$obj = new \stdClass();
		$obj->x = 'preserved';
		self::assertSame($obj, TPropertyValue::coerceToType($obj, $t));
	}

	// ════════════════════════════════════════════════════════════════════════
	// coerceToType — union native-type short-circuit (non-string values)
	// ════════════════════════════════════════════════════════════════════════

	public function testCoerceToTypeUnionNativeArrayPreserved(): void
	{
		// Without the native-type check an array value would be ensureString'd to "Array",
		// match no heuristic, and fall back to (int)"Array" = 0.
		$t = $this->typeOf(fn(int|array $x) => $x);
		self::assertSame([1, 2], TPropertyValue::coerceToType([1, 2], $t));
		self::assertSame([],     TPropertyValue::coerceToType([],     $t));
	}

	public function testCoerceToTypeUnionArrayPreservedWhenStringAlsoInUnion(): void
	{
		// Bug CU2: when `string` is in the union, step 4 (string member) used
		// to fire ensureString() on array values, producing the useless string
		// "Array" instead of preserving the array.  The pre-string short-
		// circuit (step 3) now claims array values before the string coercion
		// path fires.
		$t = $this->typeOf(fn(string|array $x) => $x);
		self::assertSame([1, 2, 3],          TPropertyValue::coerceToType([1, 2, 3], $t));
		self::assertSame([],                 TPropertyValue::coerceToType([],        $t));
		self::assertSame(['key' => 'value'], TPropertyValue::coerceToType(['key' => 'value'], $t));
		// A string value still passes through as-is.
		self::assertSame('hello',            TPropertyValue::coerceToType('hello',   $t));
		// Scalar non-string values (bool, int) still coerce to string when string
		// is in the union — the step 3 short-circuit is limited to arrays/objects.
		self::assertSame('true', TPropertyValue::coerceToType(true, $t));
		self::assertSame('42',   TPropertyValue::coerceToType(42,   $t));
	}

	public function testCoerceToTypeUnionNativeBoolPreserved(): void
	{
		$t = $this->typeOf(fn(bool|array $x) => $x);
		self::assertSame(true,  TPropertyValue::coerceToType(true,  $t));
		self::assertSame(false, TPropertyValue::coerceToType(false, $t));
	}

	public function testCoerceToTypeUnionNativeIntPreserved(): void
	{
		$t = $this->typeOf(fn(int|array $x) => $x);
		self::assertSame(42, TPropertyValue::coerceToType(42, $t));
		self::assertSame(-5, TPropertyValue::coerceToType(-5, $t));
	}

	public function testCoerceToTypeUnionNativeFloatPreserved(): void
	{
		$t = $this->typeOf(fn(float|array $x) => $x);
		self::assertSame(3.14, TPropertyValue::coerceToType(3.14, $t));
	}

	public function testCoerceToTypeUnionNativeEnumPreserved(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestColor|int $x) => $x);
		// Already the right enum type — should come back unchanged
		self::assertSame(TPropertyValueTestColor::Red, TPropertyValue::coerceToType(TPropertyValueTestColor::Red, $t));
	}

	public function testCoerceToTypeUnionBoolWidensToInt(): void
	{
		// Step 5 (native-type) pass B: when bool is absent from the union, a
		// native bool widens to int (true=1, false=0), matching PHP's non-
		// strict coercion behavior.
		$t = $this->typeOf(fn(int|array $x) => $x);
		self::assertSame(1, TPropertyValue::coerceToType(true,  $t));
		self::assertSame(0, TPropertyValue::coerceToType(false, $t));
	}

	public function testCoerceToTypeUnionBoolWidensToFloatWhenNoInt(): void
	{
		// When int is absent but float is present, bool widens to float (step 5 pass B).
		$t = $this->typeOf(fn(float|array $x) => $x);
		self::assertSame(1.0, TPropertyValue::coerceToType(true,  $t));
		self::assertSame(0.0, TPropertyValue::coerceToType(false, $t));
	}

	public function testCoerceToTypeUnionBoolPreservedWhenBoolInUnion(): void
	{
		// When bool IS in the union, step 5 pass A exact-match fires and the
		// value stays bool.
		$t = $this->typeOf(fn(bool|int $x) => $x);
		self::assertSame(true,  TPropertyValue::coerceToType(true,  $t));
		self::assertSame(false, TPropertyValue::coerceToType(false, $t));
	}

	public function testCoerceToTypeUnionIntWidensToFloat(): void
	{
		// Step 5 (native-type) pass B: when int is absent but float is
		// present, a native int widens to float (lossless promotion),
		// matching PHP non-strict behavior.
		$t = $this->typeOf(fn(float|array $x) => $x);
		self::assertSame(42.0, TPropertyValue::coerceToType(42, $t));
		self::assertSame(-1.0, TPropertyValue::coerceToType(-1, $t));
	}

	public function testCoerceToTypeUnionIntPreservedWhenIntInUnion(): void
	{
		// When int IS in the union, step 5 pass A exact-match fires and the
		// value stays int.
		$t = $this->typeOf(fn(int|float $x) => $x);
		self::assertSame(7,  TPropertyValue::coerceToType(7,  $t));
		self::assertSame(-3, TPropertyValue::coerceToType(-3, $t));
	}

	// ════════════════════════════════════════════════════════════════════════
	// Step 2 — enumerable transition (name lookup precedes string member)
	// ════════════════════════════════════════════════════════════════════════

	public function testCoerceToTypeUnionStep3IEnumerableNameWinsOverString(): void
	{
		// `TWebColor|string|null` with 'Red' has validated against TWebColor's
		// constant names and returned the canonical name `'Red'` (any-casing
		// input is corrected).  The enum has been used as a *validator*, not
		// a transition table — name→value translation has stayed inside the
		// class itself (e.g. via {@see TPropertyValue::ensureHexColor()}).
		// Pre-step-3 the `string` union member silently dominated and the
		// untouched input passed through.
		$t = $this->typeOf(fn(\Prado\Web\UI\TWebColor|string|null $x) => $x);
		self::assertSame('Red',  TPropertyValue::coerceToType('Red',  $t));
		self::assertSame('Red',  TPropertyValue::coerceToType('red',  $t));
		self::assertSame('Red',  TPropertyValue::coerceToType('RED',  $t));
		self::assertSame('Blue', TPropertyValue::coerceToType('Blue', $t));
		self::assertSame('Blue', TPropertyValue::coerceToType('BLUE', $t));
		// Unknown name has fallen through to step 4 (string in union) → pass through.
		self::assertSame('not-a-color', TPropertyValue::coerceToType('not-a-color', $t));
		// Empty string with nullable union → null (step 1 still fires first).
		self::assertNull(TPropertyValue::coerceToType('', $t));
	}

	public function testCoerceToTypeUnionStep3BackedEnumNameWinsOverString(): void
	{
		// `TPropertyValueTestColor|string|null` with a case name resolves to the case;
		// the backing value also resolves (BackedEnum::tryFrom path inside _tryMatchEnum).
		$t = $this->typeOf(fn(TPropertyValueTestColor|string|null $x) => $x);
		// Case name (any casing).
		self::assertSame(TPropertyValueTestColor::Red,  TPropertyValue::coerceToType('Red',  $t));
		self::assertSame(TPropertyValueTestColor::Red,  TPropertyValue::coerceToType('RED',  $t));
		self::assertSame(TPropertyValueTestColor::Blue, TPropertyValue::coerceToType('Blue', $t));
		// Backing value.
		self::assertSame(TPropertyValueTestColor::Blue, TPropertyValue::coerceToType('blue', $t));
		// Unknown → fall through to step 4 string member.
		self::assertSame('not-a-color', TPropertyValue::coerceToType('not-a-color', $t));
	}

	public function testCoerceToTypeBackedEnumGuardsAgainstBackingTypeMismatch(): void
	{
		// PHP 8.1+ `BackedEnum::tryFrom()` is strict on the backing type — a
		// string against an int-backed enum (or an int against a string-backed
		// enum) raises TypeError.  Both _tryMatchEnum() (string side) and
		// _coerceToClass() (int side) have wrapped the call so the lookup
		// falls through to the name scan / single-element pass-through.

		// String input against int-backed enum — should fall through to name
		// lookup and resolve via `cases()` iteration.
		$intBacked = $this->typeOf(fn(TPropertyValueTestPriority $x) => $x);
		self::assertSame(TPropertyValueTestPriority::Low,  TPropertyValue::coerceToType('Low',  $intBacked));
		self::assertSame(TPropertyValueTestPriority::High, TPropertyValue::coerceToType('High', $intBacked));
		// String backing value DOES work because the input is a string and the
		// backing-string check (gettype) matches — but for int-backed there's
		// no string backing, so the name path is the only one that resolves.
		self::assertSame(TPropertyValueTestPriority::Low, TPropertyValue::coerceToType('low', $intBacked));

		// Int input against string-backed enum — should pass through unchanged
		// (no name lookup for non-string inputs in _coerceToClass).
		$stringBacked = $this->typeOf(fn(TPropertyValueTestColor $x) => $x);
		self::assertSame(99, TPropertyValue::coerceToType(99, $stringBacked));

		// Mixed-enum union (string-backed + int-backed): a string name that
		// matches the int-backed enum has resolved via the second iteration
		// after the first enum's tryFrom raised the wrapped TypeError.
		$mixed = $this->typeOf(fn(TPropertyValueTestColor|TPropertyValueTestPriority $x) => $x);
		self::assertSame(TPropertyValueTestPriority::High, TPropertyValue::coerceToType('High', $mixed));
	}

	public function testCoerceToTypeUnionStep3MultipleEnumsFirstMatchWins(): void
	{
		// Union with two enumerable members.  Iteration order is the union's
		// declared order; the first matching enum wins.  TPropertyValueTestColor
		// has cases Red/Blue, TPropertyValueTestPriority has Low/High — disjoint
		// names, so each input resolves unambiguously.
		$t = $this->typeOf(fn(TPropertyValueTestColor|TPropertyValueTestPriority|null $x) => $x);
		self::assertSame(TPropertyValueTestColor::Red,    TPropertyValue::coerceToType('Red',  $t));
		self::assertSame(TPropertyValueTestPriority::Low, TPropertyValue::coerceToType('Low',  $t));
		self::assertSame(TPropertyValueTestPriority::High, TPropertyValue::coerceToType('High', $t));
	}

	public function testCoerceToTypeUnionStep3NonStringValueSkipsEnumTranslation(): void
	{
		// Step 2 only fires for string $value.  Non-string inputs go through
		// the existing chain — int 1 against a BackedEnum|bool union still
		// reaches step 9 (_coerceToClass) for backing-value lookup.
		$t = $this->typeOf(fn(TPropertyValueTestPriority|bool $x) => $x);
		self::assertSame(TPropertyValueTestPriority::Low,  TPropertyValue::coerceToType(1, $t));
		self::assertSame(TPropertyValueTestPriority::High, TPropertyValue::coerceToType(2, $t));
	}

	public function testCoerceToTypeUnionStep3EnumMissContinuesChain(): void
	{
		// An enum-name miss has let the rest of the coercion chain run.
		// `TWebColor|int|null` with the numeric string '42' — TWebColor has no
		// constant named '42', so step 2 finds nothing; step 8 (numeric shape)
		// converts '42' to int 42.
		$t = $this->typeOf(fn(\Prado\Web\UI\TWebColor|int|null $x) => $x);
		self::assertSame(42, TPropertyValue::coerceToType('42', $t));
		self::assertNull(TPropertyValue::coerceToType('', $t));
	}

	public function testCoerceToTypeUnionStep3PlainUnitEnumInUnion(): void
	{
		// A non-backed UnitEnum has no backing value — the step-3 path has had
		// to fall straight through to the cases() name scan.  Returns the case
		// object, not a string.
		$t = $this->typeOf(fn(TPropertyValueTestStatus|string $x) => $x);
		self::assertSame(TPropertyValueTestStatus::Active,  TPropertyValue::coerceToType('Active',  $t));
		self::assertSame(TPropertyValueTestStatus::Active,  TPropertyValue::coerceToType('active',  $t));
		self::assertSame(TPropertyValueTestStatus::Pending, TPropertyValue::coerceToType('PENDING', $t));
		// Name miss → step 4 string member.
		self::assertSame('archived', TPropertyValue::coerceToType('archived', $t));
	}

	public function testTryMatchEnumReturnsDistinctFormsByEnumKind(): void
	{
		// Locks in the return-shape invariant of {@see _tryMatchEnum()} that
		// the rest of the coercion layer depends on:
		//   - non-backed UnitEnum → case object
		//   - BackedEnum          → case object
		//   - IEnumerable         → canonical NAME (string, case-corrected)
		// All three are exercised via coerceToType on a single non-null union
		// with the same input string so each test asserts only its own kind.
		$status   = $this->typeOf(fn(TPropertyValueTestStatus|null $x) => $x);
		$color    = $this->typeOf(fn(TPropertyValueTestColor|null $x) => $x);
		$codeEnum = $this->typeOf(fn(TPropertyValueTestCodeEnum|null $x) => $x);
		self::assertSame(TPropertyValueTestStatus::Active, TPropertyValue::coerceToType('Active', $status));
		self::assertSame(TPropertyValueTestColor::Red,     TPropertyValue::coerceToType('Red',    $color));
		// IEnumerable returns the canonical NAME, not the value 'a'.
		self::assertSame('Alpha', TPropertyValue::coerceToType('Alpha', $codeEnum));
	}

	// ════════════════════════════════════════════════════════════════════════
	// coerceForSetter — reflection cache, typed and untyped setters
	// ════════════════════════════════════════════════════════════════════════

	public function testCoerceForSetterScalarTypes(): void
	{
		$obj = new class {
			public function setBool(bool $v): void {}
			public function setInt(int $v): void {}
			public function setFloat(float $v): void {}
			public function setStr(string $v): void {}
			public function setArr(array $v): void {}
		};
		$class = get_class($obj);

		$v = 'true';  TPropertyValue::coerceForSetter($class, 'setBool',  $v);  self::assertSame(true,    $v);
		$v = 'false'; TPropertyValue::coerceForSetter($class, 'setBool',  $v);  self::assertSame(false,   $v);
		$v = '42';    TPropertyValue::coerceForSetter($class, 'setInt',   $v);  self::assertSame(42,      $v);
		$v = '3.14';  TPropertyValue::coerceForSetter($class, 'setFloat', $v);  self::assertSame(3.14,    $v);
		$v = 'hello'; TPropertyValue::coerceForSetter($class, 'setStr',   $v);  self::assertSame('hello', $v);
		$v = 'a';     TPropertyValue::coerceForSetter($class, 'setArr',   $v);  self::assertSame(['a'],   $v);
	}

	public function testCoerceForSetterNoTypeHintLeavesValueUnchanged(): void
	{
		$obj = new class {
			public function setValue($v): void {}
		};
		$class = get_class($obj);
		$v = 'raw';
		TPropertyValue::coerceForSetter($class, 'setValue', $v);
		self::assertSame('raw', $v);
	}

	public function testCoerceForSetterNonExistentMethodLeavesValueUnchanged(): void
	{
		$v = 'hello';
		TPropertyValue::coerceForSetter(TComponent::class, 'setNonExistentXyz', $v);
		self::assertSame('hello', $v);
	}

	public function testCoerceForSetterCacheIsStable(): void
	{
		// Two calls for the same method must produce identical results (cache hit path)
		$obj = new class {
			public function setCount(int $v): void {}
		};
		$class = get_class($obj);
		$a = '7';
		$b = '7';
		TPropertyValue::coerceForSetter($class, 'setCount', $a);
		TPropertyValue::coerceForSetter($class, 'setCount', $b);
		self::assertSame($a, $b);
		self::assertSame(7, $a);
	}

	public function testCoerceForSetterCaseInsensitiveMethodName(): void
	{
		// PHP method calls are case-insensitive; the cache must not create separate
		// entries for different casings of the same setter name.
		$obj = new class {
			public function setFlag(bool $v): void {}
		};
		$class = get_class($obj);

		$a = 'true';
		$b = 'false';
		$c = 'true';
		TPropertyValue::coerceForSetter($class, 'setFlag',   $a); // populates cache
		TPropertyValue::coerceForSetter($class, 'SETFLAG',   $b); // must hit same entry
		TPropertyValue::coerceForSetter($class, 'SetFlag',   $c); // must hit same entry
		self::assertSame(true,  $a);
		self::assertSame(false, $b);
		self::assertSame(true,  $c);
	}

	// ════════════════════════════════════════════════════════════════════════
	// applyProperty
	// ════════════════════════════════════════════════════════════════════════

	public function testApplyPropertyCoercesStringViaTypedSetter(): void
	{
		$obj = new class extends TComponent {
			private bool $_enabled = false;
			private int  $_count   = 0;
			public function getEnabled(): bool { return $this->_enabled; }
			public function setEnabled(bool $v): void { $this->_enabled = $v; }
			public function getCount(): int { return $this->_count; }
			public function setCount(int $v): void { $this->_count = $v; }
		};

		TPropertyValue::applyProperty($obj, 'Enabled', 'true');
		self::assertSame(true, $obj->getEnabled());

		TPropertyValue::applyProperty($obj, 'Enabled', 'false');
		self::assertSame(false, $obj->getEnabled());

		TPropertyValue::applyProperty($obj, 'Count', '99');
		self::assertSame(99, $obj->getCount());
	}

	public function testApplyPropertyCoercesNonStringValues(): void
	{
		// Non-string values are coerced too, not passed through raw.
		// bool → bool setter: ensureBoolean is idempotent, result is unchanged
		$obj = new class extends TComponent {
			private bool   $_flag  = false;
			private string $_label = '';
			public function getFlag(): bool    { return $this->_flag; }
			public function setFlag(bool $v): void   { $this->_flag = $v; }
			public function getLabel(): string { return $this->_label; }
			public function setLabel(string $v): void { $this->_label = $v; }
		};

		TPropertyValue::applyProperty($obj, 'Flag', true);
		self::assertSame(true, $obj->getFlag());

		TPropertyValue::applyProperty($obj, 'Flag', false);
		self::assertSame(false, $obj->getFlag());

		// bool → string setter: ensureString(true) = "true", NOT the raw PHP cast "1"
		TPropertyValue::applyProperty($obj, 'Label', true);
		self::assertSame('true', $obj->getLabel());

		TPropertyValue::applyProperty($obj, 'Label', false);
		self::assertSame('false', $obj->getLabel());
	}

	public function testApplyPropertyGoesthroughSetterPipeline(): void
	{
		// A getter-only property triggers TInvalidOperationException via __set().
		// If applyProperty bypassed __set() and called the setter directly it would
		// silently succeed (or get a different error), so this confirms the route.
		$obj = new class extends TComponent {
			public function getReadOnly(): string { return 'r'; }
		};
		$this->expectException(\Prado\Exceptions\TInvalidOperationException::class);
		TPropertyValue::applyProperty($obj, 'ReadOnly', 'value');
	}

	public function testApplyPropertyWithSubPath(): void
	{
		// Ensure setSubProperty (which now calls applyProperty) coerces correctly
		$inner = new class extends TComponent {
			private int $_size = 0;
			public function getSize(): int { return $this->_size; }
			public function setSize(int $v): void { $this->_size = $v; }
		};
		$outer = new class($inner) extends TComponent {
			public function __construct(private object $_inner) { parent::__construct(); }
			public function getInner(): object { return $this->_inner; }
		};

		$outer->setSubProperty('Inner.Size', '7');
		self::assertSame(7, $inner->getSize());
	}

	public function testCoerceForSetterTEnumerableTypedSetter(): void
	{
		$obj = new class extends TComponent {
			public function setMode(\Prado\TApplicationMode $v): void {}
		};
		$class = get_class($obj);

		$v = 'Debug';
		TPropertyValue::coerceForSetter($class, 'setMode', $v);
		self::assertSame('Debug', $v);

		// Invalid value is passed through unchanged.
		$v = 'Unknown';
		TPropertyValue::coerceForSetter($class, 'setMode', $v);
		self::assertSame('Unknown', $v);
	}

	// ════════════════════════════════════════════════════════════════════════
	// coerceForSetter — behavior-aware (object form)
	// ════════════════════════════════════════════════════════════════════════

	public function testCoerceForSetterAcceptsObjectInsteadOfClassName(): void
	{
		// Passing an object works the same as passing get_class($object).
		$obj = new class extends TComponent {
			public function setScore(int $v): void {}
		};
		$v = '10';
		TPropertyValue::coerceForSetter($obj, 'setScore', $v);
		self::assertSame(10, $v);
	}

	public function testCoerceForSetterNoMatchingBehaviorLeavesValueUnchanged(): void
	{
		// TComponent with behaviors enabled but no behavior that exposes the setter.
		$obj = new class extends TComponent {
			// no setFoo on the class itself
		};
		$behavior = new class extends \Prado\Util\TBehavior {
			// also no setFoo
		};
		$obj->attachBehavior('b', $behavior);

		$v = 'raw';
		TPropertyValue::coerceForSetter($obj, 'setFoo', $v);
		self::assertSame('raw', $v);
	}

	public function testCoerceForSetterBehaviorTypedSetterIsUsed(): void
	{
		// When the class has no setter but an active behavior does, its type hint wins.
		$obj = new class extends TComponent {
			// no setFlag on this class
		};
		$behavior = new class extends \Prado\Util\TBehavior {
			public function setFlag(bool $v): void {}
		};
		$obj->attachBehavior('b', $behavior);

		$v = 'true';
		TPropertyValue::coerceForSetter($obj, 'setFlag', $v);
		self::assertSame(true, $v);

		$v = 'false';
		TPropertyValue::coerceForSetter($obj, 'setFlag', $v);
		self::assertSame(false, $v);
	}

	public function testCoerceForSetterDisabledBehaviorIsSkipped(): void
	{
		// A disabled behavior must not contribute its setter type hint.
		$obj = new class extends TComponent {
			// no setRating on this class
		};
		$behavior = new class extends \Prado\Util\TBehavior {
			public function setRating(int $v): void {}
		};
		$obj->attachBehavior('b', $behavior);
		$behavior->setEnabled(false);

		$v = '5';
		TPropertyValue::coerceForSetter($obj, 'setRating', $v);
		// Rating setter is on a disabled behavior — value must stay a string.
		self::assertSame('5', $v);
	}

	public function testCoerceForSetterClassSetterTakesPrecedenceOverBehavior(): void
	{
		// When the class has its own setter, reflection resolves there; the behavior
		// setter (with a different type) must NOT override it.
		$obj = new class extends TComponent {
			public function setCount(int $v): void {}  // class-level: int
		};
		$behavior = new class extends \Prado\Util\TBehavior {
			public function setCount(float $v): void {}  // behavior: float — should be ignored
		};
		$obj->attachBehavior('b', $behavior);

		$v = '3';
		TPropertyValue::coerceForSetter($obj, 'setCount', $v);
		// Must come from class setter (int), not behavior setter (float).
		self::assertSame(3, $v);
		self::assertIsInt($v);
	}

	public function testApplyPropertyBehaviorSetterTypeIsCoerced(): void
	{
		// applyProperty must call coerceForSetter even when the class has no setter,
		// because the object is a TComponent with behaviors enabled.
		$obj = new class extends TComponent {
			// no setLevel on this class
		};
		$behavior = new class extends \Prado\Util\TBehavior {
			private int $_level = 0;
			public function getLevel(): int { return $this->_level; }
			public function setLevel(int $v): void { $this->_level = $v; }
		};
		$obj->attachBehavior('b', $behavior);

		// '5' (string) must be coerced to int 5 before being passed to the behavior setter.
		TPropertyValue::applyProperty($obj, 'Level', '5');
		self::assertSame(5, $behavior->getLevel());
	}

	public function testEnsureHexColor()
	{
		// Integer Color in format 0x00RRGGBB
		self::assertEquals('#000000', TPropertyValue::ensureHexColor(0));
		self::assertEquals('#000101', TPropertyValue::ensureHexColor(257));
		self::assertEquals('#010101', TPropertyValue::ensureHexColor(257 + 65536));
		self::assertEquals('#FFFFFF', TPropertyValue::ensureHexColor(-1));
		
		// red green and blue as arguments.
		self::assertEquals('#808182', TPropertyValue::ensureHexColor(128, 129, 130));
		self::assertEquals('#008384', TPropertyValue::ensureHexColor(-1, 131, 132));
		self::assertEquals('#FF0001', TPropertyValue::ensureHexColor(256, 0, 1));
		self::assertEquals('#050000', TPropertyValue::ensureHexColor(5, -1, 0));
		self::assertEquals('#00FF0A', TPropertyValue::ensureHexColor(0, 256, 10));
		
		
		self::assertEquals('#808182', TPropertyValue::ensureHexColor([128, 129, 130]));
		self::assertEquals('#838485', TPropertyValue::ensureHexColor(['red' => 131, 'green' => 132, 'blue' => 133]));
		self::assertEquals('#898A8B', TPropertyValue::ensureHexColor([134, 135, 136, 'red' => 137, 'green' => 138, 'blue' => 139]));
		
		try {
			self::assertEquals('value', TPropertyValue::ensureHexColor([]));
			self::fail('failed to throw TInvalidDataValueException for null value');
		} catch(TInvalidDataValueException $e) {
		}
		
		try {
			self::assertEquals('value', TPropertyValue::ensureHexColor(null));
			self::fail('failed to throw TInvalidDataValueException for null value');
		} catch(TInvalidDataValueException $e) {
		}
		
		try {
			self::assertEquals('value', TPropertyValue::ensureHexColor(''));
			self::fail('failed to throw TInvalidDataValueException for blank value');
		} catch(TInvalidDataValueException $e) {
		}
		
		try {
			self::assertEquals('value', TPropertyValue::ensureHexColor('notAColor'));
			self::fail('failed to throw TInvalidDataValueException for not a color');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // 4 or 7 required
			self::assertEquals('value', TPropertyValue::ensureHexColor('#0'));
			self::fail('failed to throw TInvalidDataValueException for Improper data length "#0"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // 4 or 7 required
			self::assertEquals('value', TPropertyValue::ensureHexColor('#00'));
			self::fail('failed to throw TInvalidDataValueException for Improper data length "#00"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // 4 or 7 required
			self::assertEquals('value', TPropertyValue::ensureHexColor('#0000'));
			self::fail('failed to throw TInvalidDataValueException for Improper data length "#0000"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // 4 or 7 required
			self::assertEquals('value', TPropertyValue::ensureHexColor('#00000'));
			self::fail('failed to throw TInvalidDataValueException for Improper data length "#00000"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // 4 or 7 required
			self::assertEquals('value', TPropertyValue::ensureHexColor('#0000000'));
			self::fail('failed to throw TInvalidDataValueException for Improper data length "#0000000"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // bad data after '#', length 4
			self::assertEquals('value', TPropertyValue::ensureHexColor('#the'));
			self::fail('failed to throw TInvalidDataValueException for Improper data "#the"');
		} catch(TInvalidDataValueException $e) {
		}
		
		try { // bad data after '#', length 7
			self::assertEquals('value', TPropertyValue::ensureHexColor('#avalue'));
			self::fail('failed to throw TInvalidDataValueException for Improper data "#avalue"');
		} catch(TInvalidDataValueException $e) {
		}
		
		// Valid cases
		self::assertEquals('#012012', TPropertyValue::ensureHexColor('#012012'));
		self::assertEquals('#345345', TPropertyValue::ensureHexColor('#345345'));
		self::assertEquals('#678678', TPropertyValue::ensureHexColor('#678678'));
		self::assertEquals('#9AB9AB', TPropertyValue::ensureHexColor('#9AB9AB'));
		self::assertEquals('#CDECDE', TPropertyValue::ensureHexColor('#CDECDE'));
		self::assertEquals('#FABFAB', TPropertyValue::ensureHexColor('#FabFab'));
		self::assertEquals('#CDECDE', TPropertyValue::ensureHexColor('#cdecde'));
		self::assertEquals('#F01F01', TPropertyValue::ensureHexColor('#f01f01'));
		
		self::assertEquals('#225588', TPropertyValue::ensureHexColor('#258'));
		self::assertEquals('#BBEEDD', TPropertyValue::ensureHexColor('#BED'));
		self::assertEquals('#CCDDEE', TPropertyValue::ensureHexColor('#cde'));
		
		// Web Colors
		self::assertEquals('#FFFFFF', TPropertyValue::ensureHexColor('White'));
		self::assertEquals('#C0C0C0', TPropertyValue::ensureHexColor('silver')); //lower case
		self::assertEquals('#808080', TPropertyValue::ensureHexColor('GRAY'));	//uppers case
		// Genuinely mixed case — verifies TWebColor::valueOfConstant($v, false)
		// drives the lookup case-insensitively (the implementation that
		// replaced the old `new ReflectionClass + array_change_key_case`).
		self::assertEquals('#00BFFF', TPropertyValue::ensureHexColor('dEePsKyBlUe'));
		self::assertEquals('#FFB6C1', TPropertyValue::ensureHexColor('LiGhTpInK'));
		self::assertEquals('#000000', TPropertyValue::ensureHexColor('Black'));
		self::assertEquals('#FF0000', TPropertyValue::ensureHexColor('Red'));
		self::assertEquals('#800000', TPropertyValue::ensureHexColor('Maroon'));
		self::assertEquals('#FFFF00', TPropertyValue::ensureHexColor('Yellow'));
		self::assertEquals('#808000', TPropertyValue::ensureHexColor('Olive'));
		self::assertEquals('#00FF00', TPropertyValue::ensureHexColor('Lime'));
		self::assertEquals('#008000', TPropertyValue::ensureHexColor('Green'));
		self::assertEquals('#00FFFF', TPropertyValue::ensureHexColor('Aqua'));
		self::assertEquals('#008080', TPropertyValue::ensureHexColor('Teal'));
		self::assertEquals('#0000FF', TPropertyValue::ensureHexColor('Blue'));
		self::assertEquals('#000080', TPropertyValue::ensureHexColor('Navy'));
		self::assertEquals('#FF00FF', TPropertyValue::ensureHexColor('Fuchsia'));
		self::assertEquals('#800080', TPropertyValue::ensureHexColor('Purple'));
		
		// Extended Web Colors
		// Pink
		self::assertEquals('#C71585', TPropertyValue::ensureHexColor('MediumVioletRed'));
		self::assertEquals('#FF1493', TPropertyValue::ensureHexColor('DeepPink'));
		self::assertEquals('#DB7093', TPropertyValue::ensureHexColor('PaleVioletRed'));
		self::assertEquals('#FF69B4', TPropertyValue::ensureHexColor('HotPink'));
		self::assertEquals('#FFB6C1', TPropertyValue::ensureHexColor('LightPink'));
		self::assertEquals('#FFC0CB', TPropertyValue::ensureHexColor('Pink'));
		
		//Red
		self::assertEquals('#8B0000', TPropertyValue::ensureHexColor('DarkRed'));
		self::assertEquals('#FF0000', TPropertyValue::ensureHexColor('Red'));
		self::assertEquals('#B22222', TPropertyValue::ensureHexColor('Firebrick'));
		self::assertEquals('#DC143C', TPropertyValue::ensureHexColor('Crimson'));
		self::assertEquals('#CD5C5C', TPropertyValue::ensureHexColor('IndianRed'));
		self::assertEquals('#F08080', TPropertyValue::ensureHexColor('LightCoral'));
		self::assertEquals('#FA8072', TPropertyValue::ensureHexColor('Salmon'));
		self::assertEquals('#E9967A', TPropertyValue::ensureHexColor('DarkSalmon'));
		self::assertEquals('#FFA07A', TPropertyValue::ensureHexColor('LightSalmon'));
		
		//Orange
		self::assertEquals('#FF4500', TPropertyValue::ensureHexColor('OrangeRed'));
		self::assertEquals('#FF6347', TPropertyValue::ensureHexColor('Tomato'));
		self::assertEquals('#FF8C00', TPropertyValue::ensureHexColor('DarkOrange'));
		self::assertEquals('#FF7F50', TPropertyValue::ensureHexColor('Coral'));
		self::assertEquals('#FFA500', TPropertyValue::ensureHexColor('Orange'));
		
		//Yellow
		self::assertEquals('#BDB76B', TPropertyValue::ensureHexColor('DarkKhaki'));
		self::assertEquals('#FFD700', TPropertyValue::ensureHexColor('Gold'));
		self::assertEquals('#F0E68C', TPropertyValue::ensureHexColor('Khaki'));
		self::assertEquals('#FFDAB9', TPropertyValue::ensureHexColor('PeachPuff'));
		self::assertEquals('#FFFF00', TPropertyValue::ensureHexColor('Yellow'));
		self::assertEquals('#EEE8AA', TPropertyValue::ensureHexColor('PaleGoldenrod'));
		self::assertEquals('#FFE4B5', TPropertyValue::ensureHexColor('Moccasin'));
		self::assertEquals('#FFEFD5', TPropertyValue::ensureHexColor('PapayaWhip'));
		self::assertEquals('#FAFAD2', TPropertyValue::ensureHexColor('LightGoldenrodYellow'));
		self::assertEquals('#FFFACD', TPropertyValue::ensureHexColor('LemonChiffon'));
		self::assertEquals('#FFFFE0', TPropertyValue::ensureHexColor('LightYellow'));
		
		//Brown
		self::assertEquals('#800000', TPropertyValue::ensureHexColor('Maroon'));
		self::assertEquals('#A52A2A', TPropertyValue::ensureHexColor('Brown'));
		self::assertEquals('#8B4513', TPropertyValue::ensureHexColor('SaddleBrown'));
		self::assertEquals('#A0522D', TPropertyValue::ensureHexColor('Sienna'));
		self::assertEquals('#D2691E', TPropertyValue::ensureHexColor('Chocolate'));
		self::assertEquals('#B8860B', TPropertyValue::ensureHexColor('DarkGoldenrod'));
		self::assertEquals('#CD853F', TPropertyValue::ensureHexColor('Peru'));
		self::assertEquals('#BC8F8F', TPropertyValue::ensureHexColor('RosyBrown'));
		self::assertEquals('#DAA520', TPropertyValue::ensureHexColor('Goldenrod'));
		self::assertEquals('#F4A460', TPropertyValue::ensureHexColor('SandyBrown'));
		self::assertEquals('#D2B48C', TPropertyValue::ensureHexColor('Tan'));
		self::assertEquals('#DEB887', TPropertyValue::ensureHexColor('Burlywood'));
		self::assertEquals('#F5DEB3', TPropertyValue::ensureHexColor('Wheat'));
		self::assertEquals('#FFDEAD', TPropertyValue::ensureHexColor('NavajoWhite'));
		self::assertEquals('#FFE4C4', TPropertyValue::ensureHexColor('Bisque'));
		self::assertEquals('#FFEBCD', TPropertyValue::ensureHexColor('BlanchedAlmond'));
		self::assertEquals('#FFF8DC', TPropertyValue::ensureHexColor('Cornsilk'));
		
		//purple, violet, magenta
		self::assertEquals('#4B0082', TPropertyValue::ensureHexColor('Indigo'));
		self::assertEquals('#800080', TPropertyValue::ensureHexColor('Purple'));
		self::assertEquals('#8B008B', TPropertyValue::ensureHexColor('DarkMagenta'));
		self::assertEquals('#9400D3', TPropertyValue::ensureHexColor('DarkViolet'));
		self::assertEquals('#483D8B', TPropertyValue::ensureHexColor('DarkSlateBlue'));
		self::assertEquals('#8A2BE2', TPropertyValue::ensureHexColor('BlueViolet'));
		self::assertEquals('#9932CC', TPropertyValue::ensureHexColor('DarkOrchid'));
		self::assertEquals('#FF00FF', TPropertyValue::ensureHexColor('Fuchsia'));
		self::assertEquals('#FF00FF', TPropertyValue::ensureHexColor('Magenta'));
		self::assertEquals('#6A5ACD', TPropertyValue::ensureHexColor('SlateBlue'));
		self::assertEquals('#7B68EE', TPropertyValue::ensureHexColor('MediumSlateBlue'));
		self::assertEquals('#BA55D3', TPropertyValue::ensureHexColor('MediumOrchid'));
		self::assertEquals('#9370DB', TPropertyValue::ensureHexColor('MediumPurple'));
		self::assertEquals('#DA70D6', TPropertyValue::ensureHexColor('Orchid'));
		self::assertEquals('#EE82EE', TPropertyValue::ensureHexColor('Violet'));
		self::assertEquals('#DDA0DD', TPropertyValue::ensureHexColor('Plum'));
		self::assertEquals('#D8BFD8', TPropertyValue::ensureHexColor('Thistle'));
		self::assertEquals('#E6E6FA', TPropertyValue::ensureHexColor('Lavender'));
		
		//blue
		self::assertEquals('#191970', TPropertyValue::ensureHexColor('MidnightBlue'));
		self::assertEquals('#000080', TPropertyValue::ensureHexColor('Navy'));
		self::assertEquals('#00008B', TPropertyValue::ensureHexColor('DarkBlue'));
		self::assertEquals('#0000CD', TPropertyValue::ensureHexColor('MediumBlue'));
		self::assertEquals('#0000FF', TPropertyValue::ensureHexColor('Blue'));
		self::assertEquals('#4169E1', TPropertyValue::ensureHexColor('RoyalBlue'));
		self::assertEquals('#4682B4', TPropertyValue::ensureHexColor('SteelBlue'));
		self::assertEquals('#1E90FF', TPropertyValue::ensureHexColor('DodgerBlue'));
		self::assertEquals('#00BFFF', TPropertyValue::ensureHexColor('DeepSkyBlue'));
		self::assertEquals('#6495ED', TPropertyValue::ensureHexColor('CornflowerBlue'));
		self::assertEquals('#87CEEB', TPropertyValue::ensureHexColor('SkyBlue'));
		self::assertEquals('#87CEFA', TPropertyValue::ensureHexColor('LightSkyBlue'));
		self::assertEquals('#B0C4DE', TPropertyValue::ensureHexColor('LightSteelBlue'));
		self::assertEquals('#ADD8E6', TPropertyValue::ensureHexColor('LightBlue'));
		self::assertEquals('#B0E0E6', TPropertyValue::ensureHexColor('PowderBlue'));
		
		//cyan
		self::assertEquals('#008080', TPropertyValue::ensureHexColor('Teal'));
		self::assertEquals('#008B8B', TPropertyValue::ensureHexColor('DarkCyan'));
		self::assertEquals('#20B2AA', TPropertyValue::ensureHexColor('LightSeaGreen'));
		self::assertEquals('#5F9EA0', TPropertyValue::ensureHexColor('CadetBlue'));
		self::assertEquals('#00CED1', TPropertyValue::ensureHexColor('DarkTurquoise'));
		self::assertEquals('#48D1CC', TPropertyValue::ensureHexColor('MediumTurquoise'));
		self::assertEquals('#40E0D0', TPropertyValue::ensureHexColor('Turquoise'));
		self::assertEquals('#00FFFF', TPropertyValue::ensureHexColor('Aqua'));
		self::assertEquals('#00FFFF', TPropertyValue::ensureHexColor('Cyan'));
		self::assertEquals('#7FFFD4', TPropertyValue::ensureHexColor('Aquamarine'));
		self::assertEquals('#AFEEEE', TPropertyValue::ensureHexColor('PaleTurquoise'));
		self::assertEquals('#E0FFFF', TPropertyValue::ensureHexColor('LightCyan'));
		
		//green
		self::assertEquals('#006400', TPropertyValue::ensureHexColor('DarkGreen'));
		self::assertEquals('#008000', TPropertyValue::ensureHexColor('Green'));
		self::assertEquals('#556B2F', TPropertyValue::ensureHexColor('DarkOliveGreen'));
		self::assertEquals('#228B22', TPropertyValue::ensureHexColor('ForestGreen'));
		self::assertEquals('#2E8B57', TPropertyValue::ensureHexColor('SeaGreen'));
		self::assertEquals('#808000', TPropertyValue::ensureHexColor('Olive'));
		self::assertEquals('#6B8E23', TPropertyValue::ensureHexColor('OliveDrab'));
		self::assertEquals('#3CB371', TPropertyValue::ensureHexColor('MediumSeaGreen'));
		self::assertEquals('#32CD32', TPropertyValue::ensureHexColor('LimeGreen'));
		self::assertEquals('#00FF00', TPropertyValue::ensureHexColor('Lime'));
		self::assertEquals('#00FF7F', TPropertyValue::ensureHexColor('SpringGreen'));
		self::assertEquals('#00FA9A', TPropertyValue::ensureHexColor('MediumSpringGreen'));
		self::assertEquals('#8FBC8F', TPropertyValue::ensureHexColor('DarkSeaGreen'));
		self::assertEquals('#66CDAA', TPropertyValue::ensureHexColor('MediumAquamarine'));
		self::assertEquals('#9ACD32', TPropertyValue::ensureHexColor('YellowGreen'));
		self::assertEquals('#7CFC00', TPropertyValue::ensureHexColor('LawnGreen'));
		self::assertEquals('#7FFF00', TPropertyValue::ensureHexColor('Chartreuse'));
		self::assertEquals('#90EE90', TPropertyValue::ensureHexColor('LightGreen'));
		self::assertEquals('#ADFF2F', TPropertyValue::ensureHexColor('GreenYellow'));
		self::assertEquals('#98FB98', TPropertyValue::ensureHexColor('PaleGreen'));
		
		//white
		self::assertEquals('#FFE4E1', TPropertyValue::ensureHexColor('MistyRose'));
		self::assertEquals('#FAEBD7', TPropertyValue::ensureHexColor('AntiqueWhite'));
		self::assertEquals('#FAF0E6', TPropertyValue::ensureHexColor('Linen'));
		self::assertEquals('#F5F5DC', TPropertyValue::ensureHexColor('Beige'));
		self::assertEquals('#F5F5F5', TPropertyValue::ensureHexColor('WhiteSmoke'));
		self::assertEquals('#FFF0F5', TPropertyValue::ensureHexColor('LavenderBlush'));
		self::assertEquals('#FDF5E6', TPropertyValue::ensureHexColor('OldLace'));
		self::assertEquals('#F0F8FF', TPropertyValue::ensureHexColor('AliceBlue'));
		self::assertEquals('#FFF5EE', TPropertyValue::ensureHexColor('Seashell'));
		self::assertEquals('#F8F8FF', TPropertyValue::ensureHexColor('GhostWhite'));
		self::assertEquals('#F0FFF0', TPropertyValue::ensureHexColor('Honeydew'));
		self::assertEquals('#FFFAF0', TPropertyValue::ensureHexColor('FloralWhite'));
		self::assertEquals('#F0FFFF', TPropertyValue::ensureHexColor('Azure'));
		self::assertEquals('#F5FFFA', TPropertyValue::ensureHexColor('MintCream'));
		self::assertEquals('#FFFAFA', TPropertyValue::ensureHexColor('Snow'));
		self::assertEquals('#FFFFF0', TPropertyValue::ensureHexColor('Ivory'));
		self::assertEquals('#FFFFFF', TPropertyValue::ensureHexColor('White'));
		
		//gray
		self::assertEquals('#000000', TPropertyValue::ensureHexColor('Black'));
		self::assertEquals('#2F4F4F', TPropertyValue::ensureHexColor('DarkSlateGray'));
		self::assertEquals('#696969', TPropertyValue::ensureHexColor('DimGray'));
		self::assertEquals('#708090', TPropertyValue::ensureHexColor('SlateGray'));
		self::assertEquals('#808080', TPropertyValue::ensureHexColor('Gray'));
		self::assertEquals('#778899', TPropertyValue::ensureHexColor('LightSlateGray'));
		self::assertEquals('#A9A9A9', TPropertyValue::ensureHexColor('DarkGray'));
		self::assertEquals('#C0C0C0', TPropertyValue::ensureHexColor('Silver'));
		self::assertEquals('#D3D3D3', TPropertyValue::ensureHexColor('LightGray'));
		self::assertEquals('#DCDCDC', TPropertyValue::ensureHexColor('Gainsboro'));
	}

	/**
	 * An array keyed with the COLOR_* constants must produce the same result as
	 * one keyed with the equivalent literal strings.
	 */
	public function testEnsureHexColor_arrayForm_withColorConstantKeys(): void
	{
		$viaConstants = TPropertyValue::ensureHexColor([
			TPropertyValue::COLOR_RED   => 0x11,
			TPropertyValue::COLOR_GREEN => 0x22,
			TPropertyValue::COLOR_BLUE  => 0x33,
		]);
		$viaLiterals = TPropertyValue::ensureHexColor([
			'red'   => 0x11,
			'green' => 0x22,
			'blue'  => 0x33,
		]);
		self::assertSame('#112233', $viaConstants);
		self::assertSame($viaLiterals, $viaConstants);
	}

	// ════════════════════════════════════════════════════════════════════════
	// ensureHexColor — $green=false disables web-color name lookup
	// ════════════════════════════════════════════════════════════════════════

	/**
	 * When $green is false the web-color name table is skipped; a '#'-prefixed hex
	 * string is still converted normally.
	 */
	public function testEnsureHexColor_greenFalse_hexStringStillConverted(): void
	{
		self::assertSame('#FF0000', TPropertyValue::ensureHexColor('#FF0000', false));
		self::assertSame('#00FF00', TPropertyValue::ensureHexColor('#00FF00', false));
		self::assertSame('#0000FF', TPropertyValue::ensureHexColor('#0000FF', false));
		self::assertSame('#112233', TPropertyValue::ensureHexColor('#112233', false));
		// 3-digit short form is also expanded.
		self::assertSame('#AABBCC', TPropertyValue::ensureHexColor('#ABC', false));
	}

	/**
	 * When $green is false a web-color name such as 'Red' is not looked up and
	 * fails hex validation, throwing TInvalidDataValueException.
	 */
	public function testEnsureHexColor_greenFalse_colorNameThrows(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		TPropertyValue::ensureHexColor('Red', false);
	}

	/**
	 * Confirm the same throw for a different color name when $green=false, to
	 * ensure the guard is not value-specific.
	 */
	public function testEnsureHexColor_greenFalse_anotherColorNameThrows(): void
	{
		$this->expectException(TInvalidDataValueException::class);
		TPropertyValue::ensureHexColor('White', false);
	}

	// ════════════════════════════════════════════════════════════════════════
	// ensureHexColor — float RGB values are truncated toward zero
	// ════════════════════════════════════════════════════════════════════════

	/**
	 * Float channel values are cast to int (truncation, not rounding) before
	 * clamping and hex conversion.
	 */
	public function testEnsureHexColor_floatRgbValues_areTruncated(): void
	{
		// 128.7 → 128 = 0x80, 100.9 → 100 = 0x64, 64.1 → 64 = 0x40
		self::assertSame('#806440', TPropertyValue::ensureHexColor(128.7, 100.9, 64.1));
		// 0.9 truncates to 0 for all channels.
		self::assertSame('#000000', TPropertyValue::ensureHexColor(0.9, 0.9, 0.9));
		// 254.999 truncates to 254 = 0xFE (not rounded up to 255).
		self::assertSame('#FEFEFE', TPropertyValue::ensureHexColor(254.999, 254.999, 254.999));
		// 255.9 truncates to 255 = 0xFF.
		self::assertSame('#FFFFFF', TPropertyValue::ensureHexColor(255.9, 255.9, 255.9));
	}

	// ════════════════════════════════════════════════════════════════════════
	// ensureHexColor — out-of-range RGB values are clamped to [0, 255]
	// ════════════════════════════════════════════════════════════════════════

	/**
	 * Values greater than 255 are clamped to 255.
	 */
	public function testEnsureHexColor_largeRgbValues_clampedTo255(): void
	{
		self::assertSame('#FFFFFF', TPropertyValue::ensureHexColor(999,  999,  999));
		self::assertSame('#FF0000', TPropertyValue::ensureHexColor(300,  0,    0));
		self::assertSame('#00FF00', TPropertyValue::ensureHexColor(0,    300,  0));
		self::assertSame('#0000FF', TPropertyValue::ensureHexColor(0,    0,    300));
		self::assertSame('#FF8000', TPropertyValue::ensureHexColor(300,  128,  0));
		// Exact boundary: 255 must not be clamped.
		self::assertSame('#FFFFFF', TPropertyValue::ensureHexColor(255,  255,  255));
		// One over: 256 clamps to 255.
		self::assertSame('#FFFFFF', TPropertyValue::ensureHexColor(256,  256,  256));
	}

	/**
	 * Negative values are clamped to 0.
	 */
	public function testEnsureHexColor_negativeRgbValues_clampedToZero(): void
	{
		self::assertSame('#000000', TPropertyValue::ensureHexColor(-1,   -1,   -1));
		self::assertSame('#000000', TPropertyValue::ensureHexColor(-999, -999, -999));
		self::assertSame('#00FFFF', TPropertyValue::ensureHexColor(-5,   255,  255));
		self::assertSame('#FF00FF', TPropertyValue::ensureHexColor(255,  -5,   255));
		self::assertSame('#FFFF00', TPropertyValue::ensureHexColor(255,  255,  -5));
		// Exact boundary: 0 must not be clamped.
		self::assertSame('#000000', TPropertyValue::ensureHexColor(0, 0, 0));
	}

	// ════════════════════════════════════════════════════════════════════════
	// ensureBoolean — whitespace and non-numeric string edge cases
	// ════════════════════════════════════════════════════════════════════════

	/**
	 * Whitespace-only strings are not numeric and do not equal 'true', so they
	 * must return false.
	 */
	public function testEnsureBoolean_whitespaceOnlyString_returnsFalse(): void
	{
		self::assertFalse(TPropertyValue::ensureBoolean('   '));
		self::assertFalse(TPropertyValue::ensureBoolean("\t"));
		self::assertFalse(TPropertyValue::ensureBoolean("\n"));
		self::assertFalse(TPropertyValue::ensureBoolean(" \t\n "));
	}

	/**
	 * Non-numeric, non-'true' strings must return false regardless of content.
	 */
	public function testEnsureBoolean_nonNumericNonTrueString_returnsFalse(): void
	{
		self::assertFalse(TPropertyValue::ensureBoolean('abc'));
		self::assertFalse(TPropertyValue::ensureBoolean('yes'));
		self::assertFalse(TPropertyValue::ensureBoolean('on'));
		self::assertFalse(TPropertyValue::ensureBoolean('enabled'));
		// 'false' / 'FALSE' — not 'true', not numeric → false.
		self::assertFalse(TPropertyValue::ensureBoolean('false'));
		self::assertFalse(TPropertyValue::ensureBoolean('FALSE'));
		self::assertFalse(TPropertyValue::ensureBoolean('False'));
	}

	// ════════════════════════════════════════════════════════════════════════
	// ensureInteger — non-numeric string input
	// ════════════════════════════════════════════════════════════════════════

	/**
	 * PHP's (int) cast returns 0 for strings that do not begin with a digit or sign.
	 */
	public function testEnsureInteger_nonNumericString_returnsZero(): void
	{
		self::assertSame(0, TPropertyValue::ensureInteger('abc'));
		self::assertSame(0, TPropertyValue::ensureInteger('hello world'));
		self::assertSame(0, TPropertyValue::ensureInteger(''));
		self::assertSame(0, TPropertyValue::ensureInteger('   '));
	}

	/**
	 * Strings with leading digits are parsed up to the first non-digit by (int).
	 */
	public function testEnsureInteger_leadingDigitString_parsesLeadingDigits(): void
	{
		self::assertSame(42, TPropertyValue::ensureInteger('42abc'));
		self::assertSame(1,  TPropertyValue::ensureInteger('1.9'));   // stops at '.'
	}

	// ════════════════════════════════════════════════════════════════════════
	// ensureFloat — non-numeric string input
	// ════════════════════════════════════════════════════════════════════════

	/**
	 * PHP's (float) cast returns 0.0 for strings that do not represent a number.
	 */
	public function testEnsureFloat_nonNumericString_returnsZero(): void
	{
		self::assertSame(0.0, TPropertyValue::ensureFloat('abc'));
		self::assertSame(0.0, TPropertyValue::ensureFloat('hello world'));
		self::assertSame(0.0, TPropertyValue::ensureFloat(''));
		self::assertSame(0.0, TPropertyValue::ensureFloat('   '));
	}

	/**
	 * Strings with leading digits are parsed up to the first non-numeric character.
	 */
	public function testEnsureFloat_leadingDigitString_parsesLeadingDigits(): void
	{
		self::assertSame(42.0, TPropertyValue::ensureFloat('42abc'));
		self::assertSame(1.9,  TPropertyValue::ensureFloat('1.9x'));
	}

	// ════════════════════════════════════════════════════════════════════════
	// ensureArray — trailing comma in array literal
	// ════════════════════════════════════════════════════════════════════════

	/**
	 * PHP allows a trailing comma inside an array literal since PHP 5.0.  The
	 * parser must accept expressions like '(1, 2, 3,)' without error.
	 */
	public function testEnsureArray_parseBranch_trailingCommaAccepted(): void
	{
		self::assertSame([1, 2, 3],       TPropertyValue::ensureArray('(1, 2, 3,)'));
		self::assertSame(['a', 'b'],       TPropertyValue::ensureArray('("a", "b",)'));
		self::assertSame(['x' => 1],       TPropertyValue::ensureArray('("x" => 1,)'));
	}

	// ════════════════════════════════════════════════════════════════════════
	// ensureEnum — non-existent class name propagates ReflectionException
	// ════════════════════════════════════════════════════════════════════════

	/**
	 * When the class-name form of ensureEnum() receives a name that does not
	 * exist, TComponentReflection::getReflectionClassByType() returns null (without
	 * caching the failure, so lazy-loaded classes can be retried), and ensureEnum()
	 * throws TInvalidDataValueException with an empty constants list.
	 */
	public function testEnsureEnum_nonExistentClass_throwsInvalidDataValueException(): void
	{
		$this->expectException(\Prado\Exceptions\TInvalidDataValueException::class);
		TPropertyValue::ensureEnum('Foo', 'TPropertyValueNonExistentClass99999XYZ');
	}

	// ════════════════════════════════════════════════════════════════════════
	// ICoercible — interface contract + fixture sanity checks
	// ════════════════════════════════════════════════════════════════════════

	public function testICoercible_interfaceExists(): void
	{
		self::assertTrue(interface_exists(ICoercible::class));
		self::assertTrue(method_exists(ICoercible::class, 'coerceFromValue'));
	}

	public function testICoercible_fixtures_implementInterface(): void
	{
		self::assertTrue(is_a(TPropertyValueTestPoint::class,              ICoercible::class, true));
		self::assertTrue(is_a(TPropertyValueTestRange::class,              ICoercible::class, true));
		self::assertTrue(is_a(TPropertyValueTestDecliner::class,           ICoercible::class, true));
		self::assertTrue(is_a(TPropertyValueTestPickyCoercer::class,       ICoercible::class, true));
		self::assertTrue(is_a(TPropertyValueTestCoercibleEnum::class,      ICoercible::class, true));
		self::assertTrue(is_a(TPropertyValueTestCoercibleDirection::class, ICoercible::class, true));
		self::assertTrue(is_a(TPropertyValueTestNeverCalled::class,        ICoercible::class, true));
		// Composite fixture also implements IEnumerable.
		self::assertTrue(is_a(TPropertyValueTestCoercibleDirection::class, IEnumerable::class, true));
	}

	// ════════════════════════════════════════════════════════════════════════
	// ICoercible — single-class path (coerceToType → _coerceToClass)
	// ════════════════════════════════════════════════════════════════════════

	public function testICoercible_singleClass_stringInputConstructsInstance(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestPoint $x) => $x);
		$p = TPropertyValue::coerceToType('3,4', $t);
		self::assertInstanceOf(TPropertyValueTestPoint::class, $p);
		self::assertSame(3, $p->x);
		self::assertSame(4, $p->y);
	}

	public function testICoercible_singleClass_arrayInputConstructsInstance(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestPoint $x) => $x);
		$p = TPropertyValue::coerceToType(['x' => -5, 'y' => 12], $t);
		self::assertInstanceOf(TPropertyValueTestPoint::class, $p);
		self::assertSame(-5, $p->x);
		self::assertSame(12, $p->y);
	}

	public function testICoercible_singleClass_instancePassesThrough(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestPoint $x) => $x);
		$existing = new TPropertyValueTestPoint(7, 8);
		self::assertSame($existing, TPropertyValue::coerceToType($existing, $t));
	}

	public function testICoercible_singleClass_identityShortCircuitSkipsFactory(): void
	{
		// The TPropertyValueTestNeverCalled fixture's coerceFromValue() throws
		// LogicException if invoked.  An instance input must short-circuit on
		// the call site's instanceof check and return without entering the
		// factory at all — assertSame proves it (no exception thrown).
		$t = $this->typeOf(fn(TPropertyValueTestNeverCalled $x) => $x);
		$existing = new TPropertyValueTestNeverCalled();
		self::assertSame($existing, TPropertyValue::coerceToType($existing, $t));
	}

	public function testICoercible_singleClass_decline_returnsValueUnchanged(): void
	{
		// Decliner always returns null → _coerceToClass returns $value unchanged
		// → TypeError surfaces at the setter boundary (we only assert pass-through).
		$t = $this->typeOf(fn(TPropertyValueTestDecliner $x) => $x);
		self::assertSame('anything',    TPropertyValue::coerceToType('anything', $t));
		self::assertSame(['k' => 'v'],  TPropertyValue::coerceToType(['k' => 'v'], $t));
		self::assertSame(42,            TPropertyValue::coerceToType(42, $t));
	}

	public function testICoercible_singleClass_throwsOnInvalidInput(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestPickyCoercer $x) => $x);
		// In-range input constructs.
		$ok = TPropertyValue::coerceToType('50', $t);
		self::assertInstanceOf(TPropertyValueTestPickyCoercer::class, $ok);
		self::assertSame(50, $ok->value);
		// Out-of-range throws.
		$this->expectException(TInvalidDataValueException::class);
		TPropertyValue::coerceToType(150, $t);
	}

	// ────────────────────────────────────────────────────────────────────────
	// ICoercible composites: BackedEnum + ICoercible / IEnumerable + ICoercible
	// ────────────────────────────────────────────────────────────────────────

	public function testICoercible_backedEnumComposite_coercerWinsForRecognizedInput(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestCoercibleEnum $x) => $x);
		// '#FF0000' is recognized by ICoercible (enum names would never match it).
		self::assertSame(TPropertyValueTestCoercibleEnum::Red, TPropertyValue::coerceToType('#FF0000', $t));
		self::assertSame(TPropertyValueTestCoercibleEnum::Blue, TPropertyValue::coerceToType('#0000FF', $t));
	}

	public function testICoercible_backedEnumComposite_declineFallsToEnumStep(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestCoercibleEnum $x) => $x);
		// Name lookup picks up casing variants of the enum names.
		self::assertSame(TPropertyValueTestCoercibleEnum::Red,   TPropertyValue::coerceToType('Red',   $t));
		self::assertSame(TPropertyValueTestCoercibleEnum::Green, TPropertyValue::coerceToType('GREEN', $t));
		// BackedEnum's own tryFrom() path also resolves the backing value.
		self::assertSame(TPropertyValueTestCoercibleEnum::Red,   TPropertyValue::coerceToType('red',   $t));
	}

	public function testICoercible_iEnumerableComposite_coercerWinsForRecognizedInput(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestCoercibleDirection $x) => $x);
		// 'coerced:foo' is recognized only by ICoercible; no enum name matches.
		$got = TPropertyValue::coerceToType('coerced:foo', $t);
		self::assertInstanceOf(TPropertyValueTestCoercibleDirection::class, $got);
		self::assertSame('foo', $got->tag);
	}

	public function testICoercible_iEnumerableComposite_declineFallsToNameLookup(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestCoercibleDirection $x) => $x);
		// Enum-step name lookup returns the canonical constant NAME ('North'),
		// because that's IEnumerable's documented validate-name semantic.
		self::assertSame('North', TPropertyValue::coerceToType('north', $t));
		self::assertSame('East',  TPropertyValue::coerceToType('EAST',  $t));
	}

	// ════════════════════════════════════════════════════════════════════════
	// ICoercible — union path (_coerceUnionType, step 2)
	// ════════════════════════════════════════════════════════════════════════

	public function testICoercibleUnion_coercerClaimsString(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestPoint|string $x) => $x);
		$p = TPropertyValue::coerceToType('1,2', $t);
		self::assertInstanceOf(TPropertyValueTestPoint::class, $p);
		self::assertSame(1, $p->x);
		self::assertSame(2, $p->y);
	}

	public function testICoercibleUnion_declineFallsToString(): void
	{
		// "not a point" doesn't match TPropertyValueTestPoint's regex → null →
		// fall through; with `string` in the union, the value reaches step 5.
		$t = $this->typeOf(fn(TPropertyValueTestPoint|string $x) => $x);
		self::assertSame('not a point', TPropertyValue::coerceToType('not a point', $t));
	}

	public function testICoercibleUnion_nullInUnion_step1WinsForNull(): void
	{
		// Step 1 short-circuits null/empty-string before ICoercible runs at all.
		$t = $this->typeOf(fn(TPropertyValueTestPoint|null $x) => $x);
		self::assertNull(TPropertyValue::coerceToType(null, $t));
		self::assertNull(TPropertyValue::coerceToType('',   $t));
		// A real point string still coerces (single non-null short-circuit
		// delegates to _coerceToClass → ICoercible).
		$p = TPropertyValue::coerceToType('9,8', $t);
		self::assertInstanceOf(TPropertyValueTestPoint::class, $p);
		self::assertSame(9, $p->x);
	}

	public function testICoercibleUnion_arrayInput(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestPoint|string $x) => $x);
		$p = TPropertyValue::coerceToType(['x' => 11, 'y' => 22], $t);
		self::assertInstanceOf(TPropertyValueTestPoint::class, $p);
		self::assertSame(11, $p->x);
		self::assertSame(22, $p->y);
	}

	public function testICoercibleUnion_intInputDeclined_widensToInt(): void
	{
		// 42 is not a string and not a point-shaped array → Point declines
		// → falls through to native-int handling (step 6, since `string` not present).
		$t = $this->typeOf(fn(TPropertyValueTestPoint|int $x) => $x);
		self::assertSame(42, TPropertyValue::coerceToType(42, $t));
	}

	public function testICoercibleUnion_boolInputDeclined_widensToBool(): void
	{
		// Point declines bool → step 6 native match returns the bool.
		$t = $this->typeOf(fn(TPropertyValueTestPoint|bool $x) => $x);
		self::assertTrue(TPropertyValue::coerceToType(true, $t));
		self::assertFalse(TPropertyValue::coerceToType(false, $t));
	}

	public function testICoercibleUnion_existingInstance_passesThroughStep2(): void
	{
		// Step 2 (ICoercible) holds the identity pass-through check itself
		// — `$value instanceof $member` is consulted before coerceFromValue
		// is invoked.  Step 4 (native object short-circuit) would also accept
		// the instance, but step 2 always claims it first.
		$existing = new TPropertyValueTestPoint(5, 6);
		$t = $this->typeOf(fn(TPropertyValueTestPoint|string $x) => $x);
		self::assertSame($existing, TPropertyValue::coerceToType($existing, $t));
	}

	public function testICoercibleUnion_identityShortCircuitSkipsFactory(): void
	{
		// Union with a throwing fixture proves the call-site short-circuit:
		// an instance input must not enter coerceFromValue() even on the union path.
		$existing = new TPropertyValueTestNeverCalled();
		$t = $this->typeOf(fn(TPropertyValueTestNeverCalled|string $x) => $x);
		self::assertSame($existing, TPropertyValue::coerceToType($existing, $t));
	}

	// ────────────────────────────────────────────────────────────────────────
	// ICoercible union — multiple coercibles, declaration-order arbitration
	// ────────────────────────────────────────────────────────────────────────

	public function testICoercibleUnion_multipleCoercers_firstClaimsWins(): void
	{
		// Both coercers could claim '1,2' (Point as x,y; Range only matches lo-hi
		// with a hyphen, so this input is unambiguous).  Point wins by being
		// the only one that recognizes the comma form.
		$t = $this->typeOf(fn(TPropertyValueTestPoint|TPropertyValueTestRange $x) => $x);
		$got = TPropertyValue::coerceToType('1,2', $t);
		self::assertInstanceOf(TPropertyValueTestPoint::class, $got);
		// '1-2' is recognized only by Range.
		$got = TPropertyValue::coerceToType('1-2', $t);
		self::assertInstanceOf(TPropertyValueTestRange::class, $got);
	}

	public function testICoercibleUnion_multipleCoercers_firstDeclines_secondWins(): void
	{
		// Decliner always returns null; Point claims '1,2'.  Verifies the
		// fallthrough across union members in declaration order.
		$t = $this->typeOf(fn(TPropertyValueTestDecliner|TPropertyValueTestPoint $x) => $x);
		$got = TPropertyValue::coerceToType('1,2', $t);
		self::assertInstanceOf(TPropertyValueTestPoint::class, $got);
	}

	public function testICoercibleUnion_allDecline_fallsThroughToLaterSteps(): void
	{
		// Two distinct decliners + a string member → ICoercible step yields
		// nothing, and `string` in the union picks up the value at step 5.
		$t = $this->typeOf(fn(TPropertyValueTestDecliner|TPropertyValueTestDecliner2|string $x) => $x);
		self::assertSame('untouched', TPropertyValue::coerceToType('untouched', $t));
	}

	// ────────────────────────────────────────────────────────────────────────
	// ICoercible union — interaction with the enum-validation step (step 3)
	// ────────────────────────────────────────────────────────────────────────

	public function testICoercibleUnion_coercibleWinsOverEnumStep(): void
	{
		// '#FF0000' is recognized only by ICoercible; this input would never
		// satisfy the enum-step name lookup for either TPropertyValueTestColor
		// or TPropertyValueTestStatus.
		$t = $this->typeOf(
			fn(TPropertyValueTestCoercibleEnum|TPropertyValueTestStatus $x) => $x
		);
		self::assertSame(
			TPropertyValueTestCoercibleEnum::Red,
			TPropertyValue::coerceToType('#FF0000', $t)
		);
	}

	public function testICoercibleUnion_coercibleDeclines_enumStepWins(): void
	{
		// 'Pending' is not a hex code (ICoercible declines) and matches the
		// non-ICoercible UnitEnum's case-name.  Enum step (step 3) wins.
		$t = $this->typeOf(
			fn(TPropertyValueTestCoercibleEnum|TPropertyValueTestStatus $x) => $x
		);
		self::assertSame(
			TPropertyValueTestStatus::Pending,
			TPropertyValue::coerceToType('Pending', $t)
		);
	}

	public function testICoercibleUnion_coercibleIEnumerableComposite_overridesNameMatch(): void
	{
		// The composite's ICoercible accepts 'coerced:tag'; the input would
		// otherwise miss every name-based enum step.
		$t = $this->typeOf(
			fn(TPropertyValueTestCoercibleDirection|TPropertyValueTestSeason $x) => $x
		);
		$got = TPropertyValue::coerceToType('coerced:winter', $t);
		self::assertInstanceOf(TPropertyValueTestCoercibleDirection::class, $got);
		self::assertSame('winter', $got->tag);
	}

	public function testICoercibleUnion_coercibleIEnumerableComposite_declineFallsToOtherEnumName(): void
	{
		// 'Spring' is declined by the coercible (no 'coerced:' prefix) and
		// matches a constant name on the second IEnumerable union member.
		$t = $this->typeOf(
			fn(TPropertyValueTestCoercibleDirection|TPropertyValueTestSeason $x) => $x
		);
		self::assertSame('Spring', TPropertyValue::coerceToType('Spring', $t));
	}

	// ────────────────────────────────────────────────────────────────────────
	// ICoercible union — throw policy
	// ────────────────────────────────────────────────────────────────────────

	public function testICoercibleUnion_throwsPropagateThroughChain(): void
	{
		// A shape-recognized but out-of-range value throws and does NOT
		// fall through to subsequent union members.  This is the documented
		// contract: decline ≠ broken; broken is a thrown exception.
		$t = $this->typeOf(fn(TPropertyValueTestPickyCoercer|string $x) => $x);
		$this->expectException(TInvalidDataValueException::class);
		TPropertyValue::coerceToType(999, $t);
	}

	public function testICoercibleUnion_inRangeValueCoercesNormally(): void
	{
		$t = $this->typeOf(fn(TPropertyValueTestPickyCoercer|string $x) => $x);
		$got = TPropertyValue::coerceToType(50, $t);
		self::assertInstanceOf(TPropertyValueTestPickyCoercer::class, $got);
		self::assertSame(50, $got->value);
	}

	public function testICoercibleUnion_pickyDeclinesNonInt_fallsToString(): void
	{
		// 'abc' isn't int-shaped → picky declines → string step takes it.
		$t = $this->typeOf(fn(TPropertyValueTestPickyCoercer|string $x) => $x);
		self::assertSame('abc', TPropertyValue::coerceToType('abc', $t));
	}

	// ════════════════════════════════════════════════════════════════════════
	// ICoercible — order verification with no other typed members
	// ════════════════════════════════════════════════════════════════════════

	public function testICoercibleUnion_twoCoerciblesPlusNull_orderingPreserved(): void
	{
		// Decliner|Point|null: Decliner declines → Point claims → instance
		// constructed.  Verifies that the null-handling step doesn't interfere
		// with reflection-order iteration through ICoercible members.
		$t = $this->typeOf(fn(TPropertyValueTestDecliner|TPropertyValueTestPoint|null $x) => $x);
		$got = TPropertyValue::coerceToType('3,3', $t);
		self::assertInstanceOf(TPropertyValueTestPoint::class, $got);
		self::assertSame(3, $got->x);
		self::assertNull(TPropertyValue::coerceToType(null, $t));
		self::assertNull(TPropertyValue::coerceToType('',   $t));
	}

	public function testICoercibleUnion_unionWithNonCoercibleClass_coercibleStepSkipsNonImplementers(): void
	{
		// Plain stdClass (not ICoercible) appears alongside Point in the union.
		// The ICoercible step iterates non-builtin members and SKIPS those that
		// don't implement the interface, then Point claims.
		$t = $this->typeOf(fn(\stdClass|TPropertyValueTestPoint $x) => $x);
		$got = TPropertyValue::coerceToType('7,7', $t);
		self::assertInstanceOf(TPropertyValueTestPoint::class, $got);
	}
}
