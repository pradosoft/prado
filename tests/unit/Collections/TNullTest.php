<?php
/**
 * TNullTest class file.
 *
 * @author  Brad Anderson <belisoful@icloud.com>
 * @link    https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Tests\Collections;

use Prado\Exceptions\TInvalidOperationException;
use Prado\ISingleton;
use Prado\Collections\TNull;
use PHPUnit\Framework\TestCase;

/**
 * TNullTest
 *
 * Comprehensive unit tests for {@see TNull}, covering every public method,
 * every interface contract, and every significant code path including
 * edge cases.
 *
 * @author  Brad Anderson <belisoful@icloud.com>
 * @since   4.3.3
 */
class TNullTest extends TestCase
{
	// -----------------------------------------------------------------------
	// Fixture helpers
	// -----------------------------------------------------------------------

	/**
	 * Resets the private static singleton storage to {@see null} via reflection,
	 * giving every test a clean starting state.
	 */
	private function resetSingleton(): void
	{
		$prop = new \ReflectionProperty(TNull::class, 'null');
		$prop->setAccessible(true);
		$prop->setValue(null, null);
	}

	/**
	 * Reset before each test so singleton creation tests are fully independent.
	 */
	protected function setUp(): void
	{
		$this->resetSingleton();
	}

	/**
	 * Reset after each test so any instance created does not leak into tests
	 * that run later (important for {@see singleton(false)} tests).
	 */
	protected function tearDown(): void
	{
		$this->resetSingleton();
	}

	// -----------------------------------------------------------------------
	// Interface implementation
	// -----------------------------------------------------------------------

	/** TNull must implement ISingleton. */
	public function testImplementsISingleton(): void
	{
		$this->assertInstanceOf(ISingleton::class, TNull::null());
	}

	/** TNull must implement JsonSerializable. */
	public function testImplementsJsonSerializable(): void
	{
		$this->assertInstanceOf(\JsonSerializable::class, TNull::null());
	}

	/** TNull must implement Stringable. */
	public function testImplementsStringable(): void
	{
		$this->assertInstanceOf(\Stringable::class, TNull::null());
	}

	// -----------------------------------------------------------------------
	// Singleton creation — singleton()
	// -----------------------------------------------------------------------

	/** singleton(true) creates and returns an instance when none exists. */
	public function testSingletonCreateReturnsInstance(): void
	{
		$this->assertInstanceOf(TNull::class, TNull::singleton(true));
	}

	/** singleton() with default argument behaves identically to singleton(true). */
	public function testSingletonDefaultArgumentCreates(): void
	{
		$this->assertInstanceOf(TNull::class, TNull::singleton());
	}

	/** singleton(false) returns null when no instance has been created yet. */
	public function testSingletonFalseReturnsNullBeforeCreation(): void
	{
		$this->assertInstanceOf(TNull::class, TNull::singleton(false));
	}

	/** singleton(false) returns the existing instance after one has been created. */
	public function testSingletonFalseReturnsInstanceAfterCreation(): void
	{
		$first = TNull::singleton(true);
		$this->assertSame($first, TNull::singleton(false));
	}

	/** Repeated singleton() calls return the identical object (strict identity). */
	public function testSingletonReturnsSameInstanceOnRepeatedCalls(): void
	{
		$a = TNull::singleton();
		$b = TNull::singleton();
		$c = TNull::singleton();
		$this->assertSame($a, $b);
		$this->assertSame($b, $c);
	}

	// -----------------------------------------------------------------------
	// Singleton creation — null()
	// -----------------------------------------------------------------------

	/** null() creates and returns a TNull instance. */
	public function testNullFactoryReturnsInstance(): void
	{
		$this->assertInstanceOf(TNull::class, TNull::null());
	}

	/** null() and singleton() return the exact same object. */
	public function testNullAndSingletonReturnSameObject(): void
	{
		$this->assertSame(TNull::null(), TNull::singleton());
	}

	/** Repeated null() calls return the identical object. */
	public function testNullReturnsSameInstanceOnRepeatedCalls(): void
	{
		$this->assertSame(TNull::null(), TNull::null());
	}

	/** null() and singleton() interleaved always return the same instance. */
	public function testNullAndSingletonInterleaved(): void
	{
		$a = TNull::null();
		$b = TNull::singleton();
		$c = TNull::null();
		$d = TNull::singleton(true);
		$this->assertSame($a, $b);
		$this->assertSame($b, $c);
		$this->assertSame($c, $d);
	}

