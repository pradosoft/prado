<?php
/*
 * Created on 13/04/2006
 */

class Ticket93 extends TPage
{
	public function buttonClicked($sender,$param)
	{
		echo 'postback triggered with value '.$param->PostBackValue;
	}
}

?>
