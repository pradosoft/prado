<?php

class TestTPanelDefaultButton extends TPage
{
	public function buttonClicked($sender,$param)
	{
		$this->Result->Text="You have clicked on '$sender->Text'.";
	}
}

/**
 * 
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class DefaultButtonTest extends SeleniumTestCase
{
	function setup()
	{
		$page = Prado::getApplication()->getTestPage(__FILE__);
		$this->open($page);
	}

	function testClick()
	{
		$this->verifyTitle("Test DefaultButton On Panel", "");
		$this->assertTextNotPresent("You have clicked on", "");
		$this->clickAndWait("link=button 3", "");
		$this->verifyTextPresent("You have clicked on 'button 3'.", "");
		$this->clickAndWait("//input[@type='submit' and @value='button1']", "");
		$this->verifyTextPresent("You have clicked on 'button1'. ", "");
		$this->clickAndWait("//input[@type='submit' and @value='button2']", "");
		$this->verifyTextPresent("You have clicked on 'button2'. ", "");
		$this->clickAndWait("link=button 3", "");
		$this->verifyTextPresent("You have clicked on 'button 3'. ", "");
		$this->click("ctl0_Content_check1", "");
		$this->clickAndWait("//input[@type='submit' and @value='button2']", "");
		$this->verifyTextPresent("You have clicked on 'button2'. ", "");
	}
}
?>