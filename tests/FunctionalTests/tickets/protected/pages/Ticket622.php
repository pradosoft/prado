<?php

class Ticket622 extends TPage
{
	public function changeA($sender, $param)
	{
		$this->ALB->setDisplay('Dynamic');
		$this->ACB->setDisplay('Dynamic');
		$this->ARB->setDisplay('Dynamic');
	}
}
