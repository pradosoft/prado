<?php

class Ticket439 extends TPage
{
	public function button_clicked($sender, $param)
	{
		$page = $this->Service->constructUrl('Home');
		$this->Response->redirect($page);
	}
}
