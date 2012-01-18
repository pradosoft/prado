<?php

class Home extends TPage
{
	public function serverValidate($sender,$param)
	{
		if($param->Value!=='test')
			$param->IsValid=false;
	}

	public function serverValidateNoControl($sender,$param)
	{
		if($this->TextBox4->Text!=='test')
			$param->IsValid=false;
	}
}

?>