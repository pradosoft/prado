<?php

class CallbackAdapterTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
		$this->url("active-controls/index.php?page=ControlAdapterTest");
		$this->assertTextPresent('Control Adapter - State Tracking Tests');

		$this->click("{$base}button2");
		$this->assertAlert('ok');

		$this->click("{$base}test6");
		$this->pause(800);
		$this->click("{$base}test7");
		$this->pause(800);
		$this->click("{$base}test8");
		$this->pause(800);
		$this->click("{$base}test9");
		$this->pause(800);

		$this->click("{$base}button1");
		$this->assertAlert('haha!');

		$this->click("{$base}button2");
		$this->assertAlert('ok');
		$this->assertAlert('baz!');

	}
/*
	function testIE()
	{
		$this->url("active-controls/index.php?page=ControlAdapterTest");
		$this->assertTextPresent('Control Adapter - State Tracking Tests');

		$this->click("{$base}button2");
		$this->assertAlert('ok');

		$this->click('test6');
		$this->pause(800);
		$this->click('test7');
		$this->pause(800);
		$this->click('test8');
		$this->pause(800);
		$this->click('test9');
		$this->pause(800);

		$this->click("{$base}button1");
		$this->assertAlert('haha!');

		//IE alerts in diffrent order
		$this->click("{$base}button2");
		$this->assertAlert('baz!');
		$this->assertAlert('ok');
	}
*/
}
