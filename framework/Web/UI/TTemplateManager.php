<?php
/**
 * TTemplateManager and TTemplate class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

use Prado\Prado;
use Prado\TApplicationMode;

/**
 * TTemplateManager class
 *
 * TTemplateManager manages the loading and parsing of control templates.
 *
 * There are two ways of loading a template, either by the associated template
 * control class name, or the template file name.
 * The former is via calling {@link getTemplateByClassName}, which tries to
 * locate the corresponding template file under the directory containing
 * the class file. The name of the template file is the class name with
 * the extension '.tpl'. To load a template from a template file path,
 * call {@link getTemplateByFileName}.
 *
 * By default, TTemplateManager is registered with {@link TPageService} as the
 * template manager module that can be accessed via {@link TPageService::getTemplateManager()}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TTemplateManager extends \Prado\TModule
{
	/**
	 * Template file extension
	 */
	const TEMPLATE_FILE_EXT = '.tpl';
	/**
	 * Prefix of the cache variable name for storing parsed templates
	 */
	const TEMPLATE_CACHE_PREFIX = 'prado:template:';

	/**
	 * Initializes the module.
	 * This method is required by IModule and is invoked by application.
	 * It starts output buffer if it is enabled.
	 * @param TXmlElement $config module configuration
	 */
	public function init($config)
	{
		$this->getService()->setTemplateManager($this);
	}

	/**
	 * Loads the template corresponding to the specified class name.
	 * @param mixed $className
	 * @return ITemplate template for the class name, null if template doesn't exist.
	 */
	public function getTemplateByClassName($className)
	{
		$class = new \ReflectionClass($className);
		$tplFile = dirname($class->getFileName()) . DIRECTORY_SEPARATOR . $class->getShortName() . self::TEMPLATE_FILE_EXT;
		return $this->getTemplateByFileName($tplFile);
	}

	/**
	 * Loads the template from the specified file.
	 * @param mixed $fileName
	 * @return ITemplate template parsed from the specified file, null if the file doesn't exist.
	 */
	public function getTemplateByFileName($fileName)
	{
		if (($fileName = $this->getLocalizedTemplate($fileName)) !== null) {
			Prado::trace("Loading template $fileName", '\Prado\Web\UI\TTemplateManager');
			if (($cache = $this->getApplication()->getCache()) === null) {
				return new TTemplate(file_get_contents($fileName), dirname($fileName), $fileName);
			} else {
				$array = $cache->get(self::TEMPLATE_CACHE_PREFIX . $fileName);
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
				$template = new TTemplate(file_get_contents($fileName), dirname($fileName), $fileName);
				$includedFiles = $template->getIncludedFiles();
				$timestamps = [];
				$timestamps[$fileName] = filemtime($fileName);
				foreach ($includedFiles as $includedFile) {
					$timestamps[$includedFile] = filemtime($includedFile);
				}
				$cache->set(self::TEMPLATE_CACHE_PREFIX . $fileName, [$template, $timestamps]);
				return $template;
			}
		} else {
			return null;
		}
	}

	/**
	 * Finds a localized template file.
	 * @param string $filename template file.
	 * @return null|string a localized template file if found, null otherwise.
	 */
	protected function getLocalizedTemplate($filename)
	{
		if (($app = $this->getApplication()->getGlobalization(false)) === null) {
			return is_file($filename) ? $filename : null;
		}
		foreach ($app->getLocalizedResource($filename) as $file) {
			if (($file = realpath($file)) !== false && is_file($file)) {
				return $file;
			}
		}
		return null;
	}
}
