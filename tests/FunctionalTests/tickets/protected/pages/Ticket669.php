<?php
prado::using ('System.Web.UI.ActiveControls.*');
class Ticket669 extends TPage
{
	public function load1($sender,$param)
	{
		$this->multiView->setActiveViewIndex(1);
		$this->panel1->render($param->getNewWriter());
	}
	public function load2($sender,$param)
	{
		$this->multiView->setActiveViewIndex(2);
		$this->panel1->render($param->getNewWriter());
	}
	public function test1($sender,$param)
	{
		$this->tb1->setText($this->tb1->getText().' +1');
		$this->tb2->setText($this->tb2->getText().' +1');
		$this->panel2->render($param->getNewWriter());
	}
	public function test2($sender,$param)
	{
		$this->tb3->setText($this->tb3->getText().' +1');
		$this->tb4->setText($this->tb4->getText().' +1');
		$this->tb5->setText($this->tb5->getText().' +1');
		$this->panel4->render($param->getNewWriter());
	}
	public function test3($sender,$param)
	{
		$this->tb6->setText($this->tb6->getText().' +1');
		$this->tb7->setText($this->tb7->getText().' +1');
		$this->panel7->render($param->getNewWriter());
	}
}

?>