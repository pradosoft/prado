<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket470 extends TPage
{
	/**
	 * Increase the reload counter and render the activepanel content
	 */
	public function Reload($sender, $param){
		$this->Results->Text = "";
		$this->counter->Text = $this->counter->Text +1;
		$this->activePanelTest->renderControl($param->getNewWriter());		
	}
	
	/**
	 *function to call when the form is valid (and the linkbutton fired his callback event)
	 */
	public function Valid($sender, $param){
		$this->Results->Text = "OK!!!";
	}
}

?>