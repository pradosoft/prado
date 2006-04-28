<?php
/*
 * Created on 28/04/2006
 */

class LabeledTextBox extends TCompositeControl
{
	public function getTextBox()
	{
		return $this->getRegisteredObject('textbox');
	}
	
	public function getLabel()
	{
		return $this->label;
	}
} 

?>
