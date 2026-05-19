<?php

class ActiveHyperLinkTest extends TPage
{
	public function change_text()
	{
		$this->link1->Text = "Prado framework";
	}
	
	public function change_image()
	{
		$this->link1->ImageUrl = "...";
	}
	
	public function change_target()
	{
		$this->link1->Target = "_top";
	}
	
	public function change_url()
	{
		$this->link1->NavigateUrl = "http://www.google.com";
	}
}
