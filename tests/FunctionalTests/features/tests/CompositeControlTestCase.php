<?php
/*
 * Created on 28/04/2006
 */

class CompositeControlTestCase extends SeleniumTestCase
{
	
	function test()
	{
		$base = "ctl0_Content_";
		$this->open("features/index.php?page=CompositeControl", "");
		$this->verifyTextPresent("Composite Control Test", "");
		$this->type("{$base}user_textbox", "Hello");
		$this->type("{$base}pass_textbox", "world");
		$this->clickAndWait("//input[@type='submit' and @value='Submit']", "");
		$this->verifyTextPresent("Result", "");
		$this->verifyTextPresent("User: Hello Pass: world", "");		
	}
	 
}

?>
