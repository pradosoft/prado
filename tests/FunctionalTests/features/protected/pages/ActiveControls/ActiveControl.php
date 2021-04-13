<?php
/*
 * Created on 2/05/2006
 */
 
class ActiveControl extends TPage
{
	private static $_colors = ['red', 'green', 'blue', 'purple', 'black', 'orange'];
	
	public function slowResponse($sender, $param)
	{
		//sleep(1);
		$this->label1->setText("The time is " . time() . " from " . $sender->getID());
		$this->label1->setForeColor($this->getColor());
		$this->label1->renderControl($param->getOutput());

		$this->button2->setEnabled(true);
		
		$this->panel2->setVisible(true);
		$this->panel1->setBackColor($this->getColor());
		$this->panel1->renderControl($param->getOutput());
		$this->getCallbackClient()->shake($this->panel1);
	}
	
	public function onButtonClicked($sender, $param)
	{
		$this->label2->setText("Muahaha !!! the time is " . time() . " from " . $sender->getID());
	}
	
	public function fastResponse($sender, $param)
	{
		$this->button2->setEnabled(false);
		$style['color'] = $this->getColor();
		$this->getCallbackClient()->setStyle($this->label2, $style);
		$this->getCallbackClient()->shake($this->label2);
	}
	
	private function getColor()
	{
		return self::$_colors[rand(0, count(self::$_colors) - 1)];
	}
}
