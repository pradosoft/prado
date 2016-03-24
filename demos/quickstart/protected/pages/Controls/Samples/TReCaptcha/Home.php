<?php

class Home extends TPage
{
	public function buttonClicked($sender,$param)
	{
		if($this->IsValid)
			$sender->Text="You passed!";
	}
}

