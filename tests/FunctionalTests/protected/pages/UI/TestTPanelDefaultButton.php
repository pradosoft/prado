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
		$this->clickAndWait("ctl0_Content_Button2");
		$this->assertTextPresent("You have clicked on 'button2'.");
	}
}
?>