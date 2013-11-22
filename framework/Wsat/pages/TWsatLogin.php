<?php

/**
 * Description of Inicio
 *
 * @author daniels
 */
class TWsatLogin extends TPage {

    public function login() {
        if ($this->IsValid) {
            $this->Session["wsat_password"] = $this->getService()->getPassword();

            $authManager = $this->Application->getModule('auth');
            $url = $authManager->ReturnUrl;
            if (empty($url)) {
                $url = $this->Service->constructUrl('TWsatHome');
            }
            $this->Response->redirect($url);
        }
    }

    public function validatePassword($sender, $param) {
        $config_pass = $this->getService()->getPassword();
        $user_pass = $this->password->Text;
        $param->IsValid = $user_pass === $config_pass;
    }

}

?>