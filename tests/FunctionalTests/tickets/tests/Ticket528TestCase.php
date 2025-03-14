<?php

class Ticket528TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('tickets/index.php?page=Ticket528');
		$this->assertTitle("Verifying Ticket 528");

		$this->select("{$base}DDropTurno", "Tarde");

		$this->assertValue("{$base}Codigo", "T");
		$this->assertValue("{$base}Descricao", "Tarde");

		$this->select("{$base}DDropTurno", "Manhã");

		$this->assertValue("{$base}Codigo", "M");
		$this->assertValue("{$base}Descricao", "Manhã");

		$this->select("{$base}DDropTurno", "Noite");

		$this->assertValue("{$base}Codigo", "N");
		$this->assertValue("{$base}Descricao", "Noite");
	}
}
