<?php
/*
 * Created on 6/05/2006
 */

class Calculator extends TPage
{
	public function do_sum($sender, $param)
	{
		$this->c->Text = floatval($this->a->Text) + floatval($this->b->Text);	
	}
}

?>