<?php

use Prado\Web\Javascripts\TJavaScript;

/**
 * Harness page for the key shortcut functional tests (#1408).
 *
 * "Save" posts back on Control+S or Meta+S, and "/" focuses the search field.
 * "Italic" is a page-wide character-key shortcut; the "Single-key shortcuts"
 * check box turns such shortcuts off through Prado.KeyShortcuts. "Bold" works
 * only while focus is inside the toolbar. "Rebind italic" changes the italic
 * shortcut to Alt+Shift+I in a callback. "Refresh panel" re-renders the panel
 * with its inner button, and "Hide inner" removes that button.
 */
class KeyShortcutsTest extends TPage
{
	public function onPreRender($param)
	{
		parent::onPreRender($param);
		$this->getClientScript()->registerEndScript('keyshortcuts:characterKeys', 'document.getElementById('
			. TJavaScript::encode($this->characterKeys->getClientID())
			. ').addEventListener("change", (e) => Prado.KeyShortcuts.setCharacterKeysEnabled(e.target.checked));');
	}

	public function saveClicked($sender, $param)
	{
		$this->saves->setText((int) $this->saves->getText() + 1);
		$this->result->setText('save');
	}

	public function italicClicked($sender, $param)
	{
		$this->result->setText('italic');
	}

	public function rebindItalic($sender, $param)
	{
		$this->italic->setKeyShortcuts('Alt+Shift+I');
		$this->result->setText('rebound');
	}

	public function firstClicked($sender, $param)
	{
		$this->result->setText('first');
	}

	public function boldClicked($sender, $param)
	{
		$this->result->setText('bold');
	}

	public function refreshPanel($sender, $param)
	{
		$this->renders->setText((int) $this->renders->getText() + 1);
		$this->panel->render($param->getNewWriter());
	}

	public function hideInner($sender, $param)
	{
		$this->inner->setVisible(false);
		$this->panel->render($param->getNewWriter());
	}

	public function innerClicked($sender, $param)
	{
		$this->result->setText('inner ' . $this->renders->getText());
	}
}
