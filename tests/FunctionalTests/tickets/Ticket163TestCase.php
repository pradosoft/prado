<?php

class Ticket163TestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket163');
		$this->assertSourceContains('100,00&nbsp;kr');
		$this->assertSourceContains('0,00&nbsp;kr');
		$this->assertSourceContains('−100,00&nbsp;kr');
	}
}