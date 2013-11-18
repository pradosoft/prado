<?php

/**
 * Description of TWsat
 * Inspired in both Microsoft Web Site Administration Tool(WSAT) and Yii's Gii.
 * @version 1.0
 * @author Daniel Sampedro darthdaniel85@gmail.com
 * @since Prado 3.3
 * 
 * To use TWsatService, configure it in the application specification like following:
 * <code>
 *   <services>
 *    <service id="wsat" class="System.Wsat.TWsatService" Password="my_secret_password" />
 *   </services>
 * </code>
 * ...and then you need to go to http://localhost/yoursite/index.php?wsat=TWsatLogin
 * and generate code and configure your site.
 */
class TWsatService extends TPageService {

    private $_pass = '';

//-----------------------------------------------------------------------------    
    public function init($config) {
        if ($this->getApplication()->getMode() === TApplicationMode::Performance || $this->getApplication()->getMode() === TApplicationMode::Normal) {
            throw new TInvalidOperationException("You should not use Prado WSAT in any of the production modes.");
        }
        if (empty($this->_pass)) {
            throw new TConfigurationException("You need to specify the Password attribute.");
        }
        $this->setDefaultPage("TWsatHome");
        $this->_startThemeManager();
        parent::init($config);
    }

    public function getBasePath() {
        $basePath = Prado::getPathOfNamespace("System.Wsat.pages");
        return realpath($basePath);
    }

    private function _startThemeManager() {
        $themeManager = new TThemeManager;
        $themeManager->BasePath = "System.Wsat.themes";
        $url = Prado::getApplication()->getAssetManager()->publishFilePath(Prado::getPathOfNamespace('System.Wsat'));
        $themeManager->BaseUrl = $url . DIRECTORY_SEPARATOR . "themes";

        $themeManager->init(null);
        $this->setThemeManager($themeManager);
    }

    public function getPassword() {
        return $this->_pass;
    }

    public function setPassword($_pass) {
        $this->_pass = $_pass;
    }

}

?>
