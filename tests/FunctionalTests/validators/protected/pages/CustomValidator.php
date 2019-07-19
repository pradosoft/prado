<?php
/*
 * Created on 16/04/2006
 */

class CustomValidator extends TPage
{
	public function CustomValidation($sender, $params)
	{
		$params->isValid = $this->text1->Text == "Prado";
	}
}
