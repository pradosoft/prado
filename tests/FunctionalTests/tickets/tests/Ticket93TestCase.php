<?php
/*
 * Created on 13/04/2006
 *
 */

class Ticket93TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket93');
		$this->assertSourceContains("ValidationGroups without any inputs with grouping");
	}
}
