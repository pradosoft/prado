<?php

use Prado\Collections\TCollectionItemChangeParameter;
use Prado\Exceptions\TInvalidOperationException;
use Prado\TEventParameter;
use PHPUnit\Framework\TestCase;

/**
 * Comprehensive unit tests for {@see TCollectionItemChangeParameter}.
 *
 * Coverage:
 *  - Public constants: flag bits and parameter array keys
 *  - Constructor: all parameter combinations, default values
 *  - Typed getters/setters: key, value, oldValue, flags
 *  - Individual flag getters/setters: isDefault, isNew, isUnset
 *  - Flag isolation: each flag is independently toggled without affecting the others
 *  - offsetExists: all documented conditional presence rules
 *  - offsetGet: all named keys proxied through typed getters
 *  - offsetSet: typed proxying, type coercion, read-only enforcement
 *  - offsetUnset: zero-value resets, read-only enforcement
 *  - Typed setters enforce read-only (via parent::offsetSet chain)
 *  - Fall-through to parent ArrayAccess for unknown keys
 *  - Class hierarchy
 *  - Semantic scenarios matching TApplication usage
 */
class TCollectionItemChangeParameterTest extends TestCase
{
	// =========================================================================
	// Flag bit constants
	// =========================================================================

	public function testIsDefaultConstantIsOneBit()
	{
		$this->assertSame(1, TCollectionItemChangeParameter::IS_DEFAULT);
	}

	public function testIsNewConstantIsTwoBit()
	{
		$this->assertSame(2, TCollectionItemChangeParameter::IS_NEW);
	}

	public function testIsUnsetConstantIsFourBit()
	{
		$this->assertSame(4, TCollectionItemChangeParameter::IS_UNSET);
	}

	public function testFlagConstantsAreDistinctSingleBits()
	{
		$this->assertSame(0, TCollectionItemChangeParameter::IS_DEFAULT & TCollectionItemChangeParameter::IS_NEW);
		$this->assertSame(0, TCollectionItemChangeParameter::IS_DEFAULT & TCollectionItemChangeParameter::IS_UNSET);
		$this->assertSame(0, TCollectionItemChangeParameter::IS_NEW & TCollectionItemChangeParameter::IS_UNSET);
	}

	// =========================================================================
	// Parameter array key constants
	// =========================================================================

	public function testKeyConstantValue()
	{
		$this->assertSame('key', TCollectionItemChangeParameter::KEY);
	}

	public function testValueConstantValue()
	{
		$this->assertSame('value', TCollectionItemChangeParameter::VALUE);
	}

	public function testOldValueConstantValue()
	{
		$this->assertSame('oldValue', TCollectionItemChangeParameter::OLD_VALUE);
	}

	public function testFlagsConstantValue()
	{
		$this->assertSame('flags', TCollectionItemChangeParameter::FLAGS);
	}

	// =========================================================================
	// Class hierarchy
	// =========================================================================

	public function testExtendsTEventParameter()
	{
		$this->assertInstanceOf(TEventParameter::class, new TCollectionItemChangeParameter());
	}

	public function testImplementsArrayAccess()
	{
		$this->assertInstanceOf(ArrayAccess::class, new TCollectionItemChangeParameter());
	}

	// =========================================================================
	// Constructor defaults
	// =========================================================================

	public function testDefaultConstructorHasEmptyKey()
	{
		$param = new TCollectionItemChangeParameter();
		$this->assertSame('', $param->getKey());
	}

	public function testDefaultConstructorHasNullValue()
	{
		$param = new TCollectionItemChangeParameter();
		$this->assertNull($param->getValue());
	}

	public function testDefaultConstructorHasNullOldValue()
	{
		$param = new TCollectionItemChangeParameter();
		$this->assertNull($param->getOldValue());
	}

	public function testDefaultConstructorHasZeroFlags()
	{
		$param = new TCollectionItemChangeParameter();
		$this->assertSame(0, $param->getFlags());
	}

	public function testDefaultConstructorAllFlagsFalse()
	{
		$param = new TCollectionItemChangeParameter();
		$this->assertFalse($param->getIsDefault());
		$this->assertFalse($param->getIsNew());
		$this->assertFalse($param->getIsUnset());
	}

	public function testDefaultConstructorIsNotReadOnly()
	{
		$param = new TCollectionItemChangeParameter();
		$this->assertFalse($param->getReadOnly());
	}

	// =========================================================================
	// Constructor with arguments
	// =========================================================================

	public function testConstructorSetsKey()
	{
		$param = new TCollectionItemChangeParameter('myKey');
		$this->assertSame('myKey', $param->getKey());
	}

	public function testConstructorSetsValue()
	{
		$param = new TCollectionItemChangeParameter('k', 'newVal');
		$this->assertSame('newVal', $param->getValue());
	}

