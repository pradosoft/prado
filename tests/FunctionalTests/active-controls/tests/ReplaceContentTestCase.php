<?php

class ReplaceContentTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url('active-controls/index.php?page=ReplaceContentTest');
		$this->assertTextPresent('Callback Replace Content Test');

		$this->assertText("{$base}subpanel", 'Sub Panel');
		$this->assertText("{$base}panel1", "Main Panel\nSub Panel");

		$this->type("{$base}content", 'something');

		$this->click("{$base}btn_append");
		$this->pause(800);

		$this->assertText("{$base}subpanel", 'Sub Panel something');
		$this->assertText("{$base}panel1", "Main Panel\nSub Panel something");

		$this->type("{$base}content", 'more');
		$this->click("{$base}btn_prepend");
		$this->pause(800);

		$this->assertText("{$base}subpanel", 'more Sub Panel something');
		$this->assertText("{$base}panel1", "Main Panel\nmore Sub Panel something");


		$this->type("{$base}content", 'prado');
		$this->click("{$base}btn_before");
		$this->pause(800);

		$this->assertText("{$base}subpanel", 'more Sub Panel something');
		$this->assertText("{$base}panel1", "Main Panel prado\nmore Sub Panel something");

		$this->type("{$base}content", ' php ');
		$this->click("{$base}btn_after");
		$this->pause(800);

		$this->type("{$base}content", 'mauahahaha');
		$this->click("{$base}btn_replace");
		$this->pause(1000);

		$this->assertText("{$base}panel1", 'Main Panel pradomauahahaha php');
	}
/*
	function testIE()
	{
		$this->url('active-controls/index.php?page=ReplaceContentTest');
		$this->assertTextPresent('Callback Replace Content Test');

		$this->assertText("{$base}subpanel", 'Sub Panel');
		$this->assertText("{$base}panel1", 'regexp:Main Panel\s*Sub Panel');

		$this->type("{$base}content", 'something');

		$this->click('btn_append');
		$this->pause(800);

		$this->assertText("{$base}subpanel", 'Sub Panel something');
		$this->assertText("{$base}panel1", 'regexp:Main Panel\s*Sub Panel\s*something');

		$this->type("{$base}content", 'more');
		$this->click('btn_prepend');
		$this->pause(800);

		$this->assertText("{$base}subpanel", 'regexp:more\s*Sub Panel\s*something');
		$this->assertText("{$base}panel1", 'regexp:Main Panel\s*moreSub Panel\s*something');


		$this->type("{$base}content", 'prado');
		$this->click('btn_before');
		$this->pause(800);

		$this->assertText("{$base}subpanel", 'regexp:more\s*Sub Panel\s*something');
		$this->assertText("{$base}panel1", 'regexp:Main Panel\s*prado\s*more\s*Sub Panel\s*something');

		$this->type("{$base}content", ' php ');
		$this->click('btn_after');
		$this->pause(800);

		$this->type("{$base}content", 'mauahahaha');
		$this->click('btn_replace');
		$this->pause(1000);

		$this->assertText("{$base}panel1", 'Main Panel pradomauahahahaphp');
	}
*/
}
