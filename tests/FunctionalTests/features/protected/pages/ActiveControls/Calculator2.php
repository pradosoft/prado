<?php
/*
 * Created on 13/05/2006
 */

class Calculator2 extends TPage
{
	public function do_sum($sender, $param)
	{
		$this->c->Text = floatval($this->a->Text) + floatval($this->b->Text);	
	}
	
	public function update_callback($sender, $param)
	{
		$this->do_sum($this->sum, null);
		$this->panel1->renderControl($param->Output); 
	}
}

?>