<?php

/**
 * @author Daniel Sampedro Bello <darthdaniel85@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @since 3.3
 * @package Wsat.pages
 */
class TWsatLogin extends TPage
{

        public function login()
        {
                if ($this->IsValid)
                {
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