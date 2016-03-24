<?php

class Home extends TPage
{
	public function buttonCallback($sender,$param)
	{
		if($this->IsValid)
			$sender->Text="You passed!";
	}
}

