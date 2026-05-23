<?php

class TextBoxValidationCallback extends TPage
{
	public function lookupZipCode()
	{
		$this->City->Text = "City: " . $this->Address->Text . ' Zip: ' . $this->ZipCode->Text;
	}
}
