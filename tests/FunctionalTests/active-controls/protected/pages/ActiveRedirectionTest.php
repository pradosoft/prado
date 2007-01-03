<?php

class ActiveRedirectionTest extends TPage
{
	function button_clicked($sender, $param)
	{
		$default=$this->Service->constructUrl($this->Service->DefaultPage);
		$this->Response->redirect($default);
	}
}

?>