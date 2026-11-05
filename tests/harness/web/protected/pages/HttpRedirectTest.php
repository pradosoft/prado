<?php

class HttpRedirectTest extends \Prado\Web\UI\TPage
{
	public function onLoad($param)
	{
		parent::onLoad($param);

		$action = $this->Request->itemAt('action') ?? '';

		switch ($action) {
			case 'redirect302':
				// Standard 302 redirect to the target page
				$targetUrl = $this->Service->constructUrl('HttpRedirectTarget');
				$this->Response->redirect($targetUrl);
				break;

			case 'redirect302-labeled':
				// 302 redirect with a query param on the target so we can verify it arrived
				$targetUrl = $this->Service->constructUrl('HttpRedirectTarget', ['from' => 'redirect302']);
				$this->Response->redirect($targetUrl);
				break;
		}
	}
}
