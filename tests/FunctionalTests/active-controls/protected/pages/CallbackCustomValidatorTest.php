<?php

class CallbackCustomValidatorTest extends TPage
{
	function validate_text1($sender, $param)
	{
		$param->IsValid = $param->Value == 'Prado';
	}
}

?>