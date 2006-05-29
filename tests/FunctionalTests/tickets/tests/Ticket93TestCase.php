<?php
/*
 * Created on 13/04/2006
 *
 */

class Ticket93TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('tickets/index.php?page=Ticket93');
		$this->verifyTextPresent("ValidationGroups without any inputs with grouping");
	}
	
}
?>
