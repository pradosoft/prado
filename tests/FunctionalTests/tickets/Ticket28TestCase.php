<?php

class Ticket28TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket28');
		$this->assertSourceContains('Label 1');
		$this->byLinkText("Click Me")->click();
		$this->assertSourceContains('Link Button 1 Clicked!');
	}
}
