<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Collections\TAttributeCollection;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\WebControls\TListItem;

class TListItemTest extends TestCase
{
	// ================================================================================
	// Construction
	// ================================================================================

	public function testConstructorDefaults()
	{
		$item = new TListItem();
		$this->assertSame('', $item->getText());
		$this->assertSame('', $item->getValue());
		$this->assertTrue($item->getEnabled());
		$this->assertFalse($item->getSelected());
		$this->assertFalse($item->getHasAttributes());
	}

	public function testConstructorArguments()
	{
		$item = new TListItem('Text', 'val', 'false', 'true');
		$this->assertSame('Text', $item->getText());
		$this->assertSame('val', $item->getValue());
		$this->assertFalse($item->getEnabled());
		$this->assertTrue($item->getSelected());
	}

	// ================================================================================
	// Text / Value
	// ================================================================================

	public function testTextFallsBackToValue()
	{
		$item = new TListItem('', 'val');
		$this->assertSame('val', $item->getText());
		$this->assertSame('val', $item->getValue());
	}

	public function testValueFallsBackToText()
	{
		$item = new TListItem('Text');
		$this->assertSame('Text', $item->getText());
		$this->assertSame('Text', $item->getValue());
	}

	public function testSetTextAndValueEnsureString()
	{
		$item = new TListItem();
		$item->setText(12);
		$item->setValue(3.5);
		$this->assertSame('12', $item->getText());
		$this->assertSame('3.5', $item->getValue());
	}

	// ================================================================================
	// Enabled / Selected
	// ================================================================================

	public function testEnabled()
	{
		$item = new TListItem();
		$item->setEnabled('false');
		$this->assertFalse($item->getEnabled());
		$item->setEnabled(1);
		$this->assertTrue($item->getEnabled());
	}

	public function testSelected()
	{
		$item = new TListItem();
		$item->setSelected('true');
		$this->assertTrue($item->getSelected());
		$item->setSelected(0);
		$this->assertFalse($item->getSelected());
	}

	// ================================================================================
	// Attributes
	// ================================================================================

	public function testAttributeAccessorsWithoutCollection()
	{
		$item = new TListItem();
		$this->assertFalse($item->getHasAttributes());
		$this->assertFalse($item->hasAttribute('class'));
		$this->assertNull($item->getAttribute('class'));
		$this->assertNull($item->removeAttribute('class'));
		$this->assertNull(PradoUnit::getProp($item, '_attributes'));
	}

	public function testGetAttributesCreatesCollectionOnce()
	{
		$item = new TListItem();
		$attributes = $item->getAttributes();
		$this->assertInstanceOf(TAttributeCollection::class, $attributes);
		$this->assertSame($attributes, $item->getAttributes());
		$this->assertFalse($item->getHasAttributes());
	}

	public function testSetGetRemoveAttribute()
	{
		$item = new TListItem();
		$item->setAttribute('class', 'hot');
		$this->assertTrue($item->getHasAttributes());
		$this->assertTrue($item->hasAttribute('class'));
		$this->assertFalse($item->hasAttribute('style'));
		$this->assertSame('hot', $item->getAttribute('class'));
		$this->assertNull($item->getAttribute('style'));

		$this->assertSame('hot', $item->removeAttribute('class'));
		$this->assertFalse($item->hasAttribute('class'));
		$this->assertFalse($item->getHasAttributes());
	}

	// ================================================================================
	// Serialization
	// ================================================================================

	public function testSleepZapsDefaultProperties()
	{
		$props = (new TListItem())->__sleep();
		$this->assertNotContains("\0*\0_attributes", $props);
		$this->assertNotContains("\0*\0_text", $props);
		$this->assertNotContains("\0*\0_value", $props);
		$this->assertNotContains("\0*\0_enabled", $props);
		$this->assertNotContains("\0*\0_selected", $props);
	}

	public function testSleepKeepsNonDefaultProperties()
	{
		$item = new TListItem('Text', 'val', false, true);
		$item->setAttribute('class', 'hot');
		$props = $item->__sleep();
		$this->assertContains("\0*\0_attributes", $props);
		$this->assertContains("\0*\0_text", $props);
		$this->assertContains("\0*\0_value", $props);
		$this->assertContains("\0*\0_enabled", $props);
		$this->assertContains("\0*\0_selected", $props);
	}

	public function testCloneCopiesAttributes()
	{
		$item = new TListItem('Text', 'val');
		$item->setAttribute('class', 'hot');
		$copy = clone $item;
		$copy->setAttribute('class', 'cold');
		$this->assertSame('hot', $item->getAttribute('class'));
		$this->assertSame('cold', $copy->getAttribute('class'));
		$this->assertFalse((clone new TListItem())->getHasAttributes());
	}

		public function testSerializeRoundTrip()
	{
		$item = new TListItem('Text', 'val', false, true);
		$item->setAttribute('class', 'hot');
		$copy = unserialize(serialize($item));
		$this->assertSame('Text', $copy->getText());
		$this->assertSame('val', $copy->getValue());
		$this->assertFalse($copy->getEnabled());
		$this->assertTrue($copy->getSelected());
		$this->assertSame('hot', $copy->getAttribute('class'));

		$plain = unserialize(serialize(new TListItem()));
		$this->assertSame('', $plain->getText());
		$this->assertTrue($plain->getEnabled());
		$this->assertFalse($plain->getSelected());
		$this->assertFalse($plain->getHasAttributes());
	}
}
