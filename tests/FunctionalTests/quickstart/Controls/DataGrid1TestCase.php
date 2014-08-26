<?php

class QuickstartDataGrid1TestCase extends PradoGenericSelenium2Test
{
	function test()
	{
		$this->url("../../demos/quickstart/index.php?page=Controls.Samples.TDataGrid.Sample1&amp;notheme=true&amp;lang=en");

		// verify if all required texts are present
		$this->assertContains('id', $this->source());
		$this->assertContains('name', $this->source());
		$this->assertContains('quantity', $this->source());
		$this->assertContains('price', $this->source());
		$this->assertContains('imported', $this->source());
		$this->assertContains('ITN001', $this->source());
		$this->assertContains('Motherboard', $this->source());
		$this->assertContains('100', $this->source());
		$this->assertContains('true', $this->source());
		$this->assertContains('ITN019', $this->source());
		$this->assertContains('Speaker', $this->source());
		$this->assertContains('35', $this->source());
		$this->assertContains('65', $this->source());
		$this->assertContains('false', $this->source());

		// verify specific table tags
		$this->assertElementPresent("ctl0_body_DataGrid");
		$this->assertAttribute("ctl0_body_DataGrid@cellpadding","2");
	}
}
