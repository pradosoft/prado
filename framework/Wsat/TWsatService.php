<?php

/**
 * @author Daniel Sampedro Bello <darthdaniel85@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2013 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Id$
 * @since 3.3
 * @package Wsat
 */

/**
 * TWsatService class
 * 
 * Wsat is inspired in both Asp.Net - Web Site Administration Tool(WSAT) and Yii's Gii.
 * Wsat enables you to generate code saving your time in too many tedious tasks in a GUI fashion.
 * 
 * Current options:
 * 1- Generate one or all Active Record Classes from your DataBase.
 *  1.1- Automatically generate all relations between the AR Classes (new).
 *  1.2- Automatically generate the __toString() magic method in a smart way (new).
 * 
 * To use TWsatService, configure it in the application configuration file like following:
 * <code>
 *   <services>
 *     ...
 *     <service id="wsat" class="System.Wsat.TWsatService" Password="my_secret_password" />
 *   </services>
 * </code>
 * ...and then you need to go to http://localhost/yoursite/index.php?wsat=TWsatLogin
 * and generate code and configure your site.
 * 
 * Warning: You should only use Wsat in development mode.
 */
class TWsatService extends TPageService
{

        private $_pass = '';

        public function init($config)
        {
                if ($this->getApplication()->getMode() === TApplicationMode::Performance || $this->getApplication()->getMode() === TApplicationMode::Normal)
                        throw new TInvalidOperationException("You should not use Prado WSAT in any of the production modes.");

                if (empty($this->_pass))
                        throw new TConfigurationException("You need to specify the Password attribute.");

                $this->setDefaultPage("TWsatHome");
                $this->_startThemeManager();
                parent::init($config);
        }

        public function getBasePath()
        {
                $basePath = Prado::getPathOfNamespace("System.Wsat.pages");
                return realpath($basePath);
        }

        private function _startThemeManager()
        {
                $themeManager = new TThemeManager;
                $themeManager->BasePath = "System.Wsat.themes";
                $url = Prado::getApplication()->getAssetManager()->publishFilePath(Prado::getPathOfNamespace('System.Wsat'));
                $themeManager->BaseUrl = "$url/themes";

                $themeManager->init(null);
                $this->setThemeManager($themeManager);
        }

        public function getPassword()
        {
                return $this->_pass;
        }

        public function setPassword($_pass)
        {
                $this->_pass = $_pass;
        }

}