	// -----------------------------------------------------------------------
	// Bootstrap initialisation — init()
	// -----------------------------------------------------------------------

	/** init() returns void — it has no return value. */
	public function testInitReturnsVoid(): void
	{
		$result = TNull::init();
		$this->assertNull($result);
	}

	/** After init(), singleton(false) returns an instance — the singleton was created. */
	public function testInitCreatesSingleton(): void
	{
		TNull::init();
		$this->assertInstanceOf(TNull::class, TNull::singleton(false));
	}

	/** After init(), null() returns the instance that was created. */
	public function testInitAndNullReturnSameInstance(): void
	{
		TNull::init();
		$n = TNull::null();
		$this->assertInstanceOf(TNull::class, $n);
		// Calling init() again must not disturb the existing singleton.
		TNull::init();
		$this->assertSame($n, TNull::null());
	}

	/** init() before null() — null() returns the singleton init() created. */
	public function testNullAfterInitReturnsSingleton(): void
	{
		TNull::init();
		$this->assertInstanceOf(TNull::class, TNull::null());
	}

	/** init() after null() — the singleton null() created is unchanged. */
	public function testInitAfterNullDoesNotReplaceInstance(): void
	{
		$before = TNull::null();
		TNull::init();
		$this->assertSame($before, TNull::null());
	}

	/** Repeated init() calls are idempotent — the singleton is created once only. */
	public function testInitIsIdempotent(): void
	{
		TNull::init();
		TNull::init();
		TNull::init();
		// Only one instance should exist; singleton(false) must return it.
		$n = TNull::singleton(false);
		$this->assertInstanceOf(TNull::class, $n);
		$this->assertSame($n, TNull::null());
	}

	/** init() interleaved with null() and singleton() never creates a second instance. */
	public function testInitInterleavedWithOtherFactories(): void
	{
		TNull::init();
		$a = TNull::singleton();
		TNull::init();
		$b = TNull::null();
		TNull::init();
		$this->assertSame($a, $b);
	}

	// -----------------------------------------------------------------------
	// Singleton integrity — __clone()
	// -----------------------------------------------------------------------

	/** Cloning the singleton must throw TInvalidOperationException. */
	public function testCloneThrowsException(): void
	{
		$this->expectException(TInvalidOperationException::class);
		clone TNull::null();
	}

	// -----------------------------------------------------------------------
	// String representation — __toString()
	// -----------------------------------------------------------------------

	/** __toString() returns an empty string. */
	public function testToStringReturnsEmptyString(): void
	{
		$this->assertSame('', (string) TNull::null());
	}

	/** String cast of TNull mirrors the string cast of PHP null. */
	public function testToStringMatchesPhpNullCast(): void
	{
		$this->assertSame((string) null, (string) TNull::null());
	}

	/** TNull can be interpolated inside a double-quoted string. */
	public function testStringInterpolation(): void
	{
		$n = TNull::null();
		$this->assertSame('prefixsuffix', "prefix{$n}suffix");
	}

	/** Concatenating TNull on the right yields the left operand unchanged. */
	public function testStringConcatenationLeft(): void
	{
		$this->assertSame('hello', 'hello' . TNull::null());
	}

	/** Concatenating TNull on the left yields the right operand unchanged. */
	public function testStringConcatenationRight(): void
	{
		$this->assertSame('hello', TNull::null() . 'hello');
	}

	// -----------------------------------------------------------------------
	// Serialization — __sleep() / serialize() / unserialize()
	// -----------------------------------------------------------------------

	/** __sleep() returns an empty array (TNull has no serializable state). */
	public function testSleepReturnsEmptyArray(): void
	{
		$this->assertSame([], TNull::null()->__sleep());
	}

	/** serialize() produces a non-empty string without error. */
	public function testSerializeProducesString(): void
	{
		$serialized = serialize(TNull::null());
		$this->assertIsString($serialized);
		$this->assertNotEmpty($serialized);
	}

	/** unserialize() reconstructs a valid TNull instance. */
	public function testUnserializeProducesTNullInstance(): void
	{
		$this->assertInstanceOf(TNull::class, unserialize(serialize(TNull::null())));
	}

