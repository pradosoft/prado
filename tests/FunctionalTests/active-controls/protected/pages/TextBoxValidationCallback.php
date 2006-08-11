<?php

class TextBoxValidationCallback extends TPage
{

	function lookupZipCode()
	{
		$this->City->Text = "City: ".$this->Address->Text . ' Zip: '.$this->ZipCode->Text;
	}
}
?>