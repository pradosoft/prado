<?php

class Ticket121 extends TPage
{
	public function buttonClicked($sender,$param)
	{
		$this->Result->Text="clicked at ({$param->X},{$param->Y})";
	}
}

?>