	/**
	 * A deserialized TNull is a valid instance but is not the canonical
	 * singleton — callers requiring the canonical reference must call
	 * null() or singleton() after deserialization.
	 */
	public function testUnserializedInstanceIsNotCanonicalSingleton(): void
	{
		$original = TNull::null();
		$restored = unserialize(serialize($original));
		$this->assertInstanceOf(TNull::class, $restored);
		$this->assertNotSame($original, $restored);
	}

	/** A deserialized TNull still converts to an empty string. */
	public function testUnserializedInstanceToString(): void
	{
		$this->assertSame('', (string) unserialize(serialize(TNull::null())));
	}

	/** A deserialized TNull still encodes to JSON null. */
	public function testUnserializedInstanceJsonEncode(): void
	{
		$this->assertSame('null', json_encode(unserialize(serialize(TNull::null()))));
	}

	/** is_null() recognises a deserialized TNull as a null value. */
	public function testIsNullRecognisesDeserializedInstance(): void
	{
		$this->assertTrue(TNull::is_null(unserialize(serialize(TNull::null()))));
	}

	/** empty() recognises a deserialized TNull as an empty value. */
	public function testEmptyRecognisesDeserializedInstance(): void
	{
		$this->assertTrue(TNull::empty(unserialize(serialize(TNull::null()))));
	}

	// -----------------------------------------------------------------------
	// JSON encoding — jsonSerialize() / json_encode()
	// -----------------------------------------------------------------------

	/** jsonSerialize() returns PHP null. */
	public function testJsonSerializeReturnsNull(): void
	{
		$this->assertNull(TNull::null()->jsonSerialize());
	}

	/** json_encode() of a TNull instance produces the string "null". */
	public function testJsonEncodeScalar(): void
	{
		$this->assertSame('null', json_encode(TNull::null()));
	}

	/** TNull in an associative array encodes to {"key":null}. */
	public function testJsonEncodeInAssocArray(): void
	{
		$this->assertSame('{"key":null}', json_encode(['key' => TNull::null()]));
	}

	/** TNull alongside other values encodes correctly. */
	public function testJsonEncodeMixedArray(): void
	{
		$decoded = json_decode(
			json_encode(['a' => TNull::null(), 'b' => 1, 'c' => 'hello']),
			true
		);
		$this->assertNull($decoded['a']);
		$this->assertSame(1, $decoded['b']);
		$this->assertSame('hello', $decoded['c']);
	}

	/** TNull in a nested structure encodes correctly at every depth. */
	public function testJsonEncodeNested(): void
	{
		$decoded = json_decode(
			json_encode(['outer' => ['inner' => TNull::null()]]),
			true
		);
		$this->assertNull($decoded['outer']['inner']);
	}

	/** An indexed array containing TNull instances encodes correctly. */
	public function testJsonEncodeIndexedArray(): void
	{
		$this->assertSame('[null,"hello",null]', json_encode([TNull::null(), 'hello', TNull::null()]));
	}

	// -----------------------------------------------------------------------
	// Predicates — is_null()
	// -----------------------------------------------------------------------

	/** is_null() returns true for the TNull singleton. */
	public function testIsNullWithSingleton(): void
	{
		$this->assertTrue(TNull::is_null(TNull::null()));
	}

	/** is_null() returns true for PHP null. */
	public function testIsNullWithPhpNull(): void
	{
		$this->assertTrue(TNull::is_null(null));
	}

	/** is_null() returns false for an empty string. */
	public function testIsNullWithEmptyString(): void
	{
		$this->assertFalse(TNull::is_null(''));
	}

	/** is_null() returns false for integer zero. */
	public function testIsNullWithZeroInt(): void
	{
		$this->assertFalse(TNull::is_null(0));
	}

	/** is_null() returns false for float zero. */
	public function testIsNullWithZeroFloat(): void
	{
		$this->assertFalse(TNull::is_null(0.0));
	}

	/** is_null() returns false for false. */
	public function testIsNullWithFalse(): void
	{
		$this->assertFalse(TNull::is_null(false));
	}

	/** is_null() returns false for an empty array. */
	public function testIsNullWithEmptyArray(): void
	{
		$this->assertFalse(TNull::is_null([]));
	}

	/** is_null() returns false for a non-empty string. */
	public function testIsNullWithNonEmptyString(): void
	{
		$this->assertFalse(TNull::is_null('hello'));
	}

