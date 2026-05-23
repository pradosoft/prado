<?php

use Prado\Exceptions\TInvalidDataValueException;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 */
class TPropertyValueTest extends PHPUnit\Framework\TestCase
{
	protected function setUp(): void
	{
	}


	protected function tearDown(): void
	{
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
	
	public function testEnsureArray()
	{
		self::assertEquals([], TPropertyValue::ensureArray(null));
		self::assertEquals([], TPropertyValue::ensureArray(''));
		self::assertEquals([], TPropertyValue::ensureArray([]));
		self::assertEquals([0 => 0], TPropertyValue::ensureArray(0));
		self::assertEquals([0 => 1], TPropertyValue::ensureArray(1));
		self::assertEquals(['value'], TPropertyValue::ensureArray('value'));
		self::assertEquals(['value'], TPropertyValue::ensureArray(' value '));
		self::assertEquals([], TPropertyValue::ensureArray('()'));
		self::assertEquals(['my', 'prop'], TPropertyValue::ensureArray('("my", "prop")'));
	}

	// ── ensureArray: eval branch — string starting and ending with parentheses ──

	public function testEnsureArrayEvalEmptyParens()
	{
		// '()' → eval('return array();') → []
		self::assertSame([], TPropertyValue::ensureArray('()'));
	}

	public function testEnsureArrayEvalParensWithWhitespace()
	{
		// '( )' → eval('return array( );') → []
		self::assertSame([], TPropertyValue::ensureArray('( )'));

		// '(   )' → same
		self::assertSame([], TPropertyValue::ensureArray('(   )'));
	}

	public function testEnsureArrayEvalOuterWhitespaceTrimmed()
	{
		// Leading/trailing whitespace on the whole string is trimmed before the
		// paren check, so '  ("a", "b")  ' hits the eval branch.
		self::assertSame(['a', 'b'], TPropertyValue::ensureArray('  ("a", "b")  '));
	}

	public function testEnsureArrayEvalDoubleQuotedStrings()
	{
		// Double-quoted string elements
		self::assertSame(['my', 'prop'], TPropertyValue::ensureArray('("my", "prop")'));
		self::assertSame(['a', 'b', 'c'], TPropertyValue::ensureArray('("a", "b", "c")'));
	}

	public function testEnsureArrayEvalSingleQuotedStrings()
	{
		// Single-quoted string elements
		self::assertSame(['my', 'prop'], TPropertyValue::ensureArray("('my', 'prop')"));
		self::assertSame(['hello', 'world'], TPropertyValue::ensureArray("('hello', 'world')"));
	}

	public function testEnsureArrayEvalIntegerElements()
	{
		// Integer elements — eval parses them as PHP integers
		self::assertSame([1, 2, 3], TPropertyValue::ensureArray('(1, 2, 3)'));
		self::assertSame([0, 100, -5], TPropertyValue::ensureArray('(0, 100, -5)'));
	}

	public function testEnsureArrayEvalFloatElements()
	{
		// Float elements
		self::assertSame([1.5, 2.5], TPropertyValue::ensureArray('(1.5, 2.5)'));
		self::assertSame([-0.5, 3.14], TPropertyValue::ensureArray('(-0.5, 3.14)'));
	}

	public function testEnsureArrayEvalMixedTypes()
	{
		// Mixed PHP types in one expression
		self::assertSame([1, 'two', 3.0], TPropertyValue::ensureArray('(1, "two", 3.0)'));
	}

	public function testEnsureArrayEvalBooleanAndNull()
	{
		// PHP keywords true, false, null parsed by eval
		self::assertSame([true, false, null], TPropertyValue::ensureArray('(true, false, null)'));
	}

	public function testEnsureArrayEvalAssociativeStringKeys()
	{
		// Associative array with string keys
		self::assertSame(['x' => 1, 'y' => 2], TPropertyValue::ensureArray('("x" => 1, "y" => 2)'));
	}

	public function testEnsureArrayEvalAssociativeIntegerKeys()
	{
		// Explicit integer keys
		self::assertSame([5 => 'a', 10 => 'b'], TPropertyValue::ensureArray('(5 => "a", 10 => "b")'));
	}

	public function testEnsureArrayEvalSingleElement()
	{
		// Single element
		self::assertSame(['only'], TPropertyValue::ensureArray('("only")'));
		self::assertSame([42], TPropertyValue::ensureArray('(42)'));
	}

	public function testEnsureArrayEvalNestedArrays()
	{
		// Nested arrays using the array() constructor inside the expression
		self::assertSame([[1, 2], [3, 4]], TPropertyValue::ensureArray('(array(1, 2), array(3, 4))'));
		self::assertSame([['a', 'b'], ['c']], TPropertyValue::ensureArray('(array("a", "b"), array("c"))'));
	}

	public function testEnsureArrayEvalEmptyStringElement()
	{
		// A single empty string element
		self::assertSame([''], TPropertyValue::ensureArray('("")'));
	}

	public function testEnsureArrayEvalComplexExpression()
	{
		// A realistic config-style expression with integers, strings, and nested array
		self::assertSame(
			[11, 'abc', 'xyz', [12, 'bcd', 'wxy', '']],
			TPropertyValue::ensureArray('(11, "abc", "xyz", array(12, "bcd", "wxy", ""))')
		);
	}

	// ── ensureArray: non-eval string branch — plain strings ──────────────────

	public function testEnsureArrayStringNotParens()
	{
		// A plain string with no surrounding parens is wrapped in an array
		self::assertSame(['value'], TPropertyValue::ensureArray('value'));
		self::assertSame(['hello world'], TPropertyValue::ensureArray('hello world'));
	}

	public function testEnsureArrayStringWithLeadingParenOnly()
	{
		// Starts with '(' but does NOT end with ')' → plain string, not eval
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
		// Single-char strings: '(' or ')' have len=1 < 2, so do NOT trigger eval
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
		self::assertEquals($obj, TPropertyValue::ensureObject($obj));
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

		// Single-element array form: value is in the array
		self::assertEquals('only', TPropertyValue::ensureEnum('only', ['only']));

		// Variadic form with many values
		self::assertEquals('z', TPropertyValue::ensureEnum('z', 'a', 'b', 'c', 'z'));

		// Variadic form throws when value absent
		try {
			TPropertyValue::ensureEnum('missing', 'a', 'b', 'c');
			self::fail('Expected TInvalidDataValueException for variadic miss');
		} catch (TInvalidDataValueException $e) {
		}

		// ── Class-constant form — returns the value as-is when found ─────────

		// Case-sensitive: 'debug' (lowercase) is NOT a TApplicationMode constant
		try {
			TPropertyValue::ensureEnum('debug', \Prado\TApplicationMode::class);
			self::fail('Expected TInvalidDataValueException for wrong case');
		} catch (TInvalidDataValueException $e) {
		}

		// Exception message names valid constants
		try {
			TPropertyValue::ensureEnum('invalid', \Prado\TApplicationMode::class);
			self::fail('Expected TInvalidDataValueException');
		} catch (TInvalidDataValueException $e) {
			self::assertStringContainsString('Off', $e->getMessage());
		}

		// ── ReflectionClass cache: repeated calls do not throw ────────────────
		// The static $types cache is populated on first call; second call reuses it.
		self::assertEquals('Off', TPropertyValue::ensureEnum('Off', \Prado\TApplicationMode::class));
		self::assertEquals('Debug', TPropertyValue::ensureEnum('Debug', \Prado\TApplicationMode::class));
	}
	
	public function testEnsureNullIfEmpty()
	{
		self::assertNull(TPropertyValue::ensureNullIfEmpty(''));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(""));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(null));
		self::assertNull(TPropertyValue::ensureNullIfEmpty([]));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(false));
		self::assertNull(TPropertyValue::ensureNullIfEmpty(null));
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
}
