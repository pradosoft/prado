<?php

class DataGrid3TestCase extends SeleniumTestCase
{
	function test()
	{
		$this->open("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample3&amp;notheme=true", "");

		// verify the 2nd row of data
		$this->verifyTextPresent("Design Patterns: Elements of Reusable Object-Oriented Software", "");
		$this->verifyTextPresent("Addison-Wesley Professional", "");
		$this->verifyTextPresent("$47.04", "");
		$this->verifyAttribute('ctl0_body_DataGrid_ctl2_ctl7@checked','regexp:true|checked');
		$this->verifyAttribute('ctl0_body_DataGrid_ctl2_ctl7@disabled','regexp:true|disabled');
		//$this->verifyElementPresent("//img[@src='images/star5.gif']",'');

		// edit the 2nd row
		$this->clickAndWait("id=ctl0_body_DataGrid_ctl2_ctl8", "");
		$this->type("ctl0\$body\$DataGrid\$ctl2\$ctl7", "Design Pattern: Elements of Reusable Object-Oriented Software");
		$this->type("ctl0\$body\$DataGrid\$ctl2\$ctl8", "Addison Wesley Professional");
		$this->type("ctl0\$body\$DataGrid\$ctl2\$ctl9", "\$57.04");
		$this->click("//input[@name='ctl0\$body\$DataGrid\$ctl2\$ctl10']", "");
		$this->select("ctl0\$body\$DataGrid\$ctl2\$Rating", "label=1");
		$this->clickAndWait("link=Save", "");

		// verify the 2nd row is saved
		$this->verifyTextPresent("Design Pattern: Elements of Reusable Object-Oriented Software", "");
		$this->verifyTextPresent("Addison Wesley Professional", "");
		$this->verifyTextPresent("$57.04", "");
		$this->verifyAttribute('ctl0_body_DataGrid_ctl2_ctl7@checked','regexp:false|null');
		$this->verifyAttribute('ctl0_body_DataGrid_ctl2_ctl7@disabled','regexp:true|disabled');
		//$this->verifyElementPresent("//img[@src='images/star1.gif']",'');

		// verify cancel editting the 3rd row
		$this->clickAndWait("id=ctl0_body_DataGrid_ctl3_ctl8", "");
		$this->clickAndWait("link=Cancel", "");
		$this->verifyTextPresent("Design Patterns Explained : A New Perspective on Object-Oriented Design", "");

		// verify deleting
		$this->clickAndWait("id=ctl0_body_DataGrid_ctl3_ctl9", "");
		$this->verifyConfirmation("Are you sure?");
		$this->verifyTextNotPresent("Design Patterns Explained : A New Perspective on Object-Oriented Design", "");

		$this->verifyTextPresent("Extreme Programming Explained : Embrace Change",'');
		$this->chooseCancelOnNextConfirmation();
		$this->click("id=ctl0_body_DataGrid_ctl6_ctl9", "");
		$this->verifyConfirmation("Are you sure?");
		$this->verifyTextPresent("Extreme Programming Explained : Embrace Change",'');
	}
}

?>