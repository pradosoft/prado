<?php

class Ticket573TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket573');
		$this->assertEquals("Verifying Ticket 573", $this->title());

		$this->assertText('test1', '10.00');
	}
}
