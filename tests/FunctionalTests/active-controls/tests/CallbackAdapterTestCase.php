<?php

class CallbackAdapterTestCase extends PradoGenericSelenium2Test
{
	public function test()
	{
		$base = 'ctl0_Content_';
		$this->url("active-controls/index.php?page=ControlAdapterTest");
		$this->assertSourceContains('Control Adapter - State Tracking Tests');

		$this->byId("{$base}button2")->click();
		$this->pause(50);
		$this->assertEquals('ok', $this->alertText());
		$this->acceptAlert();

		$this->byId("{$base}test6")->click();
		$this->pauseFairAmount();
		$this->byId("{$base}test7")->click();
		$this->pauseFairAmount();
		$this->byId("{$base}test8")->click();
		$this->pauseFairAmount();
		$this->byId("{$base}test9")->click();
		$this->pauseFairAmount();

		$this->byId("{$base}button1")->click();
		$this->pause(50);
		$this->assertEquals('haha!', $this->alertText());
		$this->acceptAlert();

		$this->byId("{$base}button2")->click();
		$this->pause(50);
		$this->assertEquals('ok', $this->alertText());
		$this->acceptAlert();
		$this->pause(500);
		$this->assertEquals('baz!', $this->alertText());
		$this->acceptAlert();
	}
	/*
		function testIE()
		{
			$this->url("active-controls/index.php?page=ControlAdapterTest");
			$this->assertSourceContains('Control Adapter - State Tracking Tests');

			$this->byId("{$base}button2")->click();
			$this->assertEquals('ok', $this->alertText());
			$this->acceptAlert();

			$this->byId('test6')->click();
			$this->pauseFairAmount();
			$this->byId('test7')->click();
			$this->pauseFairAmount();
			$this->byId('test8')->click();
			$this->pauseFairAmount();
			$this->byId('test9')->click();
			$this->pauseFairAmount();

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
