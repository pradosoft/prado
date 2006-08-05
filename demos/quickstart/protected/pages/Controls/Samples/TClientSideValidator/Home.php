<?php

class Home extends TPage
{
	function validator2_onValidate($sender, $param)
	{
		$sender->Enabled = $this->check1->Checked;
	}
	
	function validate2_onPostValidate($sender, $param)
	{
		$sender->Enabled = true;
	}
	
	function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->panel1->Style = 
			$this->check1->Checked ? "display:block" : "display:none";		
	}	
}

?>