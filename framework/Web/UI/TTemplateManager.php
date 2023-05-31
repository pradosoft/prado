<?php
/**
 * TTemplateManager and TTemplate class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\Prado;
use Prado\TApplicationMode;
use Prado\TPropertyValue;

/**
 * TTemplateManager class
 *
 * TTemplateManager manages the loading and parsing of control templates.
 *
 * There are two ways of loading a template, either by the associated template
 * control class name, or the template file name.
 * The former is via calling {@see getTemplateByClassName}, which tries to
 * locate the corresponding template file under the directory containing
 * the class file. The name of the template file is the class name with
 * the extension '.tpl'. To load a template from a template file path,
 * call {@see getTemplateByFileName}.
 *
 * By default, TTemplateManager is registered with {@see TPageService} as the
 * template manager module that can be accessed via {@see TPageService::getTemplateManager()}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 * @method \Prado\Web\Services\TPageService getService()
 */
class TTemplateManager extends \Prado\TModule
{
	/**
	 * Template file extension
	 */
	public const TEMPLATE_FILE_EXT = '.tpl';
	/**
	 * Prefix of the cache variable name for storing parsed templates
	 */
	public const TEMPLATE_CACHE_PREFIX = 'prado:template:';
	/**
	 * @var string Default template class, default '\Prado\Web\UI\TTemplate'
	 * @since 4.2.3
	 */
	private string $_defaultTemplateClass = TTemplate::class;

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * It starts output buffer if it is enabled.
	 * @param \Prado\Xml\TXmlElement $config module configuration
	 */
	public function init($config)
	{
		Prado::getApplication()->setTemplateManager($this);
		parent::init($config);
	}

	/**
	 * Loads the template corresponding to the specified class name.
	 * @param mixed $className
	 * @return \Prado\Web\UI\TTemplate template for the class name, null if template doesn't exist.
	 */
	public function getTemplateByClassName($className)
	{
		$class = new \ReflectionClass($className);
		$tplFile = dirname($class->getFileName()) . DIRECTORY_SEPARATOR . $class->getShortName() . self::TEMPLATE_FILE_EXT;
		return $this->getTemplateByFileName($tplFile);
	}

	/**
	 * Loads the template from the specified file.
	 * @param string $fileName The file path and name of the template.
	 * @param string $tplClass, default null for the Default Template Class
	 * @param string $culture culture string, null to use current application culture
	 * @return \Prado\Web\UI\TTemplate or subclass template parsed from the specified file, null if the file doesn't exist.
	 */
	public function getTemplateByFileName($fileName, $tplClass = null, $culture = null)
	{
		if ($tplClass === null) {
			$tplClass = $this->_defaultTemplateClass;
		}
		if (!is_subclass_of($tplClass, ITemplate::class)) {
			return null;
		}
		if (($fileName = $this->getLocalizedTemplate($fileName, $culture)) !== null) {
			Prado::trace("Loading template $fileName", TTemplateManager::class);
			if (($cache = $this->getApplication()->getCache()) === null) {
				return new $tplClass(file_get_contents($fileName), dirname($fileName), $fileName);
			} else {
				$array = $cache->get(self::TEMPLATE_CACHE_PREFIX . $fileName . ':' . $tplClass);
				if (is_array($array)) {
					[$template, $timestamps] = $array;
					if ($this->getApplication()->getMode() === TApplicationMode::Performance) {
						return $template;
					}
					$cacheValid = true;
					foreach ($timestamps as $tplFile => $timestamp) {
						if (!is_file($tplFile) || filemtime($tplFile) > $timestamp) {
							$cacheValid = false;
							break;
						}
					}
					if ($cacheValid) {
						return $template;
					}
				}
				$template = new $tplClass(file_get_contents($fileName), dirname($fileName), $fileName);
				$includedFiles = $template->getIncludedFiles();
				$timestamps = [];
				$timestamps[$fileName] = filemtime($fileName);
				foreach ($includedFiles as $includedFile) {
					$timestamps[$includedFile] = filemtime($includedFile);
				}
				$cache->set(self::TEMPLATE_CACHE_PREFIX . $fileName . ':' . $tplClass, [$template, $timestamps]);
				return $template;
			}
		} else {
			return null;
		}
	}

	/**
	 * Finds a localized template file.
	 * @param string $filename template file.
	 * @param string $culture culture string, null to use current culture
	 * @return null|string a localized template file if found, null otherwise.
	 */
	protected function getLocalizedTemplate($filename, $culture = null)
	{
		if (($app = $this->getApplication()->getGlobalization(false)) === null) {
			return is_file($filename) ? $filename : null;
		}
		foreach ($app->getLocalizedResource($filename, $culture) as $file) {
			if (($file = realpath($file)) !== false && is_file($file)) {
				return $file;
			}
		}
		return null;
	}

	/**
	 * @return string the default template class.
	 * @since 4.2.3
	 */
	public function getDefaultTemplateClass(): string
	{
		return $this->_defaultTemplateClass;
	}

	/**
	 * @param string $tplClass the default template class.
	 * @since 4.2.3
	 */
	public function setDefaultTemplateClass($tplClass)
	{
		$this->_defaultTemplateClass = TPropertyValue::ensureString($tplClass);
	}
}
