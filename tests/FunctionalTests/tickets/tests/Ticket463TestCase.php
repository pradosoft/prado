<?php

class Ticket463TestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$this->url('tickets/index.php?page=Ticket463');
		$this->assertTitle("Verifying Ticket 463");
		// clean the output from UTF8-encoded spaces
		// it has been noted that the date can contain characters
		// such as Narrow no-break space (U+202F) as separator between
		//the time and the AM/PM suffix
		$cleanSource = preg_replace("/\s+/u", " ", $this->source());
		$this->assertStringContainsString('May 1, 2005 at 12:00:00 AM', $cleanSource);
	}
}
