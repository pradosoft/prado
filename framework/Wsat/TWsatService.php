<?php

/**
 * Description of TWsat
 * Inspired in both Microsoft Web Site Administration Tool(WSAT) and Yii's Gii.
 * @version 1.0
 * @author Daniel Sampedro darthdaniel85@gmail.com
 * @since Prado 3.3
 */
class TWsatService extends TPageService {

    private $_pass = '';

//-----------------------------------------------------------------------------    
    public function init($config) {
        if ($this->getApplication()->getMode() === TApplicationMode::Performance
                || $this->getApplication()->getMode() === TApplicationMode::Normal) {
            throw new TInvalidOperationException("You should not use Prado WSAT in any of the production modes.");
        }
        if (empty($this->_pass)) {
            throw new TConfigurationException("You need to specify the Password attribute.");
        }
        $this->setDefaultPage("TWsatHome");
        parent::init($config);        
    }

    public function getBasePath() {
        $basePath = Prado::getPathOfNamespace("System.Wsat.pages");
        return realpath($basePath);
    }

    public function getPassword() {
        return $this->_pass;
    }

    public function setPassword($_pass) {
        $this->_pass = $_pass;
    }

}

?>
