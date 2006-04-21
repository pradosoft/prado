<?php
/*
 * Created on 16/04/2006
 */

class CustomValidator extends TPage
{ 
	function CustomValidation($sender, $params) 
	{
		$params->isValid = $this->text1->Text == "Prado";
	}	
}

?>
