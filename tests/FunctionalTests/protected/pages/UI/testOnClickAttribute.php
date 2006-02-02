<?php

/**
 * testOnClickAttribute
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class testOnClickAttribute extends TPage
{
	function doClick()
	{
		$this->clickState->setText("Post Back clicked!");
	}
}

class OnClickAttributeTestCase extends SeleniumTestCase
{
	function test()
	{
		$page = Prado::getApplication()->getTestPage(__FILE__);
		$this->open($page);
		$this->assertTitle("Test Attributes.OnClick");
		$this->click("link=Click me");
		$this->assertConfirmation("Are you sure?");
		$this->chooseCancelOnNextConfirmation();
		$this->assertTextNotPresent("Post Back clicked!");
		$this->clickAndWait("link=Click me");
		$this->assertConfirmation("Are you sure?");
		$this->assertTextPresent("Post Back clicked!");
	}
}

?>