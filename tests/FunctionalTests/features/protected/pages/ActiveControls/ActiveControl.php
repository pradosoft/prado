<?php
/*
 * Created on 2/05/2006
 */
 
class ActiveControl extends TPage
{
	public function control1onCallback($sender, $param)
	{
		$this->label1->setText("The time is ".time());
	}
}
?>
