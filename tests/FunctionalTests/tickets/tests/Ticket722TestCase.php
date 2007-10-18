<?php
class Ticket722TestCase extends SeleniumTestCase
{
	function test()
	{
		$base = 'ctl0_Content_';
		$this->open('tickets/index.php?page=Ticket722');
		$this->assertTitle("Verifying Ticket 722");
		
		$this->assertText($base.'InPlaceTextBox__label', 'Editable Text');
		$this->click($base.'InPlaceTextBox__label');
		$this->pause(800);
		$this->assertVisible($base.'InPlaceTextBox');
		$this->type($base.'InPlaceTextBox',"Prado");
		$this->fireEvent($base.'InPlaceTextBox', 'blur'); // Release textbox
		$this->pause(800);
		$this->assertNotVisible($base.'InPlaceTextBox');
		$this->assertText($base.'InPlaceTextBox__label', 'Prado');
		$this->click($base.'ctl0');
		$this->pause(800);
		$this->assertText($base.'InPlaceTextBox__label', 'Prado [Read Only]');
		$this->click($base.'InPlaceTextBox__label');
		$this->pause(800);
		$this->assertNotVisible($base.'InPlaceTextBox');
		
	}

}
?>