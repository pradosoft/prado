<?php

// $Id: Home.php 3189 2012-07-12 12:16:21Z ctrlaltca $
class Home extends TPage
{
	public function buttonClicked($sender, $param)
	{
		if($param instanceof TCommandEventParameter)
			$sender->Text="Name: {$param->CommandName}, Param: {$param->CommandParameter}";
		else
			$sender->Text="I'm clicked";
	}

	public function buttonCallback($sender, $param)
	{
		$sender->Text .= ' using callback';
	}
}

