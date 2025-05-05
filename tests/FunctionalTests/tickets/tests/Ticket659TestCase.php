<?php

class Ticket659TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		// Normal component (working)
		$this->url('tickets/index.php?page=ToggleTest');
		$this->assertText("{$base}lbl", "Down");
		$this->byId("{$base}btn")->click();
		$this->assertText("{$base}lbl", "Up");
		// Extended component (not working)
		$this->url('tickets/index.php?page=Ticket659');
		$this->assertText("{$base}lbl", "Down");
		$this->byId("{$base}btn")->click();
		$this->assertText("{$base}lbl", "Up");
	}
}
