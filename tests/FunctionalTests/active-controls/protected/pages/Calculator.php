<?php

class Calculator extends TPage
{
	public function do_sum($sender, $param)
	{
		$this->c->Text = (float) ($this->a->Text) + (float) ($this->b->Text);
	}
}
