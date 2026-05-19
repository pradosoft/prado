<?php

class Issue524 extends TPage
{
	public function __construct()
	{
		Prado::getApplication()->getGlobalization()->setCharset('ISO-8859-1');
		parent::__construct();
	}

	public function validateText($sender, $param)
	{
		$param->IsValid = false;
		$iso8859text = iconv('utf-8', 'iso-8859-1', 'fünf');
		$this->Validator->ErrorMessage = $iso8859text;
	}
}
