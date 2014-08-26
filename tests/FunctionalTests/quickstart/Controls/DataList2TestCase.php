<?php

class QuickstartDataList2TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataList.Sample2&amp;notheme=true&amp;lang=en");

		// verify initial presentation
		$this->assertContains("Motherboard", $this->source());
		$this->assertContains("Monitor", $this->source());

		// verify selecting an item
		$this->byLinkText("ITN003")->click();
		$this->assertContains("Quantity", $this->source());
		$this->assertContains("Price", $this->source());
		$this->assertContains("\$80", $this->source());
		$this->byLinkText("ITN005")->click();
		$this->assertContains("\$150", $this->source());

		// verify editting an item
		$this->byId("ctl0_body_DataList_ctl5_ctl0")->click();
		$this->type("ctl0\$body\$DataList\$ctl5\$ProductQuantity", "11");
		$this->type("ctl0\$body\$DataList\$ctl5\$ProductPrice", "140.99");
		$this->byXPath("//input[@name='ctl0\$body\$DataList\$ctl5\$ProductImported']")->click();
		$this->byLinkText("Save")->click();

		// verify item is saved
		$this->byLinkText("ITN005")->click();
		$this->assertContains("\$140.99", $this->source());
		$this->assertContains("11", $this->source());

		// verify editting another item
		$this->byId("ctl0_body_DataList_ctl3_ctl1")->click();
		$this->type("ctl0\$body\$DataList\$ctl3\$ProductName", "Hard Drive");
		$this->type("ctl0\$body\$DataList\$ctl3\$ProductQuantity", "23");
		$this->byXPath("//input[@name='ctl0\$body\$DataList\$ctl3\$ProductImported']")->click();
		$this->byLinkText("Cancel")->click();

		// verify item is canceled
		$this->byLinkText("ITN003")->click();
		$this->assertContains("2", $this->source());
		$this->assertContains("Harddrive", $this->source());

		// verify item deletion
		$this->byId("ctl0_body_DataList_ctl3_ctl1")->click();

		$this->assertEquals("Are you sure?", $this->alertText());
		$this->acceptAlert();

		$this->pause(300); // wait for reload
		$this->byId("ctl0_body_DataList_ctl5_ctl2")->click();

		$this->assertEquals("Are you sure?", $this->alertText());
		$this->dismissAlert();

		$this->assertContains("Motherboard", $this->source());
		$this->assertContains("CPU", $this->source());
		$this->assertNotContains("Harddrive", $this->source());
		$this->assertContains("Sound card", $this->source());
		$this->assertContains("Video card", $this->source());
		$this->assertContains("Keyboard", $this->source());
		$this->assertContains("Monitor", $this->source());
	}
}
