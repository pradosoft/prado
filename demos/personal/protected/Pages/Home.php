<?php

class Home extends TPage
{
	public function onPreInit($param)
	{
		parent::onPreInit($param);
		if(!$this->getUser()->getIsGuest())
			$this->setTheme('');
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