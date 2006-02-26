<?php

class Ticket72 extends TPage
{
	public function ButtonClick($sender,$param)
	{
		$a1 = $this->K1->SafeText;
		$a2 = $this->K2->SafeText;
		$this->ResultLabel->Text = $a2;
	}
}

?>