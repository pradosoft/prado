<?php
/*
 * Created on 6/05/2006
 */
Prado::using('System.Web.UI.ActiveControls.*');

class Calculator extends TPage
{
	public function do_sum($sender, $param)
	{
		$this->c->Text = (float) ($this->a->Text) + (float) ($this->b->Text);
	}
}
