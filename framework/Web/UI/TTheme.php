<?php

/**
 * TThemeManager class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\Exceptions\TIOException;
use Prado\Exceptions\TConfigurationException;
use Prado\Prado;
use Prado\TApplicationMode;

/**
 * TTheme class
 *
 * TTheme represents a particular theme. It is merely a collection of skins
 * that are applicable to the corresponding controls.
 *
 * Each theme is stored as a directory and files under that directory.
 * The theme name is the directory name. When TTheme is created, the files
 * whose name has the extension ".skin" are parsed and saved as controls skins.
 *
 * A skin is essentially a list of initial property values that are to be applied
 * to a control when the skin is applied.  {@see \Prado\Web\UI\TSkinTemplate} is used to
 * turn off class and attribute validation.
 * Each type of control can have multiple skins identified by the SkinID.
 * If a skin does not have SkinID, it is the default skin that will be applied
 * to controls that do not specify particular SkinID.
 *
 * For globalization, Themes remove all css files that end in ".*rtl.css" or
 * ".*rtl.(media).css".  Then if there is Application Globalization, and
 * globalization current culture is Right-to-left then the rtl css files are
 * re-added at the end of the css files so rtl css has priority.
 *
 * Whenever possible, TTheme will try to make use of available cache to save
 * the parsing time.
 *
 * To apply a theme to a particular control, call {@see applySkin}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 * @method void dyThemeConstruct(string $themePath, string $themeUrl)
 * @method bool dyProcessFile(bool $returnValue, string $file))
 * @method void dyThemeProcess()
 * @method string dyCssMediaType(string $returnType, string $url)
 */
class TTheme extends \Prado\TApplicationComponent implements ITheme
{
	/**
	 * prefix for cache variable name used to store parsed themes
	 */
	public const THEME_CACHE_PREFIX = 'prado:theme:';
	/**
	 * Extension name of skin files
	 */
	public const SKIN_FILE_EXT = '.skin';
	/**
	 * @var string theme path
	 */
	private $_themePath;
	/**
	 * @var string theme url
	 */
	private $_themeUrl;
	/**
	 * @var array list of skins for the theme
	 */
	private $_skins;
	/**
	 * @var string theme name
	 */
	private $_name = '';
	/**
	 * @var array list of css files
	 */
	private $_cssFiles = [];
	/**
	 * @var array list of js files
	 */
	private $_jsFiles = [];

