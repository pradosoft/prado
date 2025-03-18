<?php

class Ticket573TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket573');
		$this->assertTitle("Verifying Ticket 573");

		$this->assertText('test1', '10.00');
	}
}
