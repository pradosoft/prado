<?php

namespace Prado\Wsat\pages\layout;

use Prado\Web\UI\TTemplateControl;
use Prado\Wsat\pages\TWsatLogin;

/**
 * Description of MainLayout
 *
 * @author daniels
 */
class TWsatLayout extends TTemplateControl
{
	public function onLoad($param)
	{
		parent::onLoad($param);
		$this->validateSecurity();
	}

	private function validateSecurity()
	{
		if ($this->Session["wsat_password"] !== $this->getService()->getPassword()) {
			if (!$this->getPage() instanceof TWsatLogin) {
				$url = $this->Service->constructUrl('TWsatLogin');
				$this->Response->redirect($url);
			}
		}
	}

	public function logout()
	{
		$this->Session["wsat_password"] = "";
		$url = $this->Service->constructUrl('TWsatLogin');
		$this->Response->redirect($url);
	}
}