	public function testConstructorSetsOldValue()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', 'oldVal');
		$this->assertSame('oldVal', $param->getOldValue());
	}

	public function testConstructorSetsFlags()
	{
		$flags = TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_NEW;
		$param = new TCollectionItemChangeParameter('k', 'v', null, $flags);
		$this->assertSame($flags, $param->getFlags());
		$this->assertTrue($param->getIsDefault());
		$this->assertTrue($param->getIsNew());
		$this->assertFalse($param->getIsUnset());
	}

	public function testConstructorSetsReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->assertTrue($param->getReadOnly());
	}

	public function testConstructorReadOnlyFalseExplicit()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, false);
		$this->assertFalse($param->getReadOnly());
	}

	public function testConstructorNullValuesStoredCorrectly()
	{
		$param = new TCollectionItemChangeParameter('k', null, null, 0);
		$this->assertNull($param->getValue());
		$this->assertNull($param->getOldValue());
	}

	public function testConstructorComplexValues()
	{
		$newVal = ['a' => 1];
		$oldVal = new stdClass();
		$param = new TCollectionItemChangeParameter('k', $newVal, $oldVal, 0);
		$this->assertSame($newVal, $param->getValue());
		$this->assertSame($oldVal, $param->getOldValue());
	}

	// =========================================================================
	// Parameter is stored in the inherited parameter array
	// =========================================================================

	public function testParameterIsArray()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', 'old', TCollectionItemChangeParameter::IS_DEFAULT);
		$this->assertTrue($param->getParameterIsArray());
	}

	public function testParameterArrayContainsAllKeys()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', 'old', TCollectionItemChangeParameter::IS_NEW);
		$arr = $param->getParameter();
		$this->assertArrayHasKey(TCollectionItemChangeParameter::KEY, $arr);
		$this->assertArrayHasKey(TCollectionItemChangeParameter::VALUE, $arr);
		$this->assertArrayHasKey(TCollectionItemChangeParameter::OLD_VALUE, $arr);
		$this->assertArrayHasKey(TCollectionItemChangeParameter::FLAGS, $arr);
	}

	public function testParameterArrayValuesMatchGetters()
	{
		$flags = TCollectionItemChangeParameter::IS_NEW;
		$param = new TCollectionItemChangeParameter('myKey', 'myVal', null, $flags);
		$arr = $param->getParameter();
		$this->assertSame('myKey', $arr[TCollectionItemChangeParameter::KEY]);
		$this->assertSame('myVal', $arr[TCollectionItemChangeParameter::VALUE]);
		$this->assertNull($arr[TCollectionItemChangeParameter::OLD_VALUE]);
		$this->assertSame($flags, $arr[TCollectionItemChangeParameter::FLAGS]);
	}

	// =========================================================================
	// Key getter / setter
	// =========================================================================

	public function testGetSetKey()
	{
		$param = new TCollectionItemChangeParameter();
		$param->setKey('theKey');
		$this->assertSame('theKey', $param->getKey());
	}

	public function testSetKeyEmptyString()
	{
		$param = new TCollectionItemChangeParameter('original');
		$param->setKey('');
		$this->assertSame('', $param->getKey());
	}

	public function testSetKeyOverwritesPreviousValue()
	{
		$param = new TCollectionItemChangeParameter('first');
		$param->setKey('second');
		$this->assertSame('second', $param->getKey());
	}

	public function testSetKeyThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->setKey('new');
	}

	// =========================================================================
	// Value getter / setter
	// =========================================================================

	public function testGetSetValue()
	{
		$param = new TCollectionItemChangeParameter();
		$param->setValue('hello');
		$this->assertSame('hello', $param->getValue());
	}

	public function testSetValueToNull()
	{
		$param = new TCollectionItemChangeParameter('k', 'existing');
		$param->setValue(null);
		$this->assertNull($param->getValue());
	}

	public function testSetValueFalse()
	{
		$param = new TCollectionItemChangeParameter();
		$param->setValue(false);
		$this->assertFalse($param->getValue());
	}

	public function testSetValueZero()
	{
		$param = new TCollectionItemChangeParameter();
		$param->setValue(0);
		$this->assertSame(0, $param->getValue());
	}

	public function testSetValueArray()
	{
		$param = new TCollectionItemChangeParameter();
		$param->setValue([1, 2, 3]);
		$this->assertSame([1, 2, 3], $param->getValue());
	}

	public function testSetValueThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->setValue('new');
	}

	// =========================================================================
	// OldValue getter / setter
	// =========================================================================

	public function testGetSetOldValue()
	{
		$param = new TCollectionItemChangeParameter();
		$param->setOldValue('previous');
		$this->assertSame('previous', $param->getOldValue());
	}

	public function testSetOldValueToNull()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old');
		$param->setOldValue(null);
		$this->assertNull($param->getOldValue());
	}

	public function testSetOldValueObject()
	{
		$obj = new stdClass();
		$param = new TCollectionItemChangeParameter();
		$param->setOldValue($obj);
		$this->assertSame($obj, $param->getOldValue());
	}

	public function testSetOldValueThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', 'old', 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->setOldValue('new');
	}

	// =========================================================================
	// Flags bitmask getter / setter
	// =========================================================================

	public function testGetSetFlags()
	{
		$param = new TCollectionItemChangeParameter();
		$param->setFlags(TCollectionItemChangeParameter::IS_NEW);
		$this->assertSame(TCollectionItemChangeParameter::IS_NEW, $param->getFlags());
	}

	public function testSetFlagsToZeroClearsAll()
	{
		$all = TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_NEW | TCollectionItemChangeParameter::IS_UNSET;
		$param = new TCollectionItemChangeParameter('k', 'v', null, $all);
		$param->setFlags(0);
		$this->assertSame(0, $param->getFlags());
		$this->assertFalse($param->getIsDefault());
		$this->assertFalse($param->getIsNew());
		$this->assertFalse($param->getIsUnset());
	}

	public function testSetFlagsAllCombined()
	{
		$all = TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_NEW | TCollectionItemChangeParameter::IS_UNSET;
		$param = new TCollectionItemChangeParameter();
		$param->setFlags($all);
		$this->assertSame($all, $param->getFlags());
		$this->assertTrue($param->getIsDefault());
		$this->assertTrue($param->getIsNew());
		$this->assertTrue($param->getIsUnset());
	}

	public function testSetFlagsThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->setFlags(TCollectionItemChangeParameter::IS_DEFAULT);
	}

	// =========================================================================
	// IsDefault flag getter / setter
	// =========================================================================

	public function testIsDefaultFalseByDefault()
	{
		$this->assertFalse((new TCollectionItemChangeParameter())->getIsDefault());
	}

	public function testSetIsDefaultTrue()
	{
		$param = new TCollectionItemChangeParameter();
		$param->setIsDefault(true);
		$this->assertTrue($param->getIsDefault());
		$this->assertSame(TCollectionItemChangeParameter::IS_DEFAULT, $param->getFlags() & TCollectionItemChangeParameter::IS_DEFAULT);
	}

	public function testSetIsDefaultFalseClears()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_DEFAULT);
		$param->setIsDefault(false);
		$this->assertFalse($param->getIsDefault());
		$this->assertSame(0, $param->getFlags() & TCollectionItemChangeParameter::IS_DEFAULT);
	}

	public function testSetIsDefaultDoesNotAffectOtherFlags()
	{
		$others = TCollectionItemChangeParameter::IS_NEW | TCollectionItemChangeParameter::IS_UNSET;
		$param = new TCollectionItemChangeParameter('k', 'v', null, $others);
		$param->setIsDefault(true);
		$this->assertTrue($param->getIsNew());
		$this->assertTrue($param->getIsUnset());
		$param->setIsDefault(false);
		$this->assertTrue($param->getIsNew());
		$this->assertTrue($param->getIsUnset());
	}

	public function testSetIsDefaultThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->setIsDefault(true);
	}

	// =========================================================================
	// IsNew flag getter / setter
	// =========================================================================

	public function testIsNewFalseByDefault()
	{
		$this->assertFalse((new TCollectionItemChangeParameter())->getIsNew());
	}

	public function testSetIsNewTrue()
	{
		$param = new TCollectionItemChangeParameter();
		$param->setIsNew(true);
		$this->assertTrue($param->getIsNew());
		$this->assertSame(TCollectionItemChangeParameter::IS_NEW, $param->getFlags() & TCollectionItemChangeParameter::IS_NEW);
	}

	public function testSetIsNewFalseClears()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_NEW);
		$param->setIsNew(false);
		$this->assertFalse($param->getIsNew());
		$this->assertSame(0, $param->getFlags() & TCollectionItemChangeParameter::IS_NEW);
	}

	public function testSetIsNewDoesNotAffectOtherFlags()
	{
		$others = TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_UNSET;
		$param = new TCollectionItemChangeParameter('k', 'v', null, $others);
		$param->setIsNew(true);
		$this->assertTrue($param->getIsDefault());
		$this->assertTrue($param->getIsUnset());
		$param->setIsNew(false);
		$this->assertTrue($param->getIsDefault());
		$this->assertTrue($param->getIsUnset());
	}

	public function testSetIsNewThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->setIsNew(true);
	}

	// =========================================================================
	// IsUnset flag getter / setter
	// =========================================================================

	public function testIsUnsetFalseByDefault()
	{
		$this->assertFalse((new TCollectionItemChangeParameter())->getIsUnset());
	}

	public function testSetIsUnsetTrue()
	{
		$param = new TCollectionItemChangeParameter();
		$param->setIsUnset(true);
		$this->assertTrue($param->getIsUnset());
		$this->assertSame(TCollectionItemChangeParameter::IS_UNSET, $param->getFlags() & TCollectionItemChangeParameter::IS_UNSET);
	}

	public function testSetIsUnsetFalseClears()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET);
		$param->setIsUnset(false);
		$this->assertFalse($param->getIsUnset());
		$this->assertSame(0, $param->getFlags() & TCollectionItemChangeParameter::IS_UNSET);
	}

	public function testSetIsUnsetDoesNotAffectOtherFlags()
	{
		$others = TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_NEW;
		$param = new TCollectionItemChangeParameter('k', 'v', null, $others);
		$param->setIsUnset(true);
		$this->assertTrue($param->getIsDefault());
		$this->assertTrue($param->getIsNew());
		$param->setIsUnset(false);
		$this->assertTrue($param->getIsDefault());
		$this->assertTrue($param->getIsNew());
	}

	public function testSetIsUnsetThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->setIsUnset(true);
	}

	// =========================================================================
	// All three flags independently toggled — round-trip
	// =========================================================================

	public function testAllThreeFlagsIndependentlyToggleable()
	{
		$param = new TCollectionItemChangeParameter();
		$this->assertSame(0, $param->getFlags());

		$param->setIsDefault(true);
		$this->assertSame(TCollectionItemChangeParameter::IS_DEFAULT, $param->getFlags());

		$param->setIsNew(true);
		$this->assertSame(
			TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_NEW,
			$param->getFlags()
		);

		$param->setIsUnset(true);
		$this->assertSame(
			TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_NEW | TCollectionItemChangeParameter::IS_UNSET,
			$param->getFlags()
		);

		$param->setIsDefault(false);
		$this->assertSame(
			TCollectionItemChangeParameter::IS_NEW | TCollectionItemChangeParameter::IS_UNSET,
			$param->getFlags()
		);

		$param->setIsNew(false);
		$this->assertSame(TCollectionItemChangeParameter::IS_UNSET, $param->getFlags());

		$param->setIsUnset(false);
		$this->assertSame(0, $param->getFlags());
	}

	// =========================================================================
	// offsetExists — 'key' and 'flags' always present
	// =========================================================================

	public function testOffsetExistsKeyAlwaysTrueWithNoFlags()
	{
		$this->assertTrue((new TCollectionItemChangeParameter('k', 'v', null, 0))->offsetExists('key'));
	}

	public function testOffsetExistsKeyAlwaysTrueWhenIsUnset()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET);
		$this->assertTrue($param->offsetExists('key'));
	}

	public function testOffsetExistsKeyAlwaysTrueViaConstant()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_NEW);
		$this->assertTrue($param->offsetExists(TCollectionItemChangeParameter::KEY));
	}

	public function testOffsetExistsFlagsAlwaysTrue()
	{
		$this->assertTrue((new TCollectionItemChangeParameter())->offsetExists('flags'));
	}

	public function testOffsetExistsFlagsAlwaysTrueViaConstant()
	{
		$this->assertTrue((new TCollectionItemChangeParameter())->offsetExists(TCollectionItemChangeParameter::FLAGS));
	}

	// =========================================================================
	// offsetExists — value / isDefault / isNew (present when !isUnset)
	// =========================================================================

	public function testOffsetExistsValueTrueWhenNotIsUnset()
	{
		$this->assertTrue((new TCollectionItemChangeParameter('k', 'v', null, 0))->offsetExists('value'));
	}

	public function testOffsetExistsValueFalseWhenIsUnset()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET);
		$this->assertFalse($param->offsetExists('value'));
	}

	public function testOffsetExistsValueTrueViaConstantWhenNotIsUnset()
	{
		$this->assertTrue((new TCollectionItemChangeParameter('k', null, null, 0))->offsetExists(TCollectionItemChangeParameter::VALUE));
	}

	public function testOffsetExistsIsDefaultTrueWhenNotIsUnset()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_DEFAULT);
		$this->assertTrue($param->offsetExists('isDefault'));
	}

	public function testOffsetExistsIsDefaultFalseWhenIsDefault()
	{
		// isDefault=false (flag not set) but not isUnset — still present
		$this->assertTrue((new TCollectionItemChangeParameter('k', 'v', null, 0))->offsetExists('isDefault'));
	}

	public function testOffsetExistsIsDefaultFalseWhenIsUnset()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET);
		$this->assertFalse($param->offsetExists('isDefault'));
	}

	public function testOffsetExistsIsNewTrueWhenNotIsUnset()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_NEW);
		$this->assertTrue($param->offsetExists('isNew'));
	}

	public function testOffsetExistsIsNewFalseIsNewFlagUnsetButStillPresentWhenNotIsUnset()
	{
		// isNew flag not set, but still present because !isUnset
		$this->assertTrue((new TCollectionItemChangeParameter())->offsetExists('isNew'));
	}

	public function testOffsetExistsIsNewFalseWhenIsUnset()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET);
		$this->assertFalse($param->offsetExists('isNew'));
	}

	// =========================================================================
	// offsetExists — isUnset (present only when IS_UNSET flag is set)
	// =========================================================================

	public function testOffsetExistsIsUnsetFalseWhenFlagNotSet()
	{
		$this->assertFalse((new TCollectionItemChangeParameter('k', 'v', null, 0))->offsetExists('isUnset'));
	}

	public function testOffsetExistsIsUnsetTrueWhenFlagSet()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET);
		$this->assertTrue($param->offsetExists('isUnset'));
	}

	// =========================================================================
	// offsetExists — oldValue (present when !isNew)
	// =========================================================================

	public function testOffsetExistsOldValueTrueWhenIsNewFlagNotSet()
	{
		$this->assertTrue((new TCollectionItemChangeParameter('k', 'v', 'old', 0))->offsetExists('oldValue'));
	}

	public function testOffsetExistsOldValueTrueViaConstantWhenNotIsNew()
	{
		$this->assertTrue((new TCollectionItemChangeParameter('k', 'v', null, 0))->offsetExists(TCollectionItemChangeParameter::OLD_VALUE));
	}

	public function testOffsetExistsOldValueFalseWhenIsNewFlagSet()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_NEW);
		$this->assertFalse($param->offsetExists('oldValue'));
	}

	public function testOffsetExistsOldValueTrueWhenIsUnsetButNotIsNew()
	{
		// clearGlobalState scenario: IS_UNSET set, IS_NEW not set → oldValue meaningful
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET);
		$this->assertTrue($param->offsetExists('oldValue'));
	}

	public function testOffsetExistsOldValueNullStoredButStillPresentWhenNotIsNew()
	{
		// null oldValue is a legitimate stored value; presence rule is about isNew, not stored value
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0);
		$this->assertTrue($param->offsetExists('oldValue'));
	}

	// =========================================================================
	// offsetExists — unknown key falls through to parent
	// =========================================================================

	public function testOffsetExistsUnknownKeyReturnsFalse()
	{
		$param = new TCollectionItemChangeParameter();
		$this->assertFalse($param->offsetExists('unknownKey'));
		$this->assertFalse($param->offsetExists(''));
	}

	// =========================================================================
	// offsetGet — all named keys
	// =========================================================================

	public function testOffsetGetKey()
	{
		$param = new TCollectionItemChangeParameter('myKey');
		$this->assertSame('myKey', $param->offsetGet('key'));
		$this->assertSame('myKey', $param->offsetGet(TCollectionItemChangeParameter::KEY));
	}

	public function testOffsetGetValue()
	{
		$param = new TCollectionItemChangeParameter('k', 'theValue');
		$this->assertSame('theValue', $param->offsetGet('value'));
		$this->assertSame('theValue', $param->offsetGet(TCollectionItemChangeParameter::VALUE));
	}

	public function testOffsetGetValueNullWhenIsUnset()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET);
		$this->assertNull($param->offsetGet('value'));
	}

	public function testOffsetGetOldValue()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', 'previousValue', 0);
		$this->assertSame('previousValue', $param->offsetGet('oldValue'));
		$this->assertSame('previousValue', $param->offsetGet(TCollectionItemChangeParameter::OLD_VALUE));
	}

	public function testOffsetGetOldValueNullWhenIsNew()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_NEW);
		$this->assertNull($param->offsetGet('oldValue'));
	}

	public function testOffsetGetOldValueNullIsLegitimateWhenNotIsNew()
	{
		// null oldValue stored explicitly — meaningful when isNew=false
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0);
		$this->assertFalse($param->getIsNew());
		$this->assertTrue($param->offsetExists('oldValue'));
		$this->assertNull($param->offsetGet('oldValue'));
	}

	public function testOffsetGetFlagsReturnsInt()
	{
		$flags = TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_NEW;
		$param = new TCollectionItemChangeParameter('k', 'v', null, $flags);
		$this->assertSame($flags, $param->offsetGet('flags'));
		$this->assertSame($flags, $param->offsetGet(TCollectionItemChangeParameter::FLAGS));
	}

	public function testOffsetGetFlagsIsZeroByDefault()
	{
		$this->assertSame(0, (new TCollectionItemChangeParameter())->offsetGet('flags'));
	}

	public function testOffsetGetIsDefaultFalse()
	{
		$this->assertFalse((new TCollectionItemChangeParameter('k', 'v', null, 0))->offsetGet('isDefault'));
	}

	public function testOffsetGetIsDefaultTrue()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_DEFAULT);
		$this->assertTrue($param->offsetGet('isDefault'));
	}

	public function testOffsetGetIsNewFalse()
	{
		$this->assertFalse((new TCollectionItemChangeParameter('k', 'v', 'old', 0))->offsetGet('isNew'));
	}

	public function testOffsetGetIsNewTrue()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_NEW);
		$this->assertTrue($param->offsetGet('isNew'));
	}

	public function testOffsetGetIsUnsetFalse()
	{
		$this->assertFalse((new TCollectionItemChangeParameter('k', 'v', null, 0))->offsetGet('isUnset'));
	}

	public function testOffsetGetIsUnsetTrue()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET);
		$this->assertTrue($param->offsetGet('isUnset'));
	}

	public function testOffsetGetUnknownKeyReturnsNull()
	{
		$param = new TCollectionItemChangeParameter();
		$this->assertNull($param->offsetGet('unknownKey'));
	}

	// =========================================================================
	// offsetSet — proxies to typed setters with coercion
	// =========================================================================

	public function testOffsetSetKey()
	{
		$param = new TCollectionItemChangeParameter('old');
		$param->offsetSet('key', 'new');
		$this->assertSame('new', $param->getKey());
	}

	public function testOffsetSetKeyViaConstant()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet(TCollectionItemChangeParameter::KEY, 'k');
		$this->assertSame('k', $param->getKey());
	}

	public function testOffsetSetKeyCoercesToString()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('key', 123);
		$this->assertSame('123', $param->getKey());
	}

	public function testOffsetSetValue()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('value', 'newValue');
		$this->assertSame('newValue', $param->getValue());
	}

	public function testOffsetSetValueNull()
	{
		$param = new TCollectionItemChangeParameter('k', 'existing');
		$param->offsetSet('value', null);
		$this->assertNull($param->getValue());
	}

	public function testOffsetSetOldValue()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('oldValue', 'theOld');
		$this->assertSame('theOld', $param->getOldValue());
	}

	public function testOffsetSetFlagsInt()
	{
		$flags = TCollectionItemChangeParameter::IS_UNSET;
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('flags', $flags);
		$this->assertSame($flags, $param->getFlags());
		$this->assertTrue($param->getIsUnset());
	}

	public function testOffsetSetFlagsViaConstant()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet(TCollectionItemChangeParameter::FLAGS, TCollectionItemChangeParameter::IS_DEFAULT);
		$this->assertTrue($param->getIsDefault());
	}

	public function testOffsetSetFlagsCoercesToInt()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('flags', '3'); // IS_DEFAULT | IS_NEW
		$this->assertSame(3, $param->getFlags());
	}

	public function testOffsetSetIsDefaultTrue()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('isDefault', true);
		$this->assertTrue($param->getIsDefault());
	}

	public function testOffsetSetIsDefaultFalseClears()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_DEFAULT);
		$param->offsetSet('isDefault', false);
		$this->assertFalse($param->getIsDefault());
	}

	public function testOffsetSetIsDefaultCoercesToBool()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('isDefault', 1);
		$this->assertTrue($param->getIsDefault());
		$param->offsetSet('isDefault', 0);
		$this->assertFalse($param->getIsDefault());
	}

	public function testOffsetSetIsNew()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('isNew', true);
		$this->assertTrue($param->getIsNew());
	}

	public function testOffsetSetIsUnset()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('isUnset', true);
		$this->assertTrue($param->getIsUnset());
	}

	public function testOffsetSetUnknownKeyFallsToParent()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('extra', 'extraValue');
		$this->assertSame('extraValue', $param->offsetGet('extra'));
	}

	// =========================================================================
	// offsetUnset — resets typed fields to zero values
	// =========================================================================

	public function testOffsetUnsetKeyResetsToEmpty()
	{
		$param = new TCollectionItemChangeParameter('myKey');
		$param->offsetUnset('key');
		$this->assertSame('', $param->getKey());
	}

	public function testOffsetUnsetValueSetsNull()
	{
		$param = new TCollectionItemChangeParameter('k', 'v');
		$param->offsetUnset('value');
		$this->assertNull($param->getValue());
	}

	public function testOffsetUnsetOldValueSetsNull()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', 'old');
		$param->offsetUnset('oldValue');
		$this->assertNull($param->getOldValue());
	}

	public function testOffsetUnsetFlagsResetsToZero()
	{
		$all = TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_NEW | TCollectionItemChangeParameter::IS_UNSET;
		$param = new TCollectionItemChangeParameter('k', null, null, $all);
		$param->offsetUnset('flags');
		$this->assertSame(0, $param->getFlags());
		$this->assertFalse($param->getIsDefault());
		$this->assertFalse($param->getIsNew());
		$this->assertFalse($param->getIsUnset());
	}

	public function testOffsetUnsetIsDefaultClearsFlag()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_DEFAULT);
		$param->offsetUnset('isDefault');
		$this->assertFalse($param->getIsDefault());
	}

	public function testOffsetUnsetIsNewClearsFlag()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_NEW);
		$param->offsetUnset('isNew');
		$this->assertFalse($param->getIsNew());
	}

	public function testOffsetUnsetIsUnsetClearsFlag()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET);
		$param->offsetUnset('isUnset');
		$this->assertFalse($param->getIsUnset());
	}

	public function testOffsetUnsetIsDefaultPreservesOtherFlags()
	{
		$flags = TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_NEW;
		$param = new TCollectionItemChangeParameter('k', 'v', null, $flags);
		$param->offsetUnset('isDefault');
		$this->assertFalse($param->getIsDefault());
		$this->assertTrue($param->getIsNew());
	}

	public function testOffsetUnsetUnknownKeyFallsToParent()
	{
		$param = new TCollectionItemChangeParameter();
		$param->offsetSet('extra', 'val');
		$param->offsetUnset('extra');
		$this->assertNull($param->offsetGet('extra'));
	}

	// =========================================================================
	// Read-only enforcement via offsetSet (through setter → parent::offsetSet)
	// =========================================================================

	public function testOffsetSetKeyThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetSet('key', 'new');
	}

	public function testOffsetSetValueThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetSet('value', 'new');
	}

	public function testOffsetSetOldValueThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', 'old', 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetSet('oldValue', 'new');
	}

	public function testOffsetSetFlagsThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetSet('flags', TCollectionItemChangeParameter::IS_DEFAULT);
	}

	public function testOffsetSetIsDefaultThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetSet('isDefault', true);
	}

	public function testOffsetSetIsNewThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetSet('isNew', true);
	}

	public function testOffsetSetIsUnsetThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetSet('isUnset', true);
	}

	public function testOffsetSetUnknownKeyThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetSet('extra', 'value');
	}

	// =========================================================================
	// Read-only enforcement via offsetUnset
	// =========================================================================

	public function testOffsetUnsetKeyThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetUnset('key');
	}

	public function testOffsetUnsetValueThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetUnset('value');
	}

	public function testOffsetUnsetOldValueThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', 'old', 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetUnset('oldValue');
	}

	public function testOffsetUnsetFlagsThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_DEFAULT, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetUnset('flags');
	}

	public function testOffsetUnsetIsDefaultThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_DEFAULT, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetUnset('isDefault');
	}

	public function testOffsetUnsetIsNewThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, TCollectionItemChangeParameter::IS_NEW, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetUnset('isNew');
	}

	public function testOffsetUnsetIsUnsetThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetUnset('isUnset');
	}

	public function testOffsetUnsetUnknownKeyThrowsWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->expectException(TInvalidOperationException::class);
		$param->offsetUnset('extra');
	}

	// =========================================================================
	// Read-only: getters and offsetGet/offsetExists remain accessible
	// =========================================================================

	public function testGettersAllowedWhenReadOnly()
	{
		$flags = TCollectionItemChangeParameter::IS_DEFAULT | TCollectionItemChangeParameter::IS_NEW;
		$param = new TCollectionItemChangeParameter('k', 'v', 'old', $flags, true);

		$this->assertSame('k', $param->getKey());
		$this->assertSame('v', $param->getValue());
		$this->assertSame('old', $param->getOldValue());
		$this->assertSame($flags, $param->getFlags());
		$this->assertTrue($param->getIsDefault());
		$this->assertTrue($param->getIsNew());
		$this->assertFalse($param->getIsUnset());
		$this->assertTrue($param->getReadOnly());
	}

	public function testOffsetGetAllowedWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('myKey', 'myVal', 'myOld', TCollectionItemChangeParameter::IS_DEFAULT, true);

		$this->assertSame('myKey', $param->offsetGet('key'));
		$this->assertSame('myVal', $param->offsetGet('value'));
		$this->assertSame('myOld', $param->offsetGet('oldValue'));
		$this->assertSame(TCollectionItemChangeParameter::IS_DEFAULT, $param->offsetGet('flags'));
		$this->assertTrue($param->offsetGet('isDefault'));
		$this->assertFalse($param->offsetGet('isNew'));
		$this->assertFalse($param->offsetGet('isUnset'));
	}

	public function testOffsetExistsAllowedWhenReadOnly()
	{
		$param = new TCollectionItemChangeParameter('k', 'v', 'old', TCollectionItemChangeParameter::IS_DEFAULT, true);

		$this->assertTrue($param->offsetExists('key'));
		$this->assertTrue($param->offsetExists('value'));
		$this->assertTrue($param->offsetExists('isDefault'));
		$this->assertTrue($param->offsetExists('isNew'));
		$this->assertFalse($param->offsetExists('isUnset'));
		$this->assertTrue($param->offsetExists('oldValue'));
		$this->assertTrue($param->offsetExists('flags'));
	}

	// =========================================================================
	// Semantic scenarios — mirror TApplication::setGlobalState / clearGlobalState
	// =========================================================================

	public function testSetGlobalStateNewKeyScenario()
	{
		// Key did not previously exist: IS_NEW set, isUnset=false, isDefault=false
		$param = new TCollectionItemChangeParameter('myState', 'initialValue', null, TCollectionItemChangeParameter::IS_NEW, true);

		$this->assertTrue($param->offsetGet('isNew'));
		$this->assertFalse($param->offsetGet('isDefault'));
		$this->assertFalse($param->offsetGet('isUnset'));
		// value present (isUnset=false)
		$this->assertTrue($param->offsetExists('value'));
		$this->assertSame('initialValue', $param->offsetGet('value'));
		// oldValue absent (isNew=true); null placeholder
		$this->assertFalse($param->offsetExists('oldValue'));
		$this->assertNull($param->getOldValue());
	}

	public function testSetGlobalStateExistingKeyScenario()
	{
		// Key existed, value changed: no flags set
		$param = new TCollectionItemChangeParameter('myState', 'newValue', 'oldValue', 0, true);

		$this->assertFalse($param->offsetGet('isNew'));
		$this->assertFalse($param->offsetGet('isDefault'));
		$this->assertFalse($param->offsetGet('isUnset'));
		// value present
		$this->assertTrue($param->offsetExists('value'));
		// oldValue present (isNew=false)
		$this->assertTrue($param->offsetExists('oldValue'));
		$this->assertSame('oldValue', $param->offsetGet('oldValue'));
	}

	public function testSetGlobalStateClearedToDefaultScenario()
	{
		// value === defaultValue: IS_DEFAULT set, key existed so isNew=false
		$param = new TCollectionItemChangeParameter('myState', null, 'previousValue', TCollectionItemChangeParameter::IS_DEFAULT, true);

		$this->assertTrue($param->offsetGet('isDefault'));
		$this->assertFalse($param->offsetGet('isNew'));
		$this->assertFalse($param->offsetGet('isUnset'));
		// value present (!isUnset)
		$this->assertTrue($param->offsetExists('value'));
		// oldValue present (isNew=false)
		$this->assertTrue($param->offsetExists('oldValue'));
		$this->assertSame('previousValue', $param->offsetGet('oldValue'));
	}

	public function testClearGlobalStateScenario()
	{
		// Key explicitly removed: IS_UNSET set, isNew=false (key existed)
		$param = new TCollectionItemChangeParameter('myState', null, 'storedValue', TCollectionItemChangeParameter::IS_UNSET, true);

		$this->assertTrue($param->offsetGet('isUnset'));
		// value/isDefault/isNew absent when isUnset
		$this->assertFalse($param->offsetExists('value'));
		$this->assertFalse($param->offsetExists('isDefault'));
		$this->assertFalse($param->offsetExists('isNew'));
		// oldValue present (isNew=false)
		$this->assertTrue($param->offsetExists('oldValue'));
		$this->assertSame('storedValue', $param->offsetGet('oldValue'));
		// key always present
		$this->assertTrue($param->offsetExists('key'));
		$this->assertSame('myState', $param->offsetGet('key'));
		// flags always present
		$this->assertTrue($param->offsetExists('flags'));
	}

	public function testIsUnsetFalseInDefaultState()
	{
		// When no flags are set, isUnset is false — the "no value at all" default
		$param = new TCollectionItemChangeParameter();
		$this->assertFalse($param->getIsUnset());
		$this->assertSame(0, $param->getFlags());
	}

	public function testValueIsNullWhenIsUnsetTrue()
	{
		// value is null on unset because isUnset is true
		$param = new TCollectionItemChangeParameter('k', null, 'old', TCollectionItemChangeParameter::IS_UNSET, true);
		$this->assertNull($param->getValue());
		$this->assertTrue($param->getIsUnset());
	}

	public function testOldValueNullIsLegitimateWhenIsNewFalse()
	{
		// oldValue can be null as a real previous value when isNew=false
		$param = new TCollectionItemChangeParameter('k', 'v', null, 0, true);
		$this->assertFalse($param->getIsNew());
		$this->assertTrue($param->offsetExists('oldValue'));  // present (isNew=false)
		$this->assertNull($param->offsetGet('oldValue'));     // legitimately null
	}
}
