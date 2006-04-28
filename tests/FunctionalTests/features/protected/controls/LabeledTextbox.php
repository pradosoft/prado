<?php
/*
 * Created on 28/04/2006
 */

class LabeledTextBox extends TTemplateControl
{
	public function __construct()
	{
		parent::__construct();
		$this->ensureChildControls();
	}

	public function getTextBox()
	{
		return $this->getRegisteredObject('textbox');
	}
	
	public function getLabel()
	{
		return $this->getRegisteredObject('label');
	}
} 

?>
