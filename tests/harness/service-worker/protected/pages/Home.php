<?php

/**
 * The page the default worker controls; its callback runs through the worker.
 */
class Home extends Prado\Web\UI\TPage
{
	public function ping($sender, $param)
	{
		$this->Pong->setText('pong ' . date('His'));
	}
}
