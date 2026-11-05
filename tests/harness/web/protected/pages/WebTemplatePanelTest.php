<?php

class WebTemplatePanelTest extends TPage
{
	public function getGeneration()
	{
		return $this->getViewState('Generation', 1);
	}

	public function setGeneration($value)
	{
		$this->setViewState('Generation', $value, 1);
	}

	public function refresh_a($sender, $param)
	{
		$this->setGeneration($this->getGeneration() + 1);
		$this->panelA->render($this->getResponse()->createHtmlWriter());
	}

	public function inner_clicked($sender, $param)
	{
		$this->innerStatus->Text = 'inner callback fired';
	}
}
