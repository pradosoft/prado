<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket359 extends TPage
{
	function validate_text1($sender, $param)
	{
		$param->IsValid = $param->Value == 'Prado';
	}
}

?>