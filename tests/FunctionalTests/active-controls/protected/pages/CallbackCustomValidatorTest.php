<?php

class CallbackCustomValidatorTest extends TPage
{
	public function validate_text1($sender, $param)
	{
		$param->IsValid = $param->Value == 'Prado';
	}
}
