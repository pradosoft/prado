<?php

/**
 * Harness page for TAbbr, TDfn, and TVar functional tests.
 *
 * The glossary panel holds the abbreviations and definitions and the text they
 * mark. The live panel re-renders during a callback. Its TAbbr marks the new text
 * in the TPanel inside it, since an active control re-rendering in a callback skips
 * its own render filters.
 */
class TermTest extends TPage
{
	public function update_live($sender, $param)
	{
		$this->update->setText('Each URL is new.');
		$this->live->render($param->getNewWriter());
	}
}
