<?php

class Ticket28TestCase extends PradoGenericSelenium2Test
{

	function test()
	{
		$this->url('tickets/index.php?page=Ticket28');
		$this->assertContains('Label 1', $this->source());
		$this->byLinkText("Click Me")->click();
		$this->assertContains('Link Button 1 Clicked!', $this->source());
	}
}
