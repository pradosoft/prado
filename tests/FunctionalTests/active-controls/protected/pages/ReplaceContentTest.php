<?php

class ReplaceContentTest extends TPage
{
	function appendContent($sender, $param)
	{
		$this->CallbackClient->appendContent($this->subpanel, $this->content->Text);
	}

	function prependContent($sender, $param)
	{
		$this->CallbackClient->prependContent($this->subpanel, $this->content->Text);
	}

	function insertContentBefore($sender, $param)
	{
		$this->CallbackClient->insertContentBefore($this->subpanel, $this->content->Text);
	}

	function insertContentAfter($sender, $param)
	{
		$this->CallbackClient->insertContentAfter($this->subpanel, $this->content->Text);
	}

	function replaceContent($sender, $param)
	{
		$this->CallbackClient->replaceContent($this->subpanel, $this->content->Text);
	}
}

?>