<?php

class CallbackAdapterTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ControlAdapterTest");
		$this->assertContains('Control Adapter - State Tracking Tests', $this->source());

		$this->byId("{$base}button2")->click();
		$this->assertEquals('ok', $this->alertText());
		$this->acceptAlert();

		$this->byId("{$base}test6")->click();
		$this->pause(800);
		$this->byId("{$base}test7")->click();
		$this->pause(800);
		$this->byId("{$base}test8")->click();
		$this->pause(800);
		$this->byId("{$base}test9")->click();
		$this->pause(800);

		$this->byId("{$base}button1")->click();
		$this->assertEquals('haha!', $this->alertText());
		$this->acceptAlert();

		$this->byId("{$base}button2")->click();
		$this->assertEquals('ok', $this->alertText());
		$this->acceptAlert();
		$this->assertEquals('baz!', $this->alertText());
		$this->acceptAlert();
	}
/*
	function testIE()
	{
		$this->url("active-controls/index.php?page=ControlAdapterTest");
		$this->assertContains('Control Adapter - State Tracking Tests', $this->source());

		$this->byId("{$base}button2")->click();
		$this->assertEquals('ok', $this->alertText());
		$this->acceptAlert();

		$this->byId('test6')->click();
		$this->pause(800);
		$this->byId('test7')->click();
		$this->pause(800);
		$this->byId('test8')->click();
		$this->pause(800);
		$this->byId('test9')->click();
		$this->pause(800);

		$this->byId("{$base}button1")->click();
		$this->assertEquals('haha!', $this->alertText());
		$this->acceptAlert();

		//IE alerts in diffrent order
		$this->byId("{$base}button2")->click();
		$this->assertEquals('baz!', $this->alertText());
		$this->acceptAlert();
		$this->assertEquals('ok', $this->alertText());
		$this->acceptAlert();
	}
*/
}
