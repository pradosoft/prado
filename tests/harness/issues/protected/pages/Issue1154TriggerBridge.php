<?php

class Issue1154TriggerBridge extends TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		// The page is plain HTML (no com:T controls), so Prado wouldn't
		// otherwise register jQuery / prado.js. Force it.
		$cs = $this->getPage()->getClientScript();
		$cs->registerPradoScript('jquery');
		$cs->registerPradoScript('prado');
	}

	// The `submitted` query param visible on the URL after a real form
	// submission is the assertion target for the navigation-based tests.
}
