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

	// ================================================================================
	// Text / Value
	// ================================================================================

	// ================================================================================
	// Enabled / Selected
	// ================================================================================

	// ================================================================================
	// Attributes
	// ================================================================================

	// ================================================================================
	// Serialization
	// ================================================================================

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