	/** is_null() returns false for a positive integer. */
	public function testIsNullWithPositiveInt(): void
	{
		$this->assertFalse(TNull::is_null(42));
	}

	/** is_null() returns false for a generic object. */
	public function testIsNullWithObject(): void
	{
		$this->assertFalse(TNull::is_null(new \stdClass()));
	}

	/** is_null() returns false for true. */
	public function testIsNullWithTrue(): void
	{
		$this->assertFalse(TNull::is_null(true));
	}

	/** is_null() returns false for a non-empty array. */
	public function testIsNullWithNonEmptyArray(): void
	{
		$this->assertFalse(TNull::is_null([1, 2, 3]));
	}

	/**
	 * TNull::is_null() and the PHP built-in is_null() agree on PHP null and
	 * non-null scalars; they differ only in that TNull::is_null() additionally
	 * recognises TNull instances.
	 */
	public function testIsNullMirrorsPhpBuiltinForScalars(): void
	{
		foreach ([null, '', '0', 0, 0.0, false, [], 'hello', 42, true] as $v) {
			$this->assertSame(is_null($v), TNull::is_null($v),
				'TNull::is_null() should agree with is_null() for ' . var_export($v, true));
		}
	}

	/** TNull::is_null() returns true for TNull where PHP is_null() would return false. */
	public function testIsNullExtendsPhpBuiltinForTNull(): void
	{
		$n = TNull::null();
		$this->assertFalse(is_null($n));        // PHP built-in: TNull is an object, not null
		$this->assertTrue(TNull::is_null($n));  // TNull-aware: recognises the instance
	}

	// -----------------------------------------------------------------------
	// Predicates — empty()
	// -----------------------------------------------------------------------

	/** empty() returns true for the TNull singleton. */
	public function testEmptyWithSingleton(): void
	{
		$this->assertTrue(TNull::empty(TNull::null()));
	}

	/** empty() returns true for PHP null. */
	public function testEmptyWithPhpNull(): void
	{
		$this->assertTrue(TNull::empty(null));
	}

	/** empty() returns true for false. */
	public function testEmptyWithFalse(): void
	{
		$this->assertTrue(TNull::empty(false));
	}

	/** empty() returns true for integer zero. */
	public function testEmptyWithZeroInt(): void
	{
		$this->assertTrue(TNull::empty(0));
	}

	/** empty() returns true for float zero. */
	public function testEmptyWithZeroFloat(): void
	{
		$this->assertTrue(TNull::empty(0.0));
	}

	/** empty() returns true for an empty string. */
	public function testEmptyWithEmptyString(): void
	{
		$this->assertTrue(TNull::empty(''));
	}

	/** empty() returns true for the string "0". */
	public function testEmptyWithStringZero(): void
	{
		$this->assertTrue(TNull::empty('0'));
	}

	/** empty() returns true for an empty array. */
	public function testEmptyWithEmptyArray(): void
	{
		$this->assertTrue(TNull::empty([]));
	}

	/** empty() returns false for a non-empty string. */
	public function testEmptyWithNonEmptyString(): void
	{
		$this->assertFalse(TNull::empty('hello'));
	}

	/** empty() returns false for a positive integer. */
	public function testEmptyWithPositiveInt(): void
	{
		$this->assertFalse(TNull::empty(42));
	}

	/** empty() returns false for true. */
	public function testEmptyWithTrue(): void
	{
		$this->assertFalse(TNull::empty(true));
	}

	/** empty() returns false for a non-empty array. */
	public function testEmptyWithNonEmptyArray(): void
	{
		$this->assertFalse(TNull::empty([1, 2, 3]));
	}

	/** empty() returns false for a generic object (objects are truthy to empty()). */
	public function testEmptyWithObject(): void
	{
		$this->assertFalse(TNull::empty(new \stdClass()));
	}

	/**
	 * TNull::empty() and the PHP empty() construct agree on all scalars and
	 * arrays; they differ only in that TNull::empty() additionally catches
	 * TNull instances (which PHP's empty() considers truthy).
	 */
	public function testEmptyMirrorsPhpConstructForNonTNull(): void
	{
		foreach ([null, '', '0', 0, 0.0, false, [], 'hello', 42, true, [1]] as $v) {
			$this->assertSame((bool) empty($v), TNull::empty($v),
				'TNull::empty() should agree with empty() for ' . var_export($v, true));
		}
	}

