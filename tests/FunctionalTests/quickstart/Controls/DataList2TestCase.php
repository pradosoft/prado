<?php

class QuickstartDataList2TestCase extends PradoDemosSelenium2Test
{
	public function test()
	{
		$this->url("quickstart/index.php?page=Controls.Samples.TDataList.Sample2&amp;notheme=true&amp;lang=en");

		// verify initial presentation
		$this->assertSourceContains("Motherboard");
		$this->assertSourceContains("Monitor");

		// verify selecting an item
		$this->byLinkText("ITN003")->click();
		$this->assertSourceContains("Quantity");
		$this->assertSourceContains("Price");
		$this->assertSourceContains("\$80");
		$this->byLinkText("ITN005")->click();
		$this->assertSourceContains("\$150");

		// verify editting an item
		$this->byId("ctl0_body_DataList_ctl5_ctl0")->click();
		$this->pause(50);
		$this->type("ctl0\$body\$DataList\$ctl5\$ProductQuantity", "11");
		$this->type("ctl0\$body\$DataList\$ctl5\$ProductPrice", "140.99");
		$this->byXPath("//input[@name='ctl0\$body\$DataList\$ctl5\$ProductImported']")->click();
		$this->byLinkText("Save")->click();
		$this->pause(50);

		// verify item is saved
		$this->byLinkText("ITN005")->click();
		$this->assertSourceContains("\$140.99");
		$this->assertSourceContains("11");

		// verify editting another item
		$this->byId("ctl0_body_DataList_ctl3_ctl1")->click();
		$this->pause(50);
		$this->type("ctl0\$body\$DataList\$ctl3\$ProductName", "Hard Drive");
		$this->type("ctl0\$body\$DataList\$ctl3\$ProductQuantity", "23");
		$this->byXPath("//input[@name='ctl0\$body\$DataList\$ctl3\$ProductImported']")->click();
		$this->byLinkText("Cancel")->click();
		$this->pause(50);

		// verify item is canceled
		$this->byLinkText("ITN003")->click();
		$this->assertSourceContains("2");
		$this->assertSourceContains("Harddrive");

		// verify item deletion
		$this->byId("ctl0_body_DataList_ctl3_ctl1")->click();
		$this->pause(50);

		$this->assertEquals("Are you sure?", $this->alertText());
		$this->acceptAlert();

		$this->pause(300); // wait for reload
		$this->byId("ctl0_body_DataList_ctl5_ctl2")->click();
		$this->pause(50);

		$this->assertEquals("Are you sure?", $this->alertText());
		$this->dismissAlert();

		$this->assertSourceContains("Motherboard");
		$this->assertSourceContains("CPU");
		$this->assertSourceNotContains("Harddrive");
		$this->assertSourceContains("Sound card");
		$this->assertSourceContains("Video card");
		$this->assertSourceContains("Keyboard");
		$this->assertSourceContains("Monitor");
	}
}
