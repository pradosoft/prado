<?php

/**
 * Harness page for TAbbr, TDfn, and TVar functional tests.
 *
 * The glossary panel holds the abbreviations and definitions and the text they
 * mark. The live panel re-renders during a callback, and its TAbbr marks the new
 * text, except in its exempt TPanel. The inner active panel re-renders alone, and
 * the TAbbr of the outer TPanel around it marks its new text; the TDfn, with its
 * MarkLimit, does not mark a fragment. The active panel in a `<pre>` re-renders
 * alone, and its new text stays unmarked. The TAbbr of the levels panel, with its
 * DepthLimit of 2, marks two levels of controls below the panel, in a full render
 * and when the active panel inside it re-renders alone.
 */
class TermTest extends TPage
{
	public function update_live($sender, $param)
	{
		$this->update->setText('Each URL is new.');
		$this->updateExempt->setText('This URL is exempt.');
		$this->live->render($param->getNewWriter());
	}

	public function update_inner($sender, $param)
	{
		$this->innerUpdate->setText('The API runs a callback.');
		$this->inner->render($param->getNewWriter());
		$this->preUpdate->setText('The API is literal here.');
		$this->innerPre->render($param->getNewWriter());
	}

	public function update_levels($sender, $param)
	{
		$this->levelUpdate->setText('The DOM changed.');
		$this->levelDeepUpdate->setText('A deeper DOM change.');
		$this->levelLive->render($param->getNewWriter());
	}
}
