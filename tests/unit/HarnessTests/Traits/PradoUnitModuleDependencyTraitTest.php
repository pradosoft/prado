<?php

/**
 * PradoUnitModuleDependencyTraitTest class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

/**
 * Tests for {@see PradoUnitModuleDependencyTrait}.
 *
 * Covers the normalizer (every valid form, mixed forms, the silently-skipped
 * cases that mirror TApplication::collectModuleDependencies, and the type
 * guard) and the assertion (equivalence across forms, mismatch surfaces as
 * an AssertionFailedError, custom failure messages).
 *
 */
class PradoUnitModuleDependencyTraitTest extends PHPUnit\Framework\TestCase
{
	use PradoUnitModuleDependencyTrait;

	// -----------------------------------------------------------------------
	// normalizeModuleDependencyReturn — empty / no-dep forms
	// -----------------------------------------------------------------------

	public function testNormalize_null_returnsEmptyMap(): void
	{
		$this->assertSame([], self::normalizeModuleDependencyReturn(null));
	}

	public function testNormalize_emptyArray_returnsEmptyMap(): void
	{
		$this->assertSame([], self::normalizeModuleDependencyReturn([]));
	}

	public function testNormalize_emptyString_returnsEmptyMap(): void
	{
		// (array) '' === [''] → skipped because empty-string IDs are silently dropped.
		$this->assertSame([], self::normalizeModuleDependencyReturn(''));
	}

	// -----------------------------------------------------------------------
	// normalizeModuleDependencyReturn — single string shorthand
	// -----------------------------------------------------------------------

	public function testNormalize_singleString_treatedAsRequiredDep(): void
	{
		$this->assertSame(
			['db' => ['id' => 'db', 'required' => true]],
			self::normalizeModuleDependencyReturn('db')
		);
	}

	// -----------------------------------------------------------------------
	// normalizeModuleDependencyReturn — indexed array form
	// -----------------------------------------------------------------------

	public function testNormalize_indexedArray_singleDepReturned(): void
	{
		$this->assertSame(
			['db' => ['id' => 'db', 'required' => true]],
			self::normalizeModuleDependencyReturn(['db'])
		);
	}

	public function testNormalize_indexedArray_multipleDepReturned(): void
	{
		$this->assertSame(
			[
				'db'    => ['id' => 'db',    'required' => true],
				'cache' => ['id' => 'cache', 'required' => true],
			],
			self::normalizeModuleDependencyReturn(['db', 'cache'])
		);
	}

	// -----------------------------------------------------------------------
	// normalizeModuleDependencyReturn — key-value form
	// -----------------------------------------------------------------------

	public function testNormalize_keyValue_boolTrue_required(): void
	{
		$this->assertSame(
			['db' => ['id' => 'db', 'required' => true]],
			self::normalizeModuleDependencyReturn(['db' => true])
		);
	}

	public function testNormalize_keyValue_boolFalse_advisory(): void
	{
		$this->assertSame(
			['db' => ['id' => 'db', 'required' => false]],
			self::normalizeModuleDependencyReturn(['db' => false])
		);
	}

	public function testNormalize_keyValue_stringBoolValue_coerced(): void
	{
		// TPropertyValue::ensureBoolean treats the literal string 'true'
		// (case-insensitive) and non-zero numeric strings as true; everything
		// else (including 'yes'/'no'/'on'/'off') is coerced to false.
		$this->assertSame(
			[
				'db'    => ['id' => 'db',    'required' => true],
				'cache' => ['id' => 'cache', 'required' => false],
			],
			self::normalizeModuleDependencyReturn(['db' => 'true', 'cache' => 'false'])
		);
	}

	public function testNormalize_keyValue_intValue_coerced(): void
	{
		$this->assertSame(
			[
				'db'    => ['id' => 'db',    'required' => true],
				'cache' => ['id' => 'cache', 'required' => false],
			],
			self::normalizeModuleDependencyReturn(['db' => 1, 'cache' => 0])
		);
	}

	// -----------------------------------------------------------------------
	// normalizeModuleDependencyReturn — verbose array form
	// -----------------------------------------------------------------------

	public function testNormalize_verboseArray_requiredTrue(): void
	{
		$this->assertSame(
			['db' => ['id' => 'db', 'required' => true]],
			self::normalizeModuleDependencyReturn([['id' => 'db', 'required' => true]])
		);
	}

	public function testNormalize_verboseArray_requiredFalse(): void
	{
		$this->assertSame(
			['db' => ['id' => 'db', 'required' => false]],
			self::normalizeModuleDependencyReturn([['id' => 'db', 'required' => false]])
		);
	}

	public function testNormalize_verboseArray_missingRequiredDefaultsTrue(): void
	{
		$this->assertSame(
			['db' => ['id' => 'db', 'required' => true]],
			self::normalizeModuleDependencyReturn([['id' => 'db']])
		);
	}

	// -----------------------------------------------------------------------
	// normalizeModuleDependencyReturn — silently-skipped invalid entries
	// -----------------------------------------------------------------------

	public function testNormalize_emptyStringKey_skipped(): void
	{
		// Empty-string key with bool value — skipped.
		$this->assertSame(
			['db' => ['id' => 'db', 'required' => true]],
			self::normalizeModuleDependencyReturn(['' => true, 'db' => true])
		);
	}

