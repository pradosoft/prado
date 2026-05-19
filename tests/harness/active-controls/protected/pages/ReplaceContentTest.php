<?php

class ReplaceContentTest extends TPage
{
	public function appendContent($sender, $param)
	{
		$this->CallbackClient->appendContent($this->subpanel, $this->replacementContent());
	}

	public function prependContent($sender, $param)
	{
		$this->CallbackClient->prependContent($this->subpanel, $this->replacementContent());
	}

	public function insertContentBefore($sender, $param)
	{
		$this->CallbackClient->insertContentBefore($this->subpanel, $this->replacementContent());
	}

	public function insertContentAfter($sender, $param)
	{
		$this->CallbackClient->insertContentAfter($this->subpanel, $this->replacementContent());
	}

	public function replaceContent($sender, $param)
	{
		$this->CallbackClient->replaceContent($this->subpanel, $this->replacementContent());
	}

	public function replacementContent()
	{
		if ($this->check1->Checked) {
			$this->newPanel->Visible = true;
			return $this->newPanel;
		} else {
			return $this->content->Text;
		}
	}
}
