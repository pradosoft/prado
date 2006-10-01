<?php

class ReplaceContentTestCase extends SeleniumTestCase
{
	function test()
	{
		$this->skipBrowsers(self::INTERNET_EXPLORER);

		$this->open('active-controls/index.php?page=ReplaceContentTest');
		$this->assertTextPresent('Callback Replace Content Test');

		$this->assertText('subpanel', 'Sub Panel');
		$this->assertText('panel1', 'Main Panel Sub Panel');

		$this->type('content', 'something');

		$this->click('btn_append');
		$this->pause(800);

		$this->assertText('subpanel', 'Sub Panel something');
		$this->assertText('panel1', 'Main Panel Sub Panel something');

		$this->type('content', 'more');
		$this->click('btn_prepend');
		$this->pause(800);

		$this->assertText('subpanel', 'more Sub Panel something');
		$this->assertText('panel1', 'Main Panel more Sub Panel something');


		$this->type('content', 'prado');
		$this->click('btn_before');
		$this->pause(800);

		$this->assertText('subpanel', 'more Sub Panel something');
		$this->assertText('panel1', 'Main Panel pradomore Sub Panel something');

		$this->type('content', ' php ');
		$this->click('btn_after');
		$this->pause(800);

		$this->type('content', 'mauahahaha');
		$this->click('btn_replace');
		$this->pause(1000);

		$this->assertText('panel1', 'Main Panel pradomauahahahaphp');
	}

	function testIE()
	{
		$this->targetBrowsers(self::INTERNET_EXPLORER);

		$this->open('active-controls/index.php?page=ReplaceContentTest');
		$this->assertTextPresent('Callback Replace Content Test');

		$this->assertText('subpanel', 'Sub Panel');
		$this->assertText('panel1', 'regexp:Main Panel\s*Sub Panel');

		$this->type('content', 'something');

		$this->click('btn_append');
		$this->pause(800);

		$this->assertText('subpanel', 'Sub Panel something');
		$this->assertText('panel1', 'regexp:Main Panel\s*Sub Panel\s*something');

		$this->type('content', 'more');
		$this->click('btn_prepend');
		$this->pause(800);

		$this->assertText('subpanel', 'regexp:more\s*Sub Panel\s*something');
		$this->assertText('panel1', 'regexp:Main Panel\s*moreSub Panel\s*something');


		$this->type('content', 'prado');
		$this->click('btn_before');
		$this->pause(800);

		$this->assertText('subpanel', 'regexp:more\s*Sub Panel\s*something');
		$this->assertText('panel1', 'regexp:Main Panel\s*prado\s*more\s*Sub Panel\s*something');

		$this->type('content', ' php ');
		$this->click('btn_after');
		$this->pause(800);

		$this->type('content', 'mauahahaha');
		$this->click('btn_replace');
		$this->pause(1000);

		$this->assertText('panel1', 'Main Panel pradomauahahahaphp');
	}

}

?>