<?php

class HomePage extends TPage
{
	public function onPreInit($param)
	{
		parent::onPreInit($param);
		if(!$this->User->IsGuest)
			$this->Theme='';
	}

	public function testClick($sender,$param)
	{
		if($sender->BackColor==='')
			$sender->BackColor='blue';
		else
			$sender->BackColor='';
	}
}

?>