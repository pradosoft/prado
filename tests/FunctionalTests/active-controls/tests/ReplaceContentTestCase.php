<?php

class ReplaceContentTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open('active-controls/index.php?page=ReplaceContentTest');
		$this->assertTextPresent('Callback Replace Content Test');

		$this->assertText('subpanel', 'Sub Panel');
		$this->assertText('panel1', 'Main Panel Sub Panel');

		$this->type('content', 'something');

		$this->click('btn_append');
		$this->pause(500);

		$this->assertText('subpanel', 'Sub Panel something');
		$this->assertText('panel1', 'Main Panel Sub Panel something');

		$this->type('content', 'more');
		$this->click('btn_prepend');
		$this->pause(500);

		$this->assertText('subpanel', 'more Sub Panel something');
		$this->assertText('panel1', 'Main Panel more Sub Panel something');


		$this->type('content', 'prado');
		$this->click('btn_before');
		$this->pause(500);

		$this->assertText('subpanel', 'more Sub Panel something');
		$this->assertText('panel1', 'Main Panel pradomore Sub Panel something');

		$this->type('content', ' php ');
		$this->click('btn_after');
		$this->pause(500);

		$this->type('content', 'mauahahaha');
		$this->click('btn_replace');
		$this->pause(1000);

		$this->assertText('panel1', 'Main Panel pradomauahahahaphp');
	}
}

?>