	/** TNull::empty() returns true for TNull where PHP empty() returns false. */
	public function testEmptyExtendsPhpConstructForTNull(): void
	{
		$n = TNull::null();
		$this->assertFalse(empty($n));        // PHP construct: non-null object is not empty
		$this->assertTrue(TNull::empty($n));  // TNull-aware: recognises the instance
	}

	/**
	 * is_null() and empty() diverge on falsy scalars: empty() catches them,
	 * is_null() does not.
	 */
	public function testIsNullVsEmptyDivergenceOnFalsyScalars(): void
	{
		foreach (['', '0', 0, 0.0, false, []] as $falsy) {
			$label = var_export($falsy, true);
			$this->assertFalse(TNull::is_null($falsy), "is_null should be false for $label");
			$this->assertTrue(TNull::empty($falsy),    "empty should be true for $label");
		}
	}

	/** Both is_null() and empty() agree on TNull and PHP null. */
	public function testIsNullAndEmptyAgreeOnNullValues(): void
	{
		$this->assertTrue(TNull::is_null(TNull::null()));
		$this->assertTrue(TNull::empty(TNull::null()));
		$this->assertTrue(TNull::is_null(null));
		$this->assertTrue(TNull::empty(null));
	}

	/** empty() can be used as a callable via the array form. */
	public function testEmptyUsableAsCallableArrayForm(): void
	{
		$items = [TNull::null(), 'hello', null, 0, 'world', ''];
		$nonEmpty = array_filter($items, [TNull::class, 'empty']);
		// array_filter keeps items where the callback returns true, i.e. empty items.
		$this->assertCount(4, $nonEmpty); // TNull::null(), null, 0, ''
	}

	// -----------------------------------------------------------------------
	// Bridging utilities — wrap()
	// -----------------------------------------------------------------------

	/** wrap(null) returns the TNull singleton. */
	public function testWrapNullReturnsSingleton(): void
	{
		$result = TNull::wrap(null);
		$this->assertInstanceOf(TNull::class, $result);
		$this->assertSame(TNull::null(), $result);
	}

	/** wrap() with a non-null string passes through unchanged. */
	public function testWrapStringPassThrough(): void
	{
		$this->assertSame('hello', TNull::wrap('hello'));
	}

	/** wrap() with an integer passes through unchanged. */
	public function testWrapIntPassThrough(): void
	{
		$this->assertSame(42, TNull::wrap(42));
	}

	/** wrap() with false passes through unchanged (false !== null). */
	public function testWrapFalsePassThrough(): void
	{
		$this->assertFalse(TNull::wrap(false));
	}

	/** wrap() with zero passes through unchanged (0 !== null). */
	public function testWrapZeroPassThrough(): void
	{
		$this->assertSame(0, TNull::wrap(0));
	}

	/** wrap() with an empty string passes through unchanged ('' !== null). */
	public function testWrapEmptyStringPassThrough(): void
	{
		$this->assertSame('', TNull::wrap(''));
	}

	/** wrap() with an empty array passes through unchanged. */
	public function testWrapEmptyArrayPassThrough(): void
	{
		$this->assertSame([], TNull::wrap([]));
	}

	/**
	 * wrap() with a TNull instance passes it through unchanged.
	 * TNull is not PHP null, so wrap() must not double-wrap it.
	 */
	public function testWrapTNullPassesThrough(): void
	{
		$n = TNull::null();
		$this->assertSame($n, TNull::wrap($n));
	}

	/** wrap() with a generic object passes it through unchanged. */
	public function testWrapObjectPassThrough(): void
	{
		$obj = new \stdClass();
		$this->assertSame($obj, TNull::wrap($obj));
	}

	// -----------------------------------------------------------------------
	// Bridging utilities — unwrap()
	// -----------------------------------------------------------------------

	/** unwrap() with a TNull instance returns PHP null. */
	public function testUnwrapTNullReturnsNull(): void
	{
		$this->assertNull(TNull::unwrap(TNull::null()));
	}

	/**
	 * unwrap() with PHP null passes through as null.
	 * PHP null is not instanceof TNull, so it is returned unchanged.
	 */
	public function testUnwrapPhpNullPassThrough(): void
	{
		$this->assertNull(TNull::unwrap(null));
	}

	/** unwrap() with a string passes through unchanged. */
	public function testUnwrapStringPassThrough(): void
	{
		$this->assertSame('hello', TNull::unwrap('hello'));
	}

