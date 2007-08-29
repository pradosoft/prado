<?php

class Home extends TPage
{
	public function onPreRender($param)
	{
		echo "token is |".$this->Captcha->Token."|";
	}

	public function buttonClicked($sender,$param)
	{
		if($this->Captcha->validate($this->Input->Text))
			$sender->Text="ok";
		else
			$sender->Text="no!";
		$this->Captcha->regenerateToken();
	}
}

?>