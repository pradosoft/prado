<?php
/*
 * Created on 2/05/2006
 */
 
class ActiveControl extends TPage
{
	public function control1onCallback($sender, $param)
	{
		$this->button2->setVisible(true);
		$this->button2->setText("Time is ".time());
		$this->control1->render($param->getOutput());
	}
}
?>
