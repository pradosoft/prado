<?php

class ControlAdapterTest extends TPage
{
	public function change_enabled()
	{
		$this->button1->Enabled = !$this->button1->Enabled;
	}
	
	public function change_visible()
	{
		$this->button1->Visible = !$this->button1->Visible;
	}
	
	public function change_tooltip()
	{
		$this->button1->ToolTip = "hello world";
	}
	
	public function change_tabindex()
	{
		$this->button1->tabIndex = 10;
	}
	
	public function change_accesskey()
	{
		$this->button1->accessKey = "F";
	}

	public function change_bgcolor1()
	{
		$this->button1->BackColor = "orange";
		$this->button1->ForeColor = "white";
		$this->button1->Font->Bold = true;
		$this->button1->Font->Size = "2em";
	}

	
	public function change_bgcolor2()
	{
		$this->button2->BackColor = "red";
		$this->button2->ForeColor = "white";
		$this->button2->Font->Bold = true;
		$this->button2->Font->Size = 14;
	}
	
	public function change_attributes1()
	{
		$this->button1->Attributes['onclick'] = "alert('haha!')";
	}

	public function change_attributes2()
	{
		$this->button2->Attributes['onclick'] = "alert('baz!')";
	}
}
