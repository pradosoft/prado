<?php

/**
 * Harness page for TOutput and TActiveOutput functional tests.
 *
 * The total recalculates during the TActiveTextBox callbacks. The note is a
 * TOutput whose ForControl lists an invisible text box. The buttons set the
 * total's Text and ForControl during a callback, and fill the empty output.
 */
class OutputTest extends TPage
{
	public function calculate($sender, $param)
	{
		$this->total->setText(sprintf('%.2f', (float) $this->price->getText() * (float) $this->quantity->getText()));
		$this->status->setText('calculated');
	}

	public function set_markup($sender, $param)
	{
		$this->total->setText('<img src=x onerror="window.__outputXss=1">');
		$this->status->setText('markup');
	}

	public function set_entity($sender, $param)
	{
		$this->total->setText('Caf&eacute; &amp; Bar');
		$this->status->setText('entity');
	}

	public function fill_empty($sender, $param)
	{
		$this->empty->setText('filled');
		$this->status->setText('filled');
	}

	public function price_only($sender, $param)
	{
		$this->total->setForControl('price');
		$this->status->setText('price only');
	}

	public function detach($sender, $param)
	{
		$this->total->setForControl('');
		$this->status->setText('detached');
	}

	public function submit($sender, $param)
	{
		$this->status->setText('submitted');
	}
}
