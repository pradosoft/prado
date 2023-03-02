<?php


use Prado\Collections\TListItemCollection;
use Prado\Web\UI\WebControls\TDropDownList;

class TDropDownListTest extends PHPUnit\Framework\TestCase
{
	public function testSetDataSource()
	{
		$list = new TDropDownList();
		$data = ['a' => 1,
		  'b' => 2,
		  'c' => 3];
		$list->setDataSource($data);
		$list->dataBind();
		$items = $list->getItems();
		$this->assertTrue($items instanceof TListItemCollection);
		$expected_keys = array_keys($data);
		$i = 0;
		foreach ($items as $item) {
			$this->assertEquals($expected_keys[$i], $item->getValue());
			$this->assertEquals((string) $data[$expected_keys[$i]], $item->getText());
			$i++;
		}
	}
}
