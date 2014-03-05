<?php
class Ticket722TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket722');
		$this->assertEquals($this->title(), "Verifying Ticket 722");

		$this->assertText($base.'InPlaceTextBox__label', 'Editable Text');
		$this->byId($base.'InPlaceTextBox__label')->click();
		$this->pause(800);
		$this->assertVisible($base.'InPlaceTextBox');
		$this->type($base.'InPlaceTextBox',"Prado");
		$this->pause(800);
		$this->assertNotVisible($base.'InPlaceTextBox');
		$this->assertText($base.'InPlaceTextBox__label', 'Prado');
		$this->byId($base.'ctl0')->click();
		$this->pause(800);
		$this->assertText($base.'InPlaceTextBox__label', 'Prado [Read Only]');
		$this->byId($base.'InPlaceTextBox__label')->click();
		$this->pause(800);
		$this->assertNotVisible($base.'InPlaceTextBox');

	}

}