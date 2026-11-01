<?php

/**
 * Harness page for the TTeleport functional tests (#1408).
 *
 * The edit dialog holds page form fields and a native dialog form. "Refresh"
 * updates the active panels inside a teleport, one replaced and one morphed.
 * "Re-render outer" re-renders an active panel that holds two teleports, the
 * second of them hidden on the first render. The note dialog is a TActiveDialog
 * whose close callback reports its return value and the note field.
 */
class TeleportTest extends TPage
{
	public function getRefreshCount()
	{
		return $this->getViewState('RefreshCount', 0);
	}

	public function saveClicked($sender, $param)
	{
		$this->postbacks->setText($this->postbacks->getText() + 1);
		$this->savedName->setText($this->userName->getText());
	}

	public function refreshTeleport($sender, $param)
	{
		$this->setViewState('RefreshCount', $this->getRefreshCount() + 1);
		$this->replacedField->setText('replaced ' . $this->getRefreshCount());
		$this->replacedPanel->render($param->getNewWriter());
		$this->morphedPanel->render($param->getNewWriter());
	}

	public function rerenderOuter($sender, $param)
	{
		$this->setViewState('RefreshCount', $this->getRefreshCount() + 1);
		$this->lateLayer->setVisible(true);
		$this->outer->render($param->getNewWriter());
	}

	public function noteClosed($sender, $param)
	{
		$this->closedWith->setText($this->noteText->getText());
	}
}
