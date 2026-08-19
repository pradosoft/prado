<?php

class WebTemplatePersistTest extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		// A value that changes each render proves the postback reloaded the page
		$this->renderTime->Text = (string) microtime(true);
	}

	public function noop($sender, $param)
	{
	}
}
