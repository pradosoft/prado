<?php

class CustomTemplateControlTest extends TPage
{
	function button2_onclick($sender, $param)
	{

		$this->label1->Text = "Button 1 was clicked ";
		$this->label1->Text .= $this->foo->Text;

		$x=Prado::createComponent('Application.pages.CustomTemplateComponent');

		$this->placeholder->getControls()->add($x);
		$this->placeholder->dataBind();
	}

	function button2_callback($sender, $param)
	{
		$this->placeholder->render($param->NewWriter);

		$this->label1->Text .= " using callback!";
		$this->label1->Text .= "... and this is the textbox text: ". $this->foo->Text;
	}

}

?>