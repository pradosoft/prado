<?php
Prado::using('System.Web.UI.ActiveControls.*');
class Ticket578 extends TPage
{

	function button2_onclick($sender, $param)
	{
		$this->label1->Text = "Button 1 was clicked : " . htmlspecialchars($this->text1->Text);
	}

}

?>