	/**
	 * Constructor.
	 * @param string $themePath theme path
	 * @param string $themeUrl theme URL
	 * @throws TConfigurationException if theme path does not exist or any parsing error of the skin files
	 */
	public function __construct($themePath, $themeUrl)
	{
		$this->_themeUrl = $themeUrl;
		$this->_themePath = realpath($themePath);
		$this->_name = basename($themePath);
		parent::__construct();
		$this->dyThemeConstruct($themePath, $themeUrl);
		$cacheValid = false;
		// TODO: the following needs to be cleaned up (Qiang)
		if (($cache = $this->getApplication()->getCache()) !== null) {
			$array = $cache->get(self::THEME_CACHE_PREFIX . $themePath);
			if (is_array($array)) {
				[$skins, $cssFiles, $jsFiles, $timestamp] = $array;
				if ($this->getApplication()->getMode() !== TApplicationMode::Performance) {
					if (($dir = opendir($themePath)) === false) {
						throw new TIOException('theme_path_inexistent', $themePath);
					}
					$cacheValid = true;
					while (($file = readdir($dir)) !== false) {
						if ($file === '.' || $file === '..' || $this->dyProcessFile(false, $file)) {
							continue;
						} elseif (basename($file, '.css') !== $file) {
							$this->_cssFiles[] = $themeUrl . DIRECTORY_SEPARATOR . $file;
						} elseif (basename($file, '.js') !== $file) {
							$this->_jsFiles[] = $themeUrl . DIRECTORY_SEPARATOR . $file;
						} elseif (basename($file, self::SKIN_FILE_EXT) !== $file && filemtime($themePath . DIRECTORY_SEPARATOR . $file) > $timestamp) {
							$cacheValid = false;
							break;
						}
					}
					closedir($dir);
					if ($cacheValid) {
						$this->_skins = $skins;
					}
				} else {
					$cacheValid = true;
					$this->_cssFiles = $cssFiles;
					$this->_jsFiles = $jsFiles;
					$this->_skins = $skins;
				}
			}
		}
		if (!$cacheValid) {
			$this->_cssFiles = [];
			$this->_jsFiles = [];
			$this->_skins = [];
			if (($dir = opendir($themePath)) === false) {
				throw new TIOException('theme_path_inexistent', $themePath);
			}
			while (($file = readdir($dir)) !== false) {
				if ($file === '.' || $file === '..' || $this->dyProcessFile(false, $file)) {
					continue;
				} elseif (basename($file, '.css') !== $file) {
					$this->_cssFiles[] = $themeUrl . DIRECTORY_SEPARATOR . $file;
				} elseif (basename($file, '.js') !== $file) {
					$this->_jsFiles[] = $themeUrl . DIRECTORY_SEPARATOR . $file;
				} elseif (basename($file, self::SKIN_FILE_EXT) !== $file) {
					$template = new TSkinTemplate(file_get_contents($themePath . DIRECTORY_SEPARATOR . $file), $themePath, $themePath . DIRECTORY_SEPARATOR . $file);
					foreach ($template->getItems() as $skin) {
						if (!isset($skin[2])) {  // a text string, ignored
							continue;
						} elseif ($skin[0] !== -1) {
							throw new TConfigurationException('theme_control_nested', $skin[1], dirname($themePath));
						}
						$type = $skin[1];
						$id = $skin[2]['skinid'] ?? 0;
						unset($skin[2]['skinid']);
						if (isset($this->_skins[$type][$id])) {
							throw new TConfigurationException('theme_skinid_duplicated', $type, $id, dirname($themePath));
						}
						/*
						foreach($skin[2] as $name=>$value)
						{
							if(is_array($value) && ($value[0]===TTemplate::CONFIG_DATABIND || $value[0]===TTemplate::CONFIG_PARAMETER))
								throw new TConfigurationException('theme_databind_forbidden',dirname($themePath),$type,$id);
						}
						*/
						$this->_skins[$type][$id] = $skin[2];
					}
				}
			}
			closedir($dir);
			sort($this->_cssFiles);
			sort($this->_jsFiles);
			$this->postProcessCssRTL();
			$this->dyThemeProcess();
			if ($cache !== null) {
				$cache->set(self::THEME_CACHE_PREFIX . $themePath, [$this->_skins, $this->_cssFiles, $this->_jsFiles, time()]);
			}
		}
	}

	/**
	 * @return string theme name
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string $value theme name
	 */
	protected function setName($value)
	{
		$this->_name = $value;
	}

	/**
	 * @return string the URL to the theme folder (without ending slash)
	 */
	public function getBaseUrl()
	{
		return $this->_themeUrl;
	}

	/**
	 * @param string $value the URL to the theme folder
	 */
	protected function setBaseUrl($value)
	{
		$this->_themeUrl = rtrim($value, DIRECTORY_SEPARATOR);
	}

	/**
	 * @return string the file path to the theme folder
	 */
	public function getBasePath()
	{
		return $this->_themePath;
	}

	/**
	 * @param string $value tthe file path to the theme folder
	 */
	protected function setBasePath($value)
	{
		$this->_themePath = $value;
	}

	/**
	 * @return array list of skins for the theme
	 */
	public function getSkins()
	{
		return $this->_skins;
	}

	/**
	 * @param array $value list of skins for the theme
	 */
	protected function setSkins($value)
	{
		$this->_skins = $value;
	}

