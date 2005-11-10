<?php

class HomePage extends TPage
{
	public function testClick($sender,$param)
	{
		if($sender->BackColor==='')
			$sender->BackColor='blue';
		else
			$sender->BackColor='';
	}
}

?>