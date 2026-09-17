<?php


namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Web\UI\ActiveControls\TActiveHiddenField;

class TActiveHiddenFieldTest extends \PHPUnit\Framework\TestCase
{
	public function testSetValue()
	{
		$field = new TActiveHiddenField();
		$field->setValue('Test');
		$this->assertEquals('Test', $field->getValue());
	}
}