	/**
	 * Applies the theme to a particular control.
	 * The control's class name and SkinID value will be used to
	 * identify which skin to be applied. If the control's SkinID is empty,
	 * the default skin will be applied.
	 * @param \Prado\Web\UI\TControl $control the control to be applied with a skin
	 * @throws TConfigurationException if any error happened during the skin application
	 * @return bool if a skin is successfully applied
	 */
	public function applySkin($control)
	{
		$type = $control::class;
		if (($id = $control->getSkinID()) === '') {
			$id = 0;
		}
		if (isset($this->_skins[$type][$id])) {
			foreach ($this->_skins[$type][$id] as $name => $value) {
				Prado::trace("Applying skin $name to $type", \Prado\Web\UI\TThemeManager::class);
				if (is_array($value)) {
					switch ($value[0]) {
						case TTemplate::CONFIG_EXPRESSION:
							$value = $this->evaluateExpression($value[1]);
							break;
						case TTemplate::CONFIG_ASSET:
							$value = $this->_themeUrl . '/' . ltrim($value[1], '/');
							break;
						case TTemplate::CONFIG_DATABIND:
							$control->bindProperty($name, $value[1]);
							break;
						case TTemplate::CONFIG_PARAMETER:
							$control->setSubProperty($name, $this->getApplication()->getParameters()->itemAt($value[1]));
							break;
						case TTemplate::CONFIG_TEMPLATE:
							$control->setSubProperty($name, $value[1]);
							break;
						case TTemplate::CONFIG_LOCALIZATION:
							$control->setSubProperty($name, Prado::localize($value[1]));
							break;
						default:
							throw new TConfigurationException('theme_tag_unexpected', $name, $value[0]);
							break;
					}
				}
				if (!is_array($value)) {
					if (strpos($name, '.') === false) {	// is simple property or custom attribute
						if ($control->hasProperty($name)) {
							if ($control->canSetProperty($name)) {
								$setter = 'set' . $name;
								$control->$setter($value);
							} else {
								throw new TConfigurationException('theme_property_readonly', $type, $name);
							}
						} else {
							throw new TConfigurationException('theme_property_undefined', $type, $name);
						}
					} else {	// complex property
						$control->setSubProperty($name, $value);
					}
				}
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * @return array list of CSS files (URL) in the theme
	 */
	public function getStyleSheetFiles()
	{
		return $this->_cssFiles;
	}

	/**
	 * @param array $value list of CSS files (URL) in the theme
	 */
	protected function setStyleSheetFiles($value)
	{
		$this->_cssFiles = $value;
	}

	/**
	 * @return array list of Javascript files (URL) in the theme
	 */
	public function getJavaScriptFiles()
	{
		return $this->_jsFiles;
	}

	/**
	 * @param array $value list of Javascript files (URL) in the theme
	 */
	protected function setJavaScriptFiles($value)
	{
		$this->_jsFiles = $value;
	}

	/**
	 * This post-processes the theme for RTL.  It loops through all the
	 * css files, removes all the '.*rtl.css' and '.*rtl.(media).css' files,
	 * then if there is globalization and globalization is RTL then re-adds
	 * the rtl css at the end.
	 * @since 4.2.0
	 */
	protected function postProcessCssRTL()
	{
		$rtlCss = [];
		foreach ($this->_cssFiles as $key => $url) {
			$segs = explode('.', basename($url));
			if ((substr_compare(strtolower($url), 'rtl.css', -7) === 0) ||
				(isset($segs[2]) && (substr_compare(strtolower($segs[count($segs) - 3]), 'rtl', -3) === 0))) {
				$rtlCss[] = $url;
				unset($this->_cssFiles[$key]);
			}
		}
		if ($globalization = $this->getApplication()->getGlobalization()) {
			if ($globalization->getIsCultureRTL()) {
				$this->_cssFiles = array_merge($this->_cssFiles, $rtlCss);
			}
		}
	}
}
