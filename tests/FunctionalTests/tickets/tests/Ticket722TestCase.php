<?php

class Ticket722TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket722');
		$this->assertEquals($this->title(), "Verifying Ticket 722");

		$label = $this->byID("{$base}InPlaceTextBox__label");
		$this->assertEquals('Editable Text', $label->text());
		$label->click();
		$this->pauseFairAmount();

		$textbox = $this->byID("{$base}InPlaceTextBox");
		$this->assertTrue($textbox->displayed());

		// calling clear() would trigger an onBlur event on the textbox
		// so we empty the textbox one char at a time
		$textbox->click();
		$this->keys(\PHPUnit\Extensions\Selenium2TestCase\Keys::END);
		for ($i = 0; $i < 13; ++$i) {
			$this->keys(\PHPUnit\Extensions\Selenium2TestCase\Keys::BACKSPACE);
		}

		$this->type($base . 'InPlaceTextBox', "Prado");
		$this->pauseFairAmount();
		$this->assertFalse($textbox->displayed());
		$this->assertEquals('Prado', $label->text());

		$this->byId("{$base}ctl0")->click();
		$this->pauseFairAmount();
		$this->assertEquals('Prado [Read Only]', $label->text());

		$label->click();
		$this->pauseFairAmount();
		$this->assertFalse($textbox->displayed());
	}
}
