<?php

class ReplaceContentTestCase extends \Prado\Tests\PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url('active-controls/index.php?page=ReplaceContentTest');
		$this->assertSourceContains('Callback Replace Content Test');

		$this->assertText("{$base}subpanel", 'Sub Panel');
		$this->assertText("{$base}panel1", "Main Panel\nSub Panel");

		$this->type("{$base}content", 'something');

		$this->byId("{$base}btn_append")->click();

		$this->assertText("{$base}subpanel", 'Sub Panel something');
		$this->assertText("{$base}panel1", "Main Panel\nSub Panel something");

		$this->type("{$base}content", 'more');
		$this->byId("{$base}btn_prepend")->click();

		$this->assertText("{$base}subpanel", 'more Sub Panel something');
		$this->assertText("{$base}panel1", "Main Panel\nmore Sub Panel something");


		$this->type("{$base}content", 'prado');
		$this->byId("{$base}btn_before")->click();

		$this->assertText("{$base}subpanel", 'more Sub Panel something');
		$this->assertText("{$base}panel1", "Main Panel prado\nmore Sub Panel something");

		$this->type("{$base}content", ' php ');
		$this->byId("{$base}btn_after")->click();

		$this->type("{$base}content", 'mauahahaha');
		$this->byId("{$base}btn_replace")->click();
		$this->pause(1000);

		$this->assertText("{$base}panel1", 'Main Panel pradomauahahaha php');
	}
	/*
		function testIE()
		{
			$this->url('active-controls/index.php?page=ReplaceContentTest');
			$this->assertSourceContains('Callback Replace Content Test');

			$this->assertText("{$base}subpanel", 'Sub Panel');
			$this->assertText("{$base}panel1", 'regexp:Main Panel\s*Sub Panel');

			$this->type("{$base}content", 'something');

			$this->byId('btn_append')->click();

			$this->assertText("{$base}subpanel", 'Sub Panel something');
			$this->assertText("{$base}panel1", 'regexp:Main Panel\s*Sub Panel\s*something');

			$this->type("{$base}content", 'more');
			$this->byId('btn_prepend')->click();

			$this->assertText("{$base}subpanel", 'regexp:more\s*Sub Panel\s*something');
			$this->assertText("{$base}panel1", 'regexp:Main Panel\s*moreSub Panel\s*something');


			$this->type("{$base}content", 'prado');
			$this->byId('btn_before')->click();

			$this->assertText("{$base}subpanel", 'regexp:more\s*Sub Panel\s*something');
			$this->assertText("{$base}panel1", 'regexp:Main Panel\s*prado\s*more\s*Sub Panel\s*something');

			$this->type("{$base}content", ' php ');
			$this->byId('btn_after')->click();

			$this->type("{$base}content", 'mauahahaha');
			$this->byId('btn_replace')->click();
			$this->pause(1000);

			$this->assertText("{$base}panel1", 'Main Panel pradomauahahahaphp');
		}
	*/
}
