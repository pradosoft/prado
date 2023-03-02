<?php

class Ticket163TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket163');
		$this->assertStringContainsString('kr&nbsp;100,00', $this->source());
		$this->assertStringContainsString('kr&nbsp;0,00', $this->source());
		$this->assertStringContainsString('kr&nbsp;−100,00', $this->source());
	}
}