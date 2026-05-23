<?php

class Ticket278 extends TPage
{
	public function validator2_onValidate($sender, $param)
	{
		$sender->Enabled = $this->check1->Checked;
	}
	
	public function validate2_onPostValidate($sender, $param)
	{
		$sender->Enabled = true;
	}
	
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->panel1->Style =
			$this->check1->Checked ? "display:block" : "display:none";
	}
}