	/** unwrap() with an integer passes through unchanged. */
	public function testUnwrapIntPassThrough(): void
	{
		$this->assertSame(42, TNull::unwrap(42));
	}

	/** unwrap() with false passes through unchanged. */
	public function testUnwrapFalsePassThrough(): void
	{
		$this->assertFalse(TNull::unwrap(false));
	}

	/** unwrap() with zero passes through unchanged. */
	public function testUnwrapZeroPassThrough(): void
	{
		$this->assertSame(0, TNull::unwrap(0));
	}

	/** unwrap() with an array passes through unchanged. */
	public function testUnwrapArrayPassThrough(): void
	{
		$arr = [1, 2, 3];
		$this->assertSame($arr, TNull::unwrap($arr));
	}

	/** unwrap() with a generic object passes it through unchanged. */
	public function testUnwrapObjectPassThrough(): void
	{
		$obj = new \stdClass();
		$this->assertSame($obj, TNull::unwrap($obj));
	}

	// -----------------------------------------------------------------------
	// Bridging utilities — wrap() / unwrap() round-trips
	// -----------------------------------------------------------------------

	/** wrap(null) then unwrap() round-trips back to PHP null. */
	public function testWrapUnwrapNullRoundTrip(): void
	{
		$this->assertNull(TNull::unwrap(TNull::wrap(null)));
	}

	/** wrap() then unwrap() on non-null values returns each original unchanged. */
	public function testWrapUnwrapNonNullRoundTrip(): void
	{
		foreach (['hello', 42, false, 0, '', [], new \stdClass()] as $value) {
			$this->assertSame($value, TNull::unwrap(TNull::wrap($value)));
		}
	}

	// -----------------------------------------------------------------------
	// Callable — __invoke()
	// -----------------------------------------------------------------------

	/** A TNull instance satisfies is_callable(). */
	public function testInstanceIsCallable(): void
	{
		$this->assertTrue(is_callable(TNull::null()));
	}

	/** Invoking the instance directly returns PHP null. */
	public function testInvokeReturnsNull(): void
	{
		$fn = TNull::null();
		$this->assertNull($fn());
	}

	/** Invoking the instance via call_user_func() returns PHP null. */
	public function testInvokeViaCallUserFunc(): void
	{
		$this->assertNull(call_user_func(TNull::null()));
	}

	/** TNull works as the callable argument to array_map(). */
	public function testInvokeAsArrayMapCallable(): void
	{
		$this->assertSame([null, null, null, null], array_map(TNull::null(), [1, 'two', true, []]));
	}

	/** array_map with TNull over an empty array returns an empty array. */
	public function testInvokeAsArrayMapOnEmptyArray(): void
	{
		$this->assertSame([], array_map(TNull::null(), []));
	}

	/** TNull stored in a variable typed as callable can be invoked. */
	public function testInvokeStoredAsCallableVariable(): void
	{
		$callable = TNull::null();
		$this->assertNull($callable());
	}

	/** Multiple consecutive invocations all return null. */
	public function testInvokeMultipleTimes(): void
	{
		$fn = TNull::null();
		$this->assertNull($fn());
		$this->assertNull($fn());
		$this->assertNull($fn());
	}

	// -----------------------------------------------------------------------
	// Type and identity assertions
	// -----------------------------------------------------------------------

	/** The singleton is an instance of TNull. */
	public function testInstanceOf(): void
	{
		$this->assertInstanceOf(TNull::class, TNull::null());
	}

	/** Strict identity: null() and singleton() return the exact same object. */
	public function testStrictIdentityNullAndSingleton(): void
	{
		$this->assertSame(TNull::null(), TNull::singleton());
	}

	/** A TNull instance is not strictly identical to PHP null. */
	public function testTNullIsNotStrictlyPhpNull(): void
	{
		$this->assertNotNull(TNull::null());
		$this->assertNotSame(null, TNull::null());
	}

	/**
	 * A TNull instance evaluates as truthy — it is a non-null object and
	 * PHP's empty() returns false for it.  This is precisely why TNull::empty()
	 * requires the explicit instanceof check rather than relying on empty() alone.
	 */
	public function testTNullIsTruthy(): void
	{
		$n = TNull::null();
		$this->assertTrue((bool) $n);
		$this->assertFalse(empty($n));
	}
}