	public function testNormalize_nullDepValue_skipped(): void
	{
		$this->assertSame([], self::normalizeModuleDependencyReturn([null]));
	}

	public function testNormalize_verbose_emptyId_skipped(): void
	{
		$this->assertSame([], self::normalizeModuleDependencyReturn([['id' => '']]));
	}

	public function testNormalize_verbose_missingId_skipped(): void
	{
		$this->assertSame([], self::normalizeModuleDependencyReturn([['required' => false]]));
	}

	public function testNormalize_verbose_nullId_skipped(): void
	{
		$this->assertSame([], self::normalizeModuleDependencyReturn([['id' => null]]));
	}

	// -----------------------------------------------------------------------
	// normalizeModuleDependencyReturn — mixed forms
	// -----------------------------------------------------------------------

	public function testNormalize_mixedForms_allCollected(): void
	{
		$this->assertSame(
			[
				'a' => ['id' => 'a', 'required' => true],
				'b' => ['id' => 'b', 'required' => false],
				'c' => ['id' => 'c', 'required' => true],
			],
			self::normalizeModuleDependencyReturn(['a', 'b' => false, ['id' => 'c']])
		);
	}

	// -----------------------------------------------------------------------
	// normalizeModuleDependencyReturn — invalid input type
	// -----------------------------------------------------------------------

	public function testNormalize_intInput_throwsInvalidArgument(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		self::normalizeModuleDependencyReturn(42);
	}

	public function testNormalize_objectInput_throwsInvalidArgument(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		self::normalizeModuleDependencyReturn(new \stdClass());
	}

	public function testNormalize_boolInput_throwsInvalidArgument(): void
	{
		$this->expectException(\InvalidArgumentException::class);
		self::normalizeModuleDependencyReturn(true);
	}

	// -----------------------------------------------------------------------
	// assertModuleDependency — basic equivalence
	// -----------------------------------------------------------------------

	public function testAssert_nullEqualsNull(): void
	{
		$this->assertModuleDependency(null, null);
	}

	public function testAssert_nullEqualsEmptyArray(): void
	{
		$this->assertModuleDependency(null, []);
	}

	public function testAssert_emptyArrayEqualsNull(): void
	{
		$this->assertModuleDependency([], null);
	}

	public function testAssert_stringEqualsItself(): void
	{
		$this->assertModuleDependency('db', 'db');
	}

	// -----------------------------------------------------------------------
	// assertModuleDependency — cross-form equivalence
	// -----------------------------------------------------------------------

	public function testAssert_stringEquivalentToIndexedArray(): void
	{
		$this->assertModuleDependency('db', ['db']);
	}

	public function testAssert_stringEquivalentToKeyValueTrue(): void
	{
		$this->assertModuleDependency('db', ['db' => true]);
	}

	public function testAssert_stringEquivalentToVerboseRequired(): void
	{
		$this->assertModuleDependency('db', [['id' => 'db', 'required' => true]]);
	}

	public function testAssert_indexedEquivalentToKeyValueAllTrue(): void
	{
		$this->assertModuleDependency(['a', 'b'], ['a' => true, 'b' => true]);
	}

	public function testAssert_keyValueEquivalentToVerbose(): void
	{
		$this->assertModuleDependency(
			['db' => true, 'cache' => false],
			[
				['id' => 'db',    'required' => true],
				['id' => 'cache', 'required' => false],
			]
		);
	}

	public function testAssert_orderIndependent_sameDeps(): void
	{
		// The map is keyed by ID; insertion order does not affect equality.
		$this->assertModuleDependency(['a', 'b'], ['b', 'a']);
	}

	// -----------------------------------------------------------------------
	// assertModuleDependency — mismatch surfaces as PHPUnit failure
	// -----------------------------------------------------------------------

	public function testAssert_differentIds_fails(): void
	{
		$this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
		$this->assertModuleDependency('db', 'cache');
	}

	public function testAssert_differentRequiredFlags_fails(): void
	{
		$this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
		$this->assertModuleDependency(['db' => true], ['db' => false]);
	}

	public function testAssert_missingDep_fails(): void
	{
		$this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
		$this->assertModuleDependency(['db', 'cache'], 'db');
	}

	public function testAssert_extraDep_fails(): void
	{
		$this->expectException(\PHPUnit\Framework\AssertionFailedError::class);
		$this->assertModuleDependency('db', ['db', 'cache']);
	}

	// -----------------------------------------------------------------------
	// assertModuleDependency — custom failure message propagates
	// -----------------------------------------------------------------------

	public function testAssert_customMessage_propagatedOnFailure(): void
	{
		try {
			$this->assertModuleDependency('db', 'cache', 'custom-message-marker');
			$this->fail('Expected AssertionFailedError was not raised.');
		} catch (\PHPUnit\Framework\AssertionFailedError $e) {
			$this->assertStringContainsString('custom-message-marker', $e->getMessage());
		}
	}

	// -----------------------------------------------------------------------
	// Callable as instance method, static method, and self:: — PHPUnit style
	// -----------------------------------------------------------------------

	public function testAssert_callableAsInstanceStaticAndSelf(): void
	{
		$this->assertModuleDependency('db', 'db');
		static::assertModuleDependency('db', 'db');
		self::assertModuleDependency('db', 'db');
	}
}
