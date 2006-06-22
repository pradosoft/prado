<?php

class ControlAdapterTest extends TPage
{
	function change_enabled()
	{
		$this->button1->Enabled = !$this->button1->Enabled;
	}
	
	function change_visible()
	{
		$this->button1->Visible = !$this->button1->Visible;
	}
	
	function change_tooltip()
	{
		$this->button1->ToolTip = "hello world";
	}
	
	function change_tabindex()
	{
		$this->button1->tabIndex = 10;
	}
	
	function change_accesskey()
	{
		$this->button1->accessKey = "F";
	}

	function change_bgcolor1()
	{
		$this->button1->BackColor = "orange";
		$this->button1->ForeColor="white";
		$this->button1->Font->Bold = true;
		$this->button1->Font->Size = "2em";
	}

	
	function change_bgcolor2()
	{
		$this->button2->BackColor = "red";
		$this->button2->ForeColor="white";
		$this->button2->Font->Bold = true;
		$this->button2->Font->Size = 14;
	}
	
	function change_attributes1()
	{
		$this->button1->Attributes['onclick'] = "alert('haha!')";
	}

	function change_attributes2()
	{
		$this->button2->Attributes['onclick'] = "alert('baz!')";
	}
}

?>