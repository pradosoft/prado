<?php

/**
 * Description of Inicio
 *
 * @author daniels
 */
class TWsatLogin extends TPage {

    public function login() {
        $config_pass = $this->getService()->getPassword();
        $user_pass = $this->password->Text;

        if ($user_pass === $config_pass) {
            $this->Session["wsat_password"] = $config_pass;

            $authManager = $this->Application->getModule('auth');
            $url = $authManager->ReturnUrl;
            if (empty($url)) {
                $url = $this->Service->constructUrl('TWsatHome');
            }
            $this->Response->redirect($url);
        } else {
            echo "user or pass wrong";
        }
    }

}

?>