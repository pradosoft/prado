<?php

class ActiveHiddenFieldTestCase extends SeleniumTestCase
{
	function test()
	{
	    $this->open("active-controls/index.php?page=ActiveHiddenFieldTest");
	    $fieldEmpty = 'No longer empty';
	    $fieldUsed = 'My value';
	    
	    $this->verifyTextPresent('Value of current hidden field');
		$this->click('Button1');
		$this->pause(800);
		$this->assertText('ResponseLabel', $fieldEmpty);
		$this->click('Button2');
		$this->pause(800);
		$this->assertText('ResponseLabel', $fieldUsed);
		$this->click('Button3');
		$this->pause(800);
		$this->assertText('ResponseLabel', $fieldEmpty.'|'.$fieldUsed);
	}
}

?>