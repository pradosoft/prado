<?php

class ActiveWebTemplatePersistTest extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		// Changes each render, so the test can prove a real full-page postback
		$this->renderTime->Text = (string) microtime(true);
	}

	public function server_stamp($sender, $param)
	{
		// Purely server-driven: the client never calls appendTo itself
		$this->rowTemplate->stampInto('listBody', ['name' => 'Server']);
	}

	public function noop($sender, $param)
	{
	}
}
