<?php

namespace Prado\Test\Unit\Web\UI\WebControls;

use PHPUnit\Framework\TestCase;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\IDataRenderer;
use Prado\Web\UI\TTemplateControl;
use Prado\Web\UI\WebControls\IItemDataRenderer;
use Prado\Web\UI\WebControls\TDataGridItemRenderer;
use Prado\Web\UI\WebControls\TItemDataRenderer;
use Prado\Web\UI\WebControls\TListItemType;

class TDataGridItemRendererTest extends TestCase
{
	public function testHierarchy()
	{
		$renderer = new TDataGridItemRenderer();
		$this->assertInstanceOf(TItemDataRenderer::class, $renderer);
		$this->assertInstanceOf(TTemplateControl::class, $renderer);
		$this->assertInstanceOf(IItemDataRenderer::class, $renderer);
		$this->assertInstanceOf(IDataRenderer::class, $renderer);
	}

	public function testItemProperties()
	{
		$renderer = new TDataGridItemRenderer();
		$this->assertNull($renderer->getItemIndex());
		$this->assertNull($renderer->getItemType());
		$this->assertNull($renderer->getData());

		$renderer->setItemIndex('4');
		$renderer->setItemType('alternatingitem');
		$renderer->setData(['name' => 'value']);
		$this->assertSame(4, $renderer->getItemIndex());
		$this->assertSame(TListItemType::AlternatingItem, $renderer->getItemType());
		$this->assertSame(['name' => 'value'], $renderer->getData());
	}

	public function testItemTypeInvalid()
	{
		$renderer = new TDataGridItemRenderer();
		$this->expectException(TInvalidDataValueException::class);
		$renderer->setItemType('Unknown');
	}
}
