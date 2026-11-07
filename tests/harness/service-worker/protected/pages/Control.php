<?php

/**
 * Stores the harness state: `?reset=1`, `?revision=<n>`, `?disabled=0|1`.
 */
class Control extends Prado\Web\UI\TPage
{
	public function onInit($param)
	{
		parent::onInit($param);
		$request = $this->getRequest();
		$state = $request->itemAt('reset') ? [] : HarnessServiceWorkerModule::readState();
		if (($revision = $request->itemAt('revision')) !== null) {
			$state['revision'] = (int) $revision;
		}
		if (($disabled = $request->itemAt('disabled')) !== null) {
			$state['disabled'] = (bool) $disabled;
		}
		HarnessServiceWorkerModule::writeState($state);
	}
}
