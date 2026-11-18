<?php

namespace Prado\Test\Unit\Web\UI;

use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Test\Unit\PradoUnit;
use Prado\Web\UI\IRenderable;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TControlCollection;

class TControlCollectionTest extends \PHPUnit\Framework\TestCase
{
	public function testOwner(): void
	{
		$owner = new TControl();
		$collection = new TControlCollection($owner);
		$this->assertSame($owner, PradoUnit::invoke($collection, 'getOwner'));
	}

	public function testInsertAtAcceptsControlsStringsAndRenderables(): void
	{
		$owner = new TControl();
		$collection = new TControlCollection($owner);
		$control = new TControl();
		$renderable = $this->createMock(IRenderable::class);
		$collection->insertAt(0, $control);
		$collection->insertAt(1, 'text');
		$collection->insertAt(2, $renderable);
		$this->assertSame([$control, 'text', $renderable], $collection->toArray());
		$this->assertSame($owner, $control->getParent());
	}

	public function testInsertAtRejectsOtherItems(): void
	{
		$collection = new TControlCollection(new TControl());
		$this->expectException(TInvalidDataTypeException::class);
		$collection->insertAt(0, new \stdClass());
	}

	public function testRemoveAtDetachesAControl(): void
	{
		$owner = new TControl();
		$collection = new TControlCollection($owner);
		$control = new TControl();
		$collection->add($control);
		$collection->add('text');
		$this->assertSame('text', $collection->removeAt(1));
		$this->assertSame($control, $collection->removeAt(0));
		$this->assertNull($control->getParent());
	}
}
