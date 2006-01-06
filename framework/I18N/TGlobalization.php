<?php
/**
 * TGlobalization class file.
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.I18N
 */


/**
 * TGlobalization contains settings for Culture, Charset, ContentType
 * and TranslationConfiguration.
 *
 * TGlobalization can be subclassed to change how the Culture, Charset
 * are determined. See TGlobalizationAutoDetect for example of
 * setting the Culture based on browser settings.
 *
 * @author Wei Zhuo<weizhuo[at]gmail[dot]com>
 * @version $Revision: 1.66 $  $Date: ${DATE} ${TIME} $
 * @package System.I18N
 * @since 3.0
 */
class TGlobalization extends TModule
{
	/**
	 * Default character set is 'UTF-8'.
	 * @var string 
	 */
	private $_defaultCharset = 'UTF-8';

	/**
	 * Default culture is 'en'.
	 * @var string	 
	 */
	private $_defaultCulture = 'en';

	/**
	 * Default content type is 'text/html'
	 * @var ${type}
	 */
	private $_defaultContentType = 'text/html';

	/**
	 * Translation source parameters.
	 * @var TMap
	 */
	private $_translation;

	/**
	 * The current charset.
	 * @var string 
	 */	
	protected $_charset='UTF-8';

	/**
	 * The current culture.
	 * @var string 
	 */
	protected $_culture='en';

	/**
	 * The content type for the http header
	 * @var string
	 */
	protected $_contentType='text/html';

	/**
	 * Initialize the Culture and Charset for this application.
	 * You should override this method if you want a different way of
	 * setting the Culture and/or Charset for your application.
	 * If you override this method, call parent::init($xml) first.
	 * @param TXmlElement application configuration
	 */
	public function init($xml)
	{		
		$this->_defaultContentType = $this->getContentType();
		$this->_defaultCharset = $this->getCharset();
		$this->_defaultCulture = $this->getCulture();

		$config = $xml->getElementByTagName('translation')->getAttributes();
		$this->setTranslationConfiguration($config);
		$this->getApplication()->setGlobalization($this);
	}

	public function getCulture()
	{
		return $this->_culture;
	}

	public function setCulture($culture)
	{
		$this->_culture = str_replace('-','_',$culture);
	}

	public function getCharset()
	{
		return $this->_charset;
	}

	public function setCharset($charset)
	{
		$this->_charset = $charset;
	}

	public function setContentType($type)
	{
		$this->_contentType = $type;
	}

	public function getContentType()
	{
		return $this->_contentType;
	}

	/**
	 * @return TMap translation source configuration.
	 */
	public function getTranslationConfiguration()
	{
		return $this->_translation;
	}

	/**
	 * Sets the translation configuration. Example configuration:
	 * <code>
	 * $config['type'] = 'XLIFF'; //XLIFF, gettext, mysql or sqlite
	 * $config['source'] = 'Path.to.directory'; //or database connection string
	 * $config['catalogue'] = 'messages'; //default catalog
	 * $config['autosave'] = 'true'; //save untranslated message
	 * $config['cache'] = 'true'; //cache translated message
	 * </code>
	 * Throws exception is source is not found.
	 * @param TMap configuration options
	 * @return ${return}
	 */
	protected function setTranslationConfiguration(TMap $config)
	{
		if($config['type'] == 'XLIFF' || $config['type'] == 'gettext')
		{
			$config['source'] = Prado::getPathOfNamespace($config['source']);
			if(!is_dir($config['source']))
				throw new TException("invalid source dir '{$config['source']}'");
		}
		if($config['cache'])
			$config['cache'] = $this->getApplication()->getRunTimePath().'/i18n';
		$this->_translation = $config;
	}

	/**
	 * @return string default charset set in application.xml
	 */
	public function getDefaultCharset()
	{
		return $this->_defaultCharset;
	}

	/**
	 * @return string default culture set in application.xml
	 */
	public function getDefaultCulture()
	{
		return $this->_defaultCulture;
	}

	/**
	 * @return string default content-type set in application.xml
	 */
	public function getDefaultContentType()
	{
		return $this->_defaultContentType;
	}

	/**
	 * Gets all the variants of a specific culture. If the parameter
	 * $culture is null, the current culture is used.
	 * @param string $culture the Culture string
	 * @return array variants of the culture.
	 */
	public function getCultureVariants($culture=null)
	{
		if(is_null($culture)) $culture = $this->getCulture();
		$variants = explode('_', $culture);
		$result = array();
		for(; count($variants) > 0; array_pop($variants))
			$result[] = implode('_', $variants);
		return $result;
	}

	/**
	 * Returns a list of possible localized files. Example
	 * <code>
	 * $files = $app->getLocalizedResource("path/to/Home.page","en_US");
	 * </code>
	 * will return
	 * <pre>
	 * array
	 *   0 => 'path/to/en_US/Home.page'
	 *   1 => 'path/to/en/Home.page'
	 *   2 => 'path/to/Home.en_US.page'
	 *   3 => 'path/to/Home.en.page'
	 *   4 => 'path/to/Home.page'
	 * </pre>
	 * Note that you still need to verify the existance of these files.
	 * @param string filename
	 * @param string culture string, null to use current culture
	 * @return array list of possible localized resource files.
	 */
	public function getLocalizedResource($file,$culture=null)
	{
		$files = array();
		$variants = $this->getCultureVariants($culture);
		$path = pathinfo($file);
		foreach($variants as $variant)
			$files[] = $path['dirname'].'/'.$variant.'/'.$path['basename'];
		$filename = substr($path['basename'],0,strrpos($path['basename'],'.'));
		foreach($variants as $variant)
			$files[] = $path['dirname'].'/'.$filename.'.'.$variant.'.'.$path['extension'];
		$files[] = $file;
		return $files;
	}

}

?>