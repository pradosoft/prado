<?php
/*
 * Created on 2/05/2006
 */
 
class ActiveControl extends TPage
{
	public function control1onCallback($sender, $param)
	{
		sleep(5);
		$this->label1->setText("The time is ".time()." from ".$sender->ID);
	}
}
?>
