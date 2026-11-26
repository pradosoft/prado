<?php


namespace Prado\Test\Unit\Web\UI\ActiveControls;

use Prado\Web\UI\ActiveControls\TActiveHiddenField;
use Prado\Web\UI\TControl;

class TActiveHiddenFieldTest extends \PHPUnit\Framework\TestCase
{
	public function testSetValue()
	{
		$field = new TActiveHiddenField();
		$field->setValue('Test');
		$this->assertEquals('Test', $field->getValue());
	}

	public function testSetValueAfterPostDataSetsTheClientValueOnce()
	{
		$page = new TActiveTestPage();
		$field = $page->addControl(new TActiveHiddenField(), 'Field');
		$field->setValue('a');
		$field->setValue('a');
		$this->assertSame([['Prado.Element.j' => ['Field', 'val', ['a']]]], $page->getClientFunctions());
	}

	public function testSetValueBeforePostDataOrOutsideACallbackUpdatesNothing()
	{
		$page = new TActiveTestPage();
		$field = $page->addControl(new TActiveHiddenField(), 'Field', TControl::CS_INITIALIZED);
		$field->setValue('a');
		$this->assertSame([], $page->getClientFunctions());

		$page = new TActiveTestPage(false);
		$field = $page->addControl(new TActiveHiddenField(), 'Field');
		$field->setValue('a');
		$this->assertSame([], $page->getClientFunctions());
		$this->assertSame('a', $field->getValue());
	}
}
