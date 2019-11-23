<?php


use Prado\Web\UI\WebControls\TLabel;

class TLabelTest extends PHPUnit\Framework\TestCase
{
	public function testSetText()
	{
		$label = new TLabel();
		$label->setText('Test');
		$this->assertEquals('Test', $label->getText());
	}
}
