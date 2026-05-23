<?php

require_once(__DIR__ . '/../../../PradoUnit.php');

use Prado\Data\SqlMap\Configuration\TParameterProperty;
use Prado\Data\SqlMap\Configuration\TSubMap;

class TParameterPropertyTest extends PHPUnit\Framework\TestCase
{
	// -------  TParameterProperty  -------

	public function test_all_properties_default_to_null()
	{
		$prop = new TParameterProperty();
		$this->assertNull($prop->getTypeHandler());
		$this->assertNull($prop->getType());
		$this->assertNull($prop->getColumn());
		$this->assertNull($prop->getDbType());
		$this->assertNull($prop->getProperty());
		$this->assertNull($prop->getNullValue());
	}

	public function test_set_type_handler()
	{
		$prop = new TParameterProperty();
		$prop->setTypeHandler('MyHandler');
		$this->assertSame('MyHandler', $prop->getTypeHandler());
	}

	public function test_set_type()
	{
		$prop = new TParameterProperty();
		$prop->setType('string');
		$this->assertSame('string', $prop->getType());
	}

	public function test_set_column()
	{
		$prop = new TParameterProperty();
		$prop->setColumn('my_column');
		$this->assertSame('my_column', $prop->getColumn());
	}

	public function test_set_db_type()
	{
		$prop = new TParameterProperty();
		$prop->setDbType('Varchar');
		$this->assertSame('Varchar', $prop->getDbType());
	}

	public function test_set_property()
	{
		$prop = new TParameterProperty();
		$prop->setProperty('myProp');
		$this->assertSame('myProp', $prop->getProperty());
	}

	public function test_set_null_value()
	{
		$prop = new TParameterProperty();
		$prop->setNullValue('N/A');
		$this->assertSame('N/A', $prop->getNullValue());
	}

	public function test_null_value_can_be_integer()
	{
		$prop = new TParameterProperty();
		$prop->setNullValue(-9999);
		$this->assertSame(-9999, $prop->getNullValue());
	}

	/**
	 * Test that null properties are excluded from sleep (via __sleep() which calls
	 * _getZappableSleepProps internally).  When all properties are null the serialized
	 * size should be smaller than when properties are set, because null props are zapped.
	 */
	public function test_zappable_null_props_excluded_from_sleep()
	{
		$allNull = new TParameterProperty();
		$allSet = new TParameterProperty();
		$allSet->setTypeHandler('H');
		$allSet->setType('string');
		$allSet->setColumn('col');
		$allSet->setDbType('Varchar');
		$allSet->setProperty('p');
		$allSet->setNullValue('N/A');

		$nullKeys = $allNull->__sleep();
		$setKeys = $allSet->__sleep();

		// All-set should include more keys than all-null
		$this->assertGreaterThan(count($nullKeys), count($setKeys));
	}

	public function test_zappable_null_props_not_in_sleep_list()
	{
		$prop = new TParameterProperty();
		// All null — private props should be absent from __sleep()
		$sleepKeys = $prop->__sleep();

		$cn = TParameterProperty::class;
		$this->assertNotContains("\0$cn\0_typeHandler", $sleepKeys);
		$this->assertNotContains("\0$cn\0_type", $sleepKeys);
		$this->assertNotContains("\0$cn\0_column", $sleepKeys);
		$this->assertNotContains("\0$cn\0_dbType", $sleepKeys);
		$this->assertNotContains("\0$cn\0_property", $sleepKeys);
		$this->assertNotContains("\0$cn\0_nullValue", $sleepKeys);
	}

	public function test_zappable_set_props_in_sleep_list()
	{
		$prop = new TParameterProperty();
		$prop->setProperty('myProp');
		$prop->setType('string');

		$sleepKeys = $prop->__sleep();

		$cn = TParameterProperty::class;
		// Set properties should appear in sleep keys
		$this->assertContains("\0$cn\0_property", $sleepKeys);
		$this->assertContains("\0$cn\0_type", $sleepKeys);

		// Null properties should NOT appear
		$this->assertNotContains("\0$cn\0_typeHandler", $sleepKeys);
		$this->assertNotContains("\0$cn\0_column", $sleepKeys);
		$this->assertNotContains("\0$cn\0_dbType", $sleepKeys);
		$this->assertNotContains("\0$cn\0_nullValue", $sleepKeys);
	}

	public function test_serialization_round_trip()
	{
		$prop = new TParameterProperty();
		$prop->setProperty('age');
		$prop->setType('integer');

		$serialized = serialize($prop);
		/** @var TParameterProperty $restored */
		$restored = unserialize($serialized);
		$this->assertSame('age', $restored->getProperty());
		$this->assertSame('integer', $restored->getType());
		$this->assertNull($restored->getTypeHandler());
	}

	public function test_is_tcomponent()
	{
		$prop = new TParameterProperty();
		$this->assertInstanceOf(\Prado\TComponent::class, $prop);
	}

	// -------  TSubMap  -------

	public function test_submap_defaults_to_null()
	{
		$sub = new TSubMap();
		$this->assertNull($sub->getValue());
		$this->assertNull($sub->getResultMapping());
	}

	public function test_submap_set_value()
	{
		$sub = new TSubMap();
		$sub->setValue('Gold');
		$this->assertSame('Gold', $sub->getValue());
	}

	public function test_submap_set_result_mapping()
	{
		$sub = new TSubMap();
		$sub->setResultMapping('GoldResultMap');
		$this->assertSame('GoldResultMap', $sub->getResultMapping());
	}

	public function test_submap_is_tcomponent()
	{
		$sub = new TSubMap();
		$this->assertInstanceOf(\Prado\TComponent::class, $sub);
	}
}
