<?php

/**
 * @author Daniel Sampedro Bello <darthdaniel85@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @since 3.3
 * @package Prado\Wsat\pages
 */

namespace Prado\Wsat\pages;

use Prado\Web\UI\TPage;

class TWsatLogin extends TPage
{
	public function login()
	{
		if ($this->IsValid) {
			$this->Session["wsat_password"] = $this->getService()->getPassword();
			$url = $this->Service->constructUrl('TWsatHome');
			$this->Response->redirect($url);
		}
	}

	public function validatePassword($sender, $param)
	{
		$config_pass = $this->Service->Password;
		$user_pass = $this->password->Text;
		$param->IsValid = $user_pass === $config_pass;
	}
}
