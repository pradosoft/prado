<?php

class ActiveHyperLinkTest extends TPage
{
	function change_text()
	{
		$this->link1->Text = "Pradosoft.com";
	}
	
	function change_image()
	{
		$this->link1->ImageUrl = "...";
	}
	
	function change_target()
	{
		$this->link1->Target = "_top";
	}
	
	function change_url()
	{
		$this->link1->NavigateUrl = "http://www.xlab6.com";
	}
}

?>