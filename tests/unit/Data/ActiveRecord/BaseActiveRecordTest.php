<?php

use Prado\Data\ActiveRecord\TActiveRecord;

class BaseRecordTest extends TActiveRecord
{
}

/**
 * Fixture with mixed-case public properties to verify that setColumnValue
 * normalises driver-returned uppercase column names to any property spelling.
 *
 * Property spellings are deliberately varied to cover the three cases:
 *   - all lowercase  ($score)
 *   - leading capital ($Username)
 *   - camelCase       ($fullName)
 */
class MixedCaseRecord extends TActiveRecord
{
	public $Username;       // leading capital
	public $score    = 0;   // all lowercase
	public $fullName;       // camelCase

	const TABLE = 'mixed_case_fixture';

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}

/**
 * Fixture with an explicit COLUMN_MAPPING entry. Used to assert that an
 * explicit mapping takes precedence over the automatic lcPropertyMap lookup.
 */
class MappedRecord extends TActiveRecord
{
	public $internalName; // DB column 'db_col' mapped here

	const TABLE = 'mapped_fixture';

	public static $COLUMN_MAPPING = ['db_col' => 'internalName'];

	public static function finder($className = __CLASS__)
	{
		return parent::finder($className);
	}
}

class BaseActiveRecordTest extends PHPUnit\Framework\TestCase
{
	public function test_finder_returns_same_instance(): void
	{
		$obj1 = TActiveRecord::finder('BaseRecordTest');
		$obj2 = TActiveRecord::finder('BaseRecordTest');
		$this->assertSame($obj1, $obj2);
	}

	// -----------------------------------------------------------------------
	// setColumnValue — exact-match (all drivers, baseline)
	// -----------------------------------------------------------------------

	public function test_setColumnValue_exact_lowercase_match(): void
	{
		$record = new MixedCaseRecord();
		$record->setColumnValue('score', 99);
		$this->assertSame(99, $record->score);
	}

	public function test_setColumnValue_exact_property_spelling_match(): void
	{
		// Column name matches the property's actual capitalisation exactly.
		$record = new MixedCaseRecord();
		$record->setColumnValue('Username', 'alice');
		$this->assertSame('alice', $record->Username);
	}

	// -----------------------------------------------------------------------
	// setColumnValue — uppercase normalisation (Firebird, Oracle)
	//
	// Databases that return identifiers in uppercase pass column names like
	// 'SCORE' or 'USERNAME'. setColumnValue must map them to the PHP property
	// regardless of the property's actual capitalisation.
	// -----------------------------------------------------------------------

	public function test_setColumnValue_uppercase_maps_to_lowercase_property(): void
	{
		$record = new MixedCaseRecord();
		$record->setColumnValue('SCORE', 42);
		$this->assertSame(42, $record->score);
	}

	public function test_setColumnValue_uppercase_maps_to_leading_capital_property(): void
	{
		// Property is 'Username' (capital U); DB returns 'USERNAME'.
		$record = new MixedCaseRecord();
		$record->setColumnValue('USERNAME', 'bob');
		$this->assertSame('bob', $record->Username);
	}

	public function test_setColumnValue_uppercase_maps_to_camelcase_property(): void
	{
		// Property is 'fullName'; DB returns 'FULLNAME'.
		$record = new MixedCaseRecord();
		$record->setColumnValue('FULLNAME', 'Alice Smith');
		$this->assertSame('Alice Smith', $record->fullName);
	}

	// -----------------------------------------------------------------------
	// copyFrom — uppercase keys (Firebird, Oracle)
	// -----------------------------------------------------------------------

	public function test_copyFrom_with_uppercase_keys_populates_all_properties(): void
	{
		$record = new MixedCaseRecord();
		$record->copyFrom([
			'USERNAME' => 'carol',
			'SCORE'    => 7,
			'FULLNAME' => 'Carol Jones',
		]);
		$this->assertSame('carol', $record->Username);
		$this->assertSame(7, $record->score);
		$this->assertSame('Carol Jones', $record->fullName);
	}

	// -----------------------------------------------------------------------
	// COLUMN_MAPPING takes precedence over automatic normalisation
	// -----------------------------------------------------------------------

	public function test_setColumnValue_column_mapping_takes_precedence_over_auto_map(): void
	{
		// MappedRecord maps 'db_col' → 'internalName' via COLUMN_MAPPING.
		// The lcPropertyMap has no 'db_col' entry (the property is 'internalName'),
		// so only the explicit mapping can route this assignment correctly.
		$record = new MappedRecord();
		$record->setColumnValue('db_col', 'mapped_value');
		$this->assertSame('mapped_value', $record->internalName);
	}
}
