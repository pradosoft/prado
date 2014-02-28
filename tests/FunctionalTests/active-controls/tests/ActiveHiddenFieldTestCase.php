<?php

class ActiveHiddenFieldTestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$base='ctl0_Content_';
	    $this->url("active-controls/index.php?page=ActiveHiddenFieldTest");
	    $fieldEmpty = 'No longer empty';
	    $fieldUsed = 'My value';

	    $this->assertTextPresent('Value of current hidden field');
		$this->click("{$base}Button1");
		$this->pause(800);
		$this->assertText("{$base}ResponseLabel", $fieldEmpty);
		$this->click("{$base}Button2");
		$this->pause(800);
		$this->assertText("{$base}ResponseLabel", $fieldUsed);
		$this->click("{$base}Button3");
		$this->pause(800);
		$this->assertText("{$base}ResponseLabel", $fieldEmpty.'|'.$fieldUsed);
	}
}
