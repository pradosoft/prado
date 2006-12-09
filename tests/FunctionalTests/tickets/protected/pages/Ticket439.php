<?php

Prado::using('System.Web.UI.ActiveControls.*');

class Ticket439 extends TPage
{

	function button_clicked($sender, $param)
	{
		$page = $this->Service->constructUrl('Home');
		$this->Response->redirect($page);
	}
}

?>