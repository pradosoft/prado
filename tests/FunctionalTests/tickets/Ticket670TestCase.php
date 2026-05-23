<?php

class Ticket670TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket670');
		$this->assertTitle("Verifying Ticket 670");

		$this->type($base . "datePicker", '07-07-2003');
		//$this->assertText($base."datePicker",'07-07-2003');
		$this->byId($base . "datePickerbutton")->click();
		$this->byId($base . "ok")->click();
		$this->assertText($base . "lbl", '07-07-2007');
	}
}
