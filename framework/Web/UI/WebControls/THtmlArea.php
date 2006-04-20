<?php
/**
 * THtmlArea class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2006 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

/**
 * Includes TTextBox class
 */
Prado::using('System.Web.UI.WebControls.TTextBox');

/**
 * THtmlArea class
 *
 * THtmlArea wraps the visual editting functionalities provided by the
 * TinyMCE project {@link http://tinymce.moxiecode.com/}.
 *
 * THtmlArea displays a WYSIWYG text area on the Web page for user input
 * in the HTML format. The text displayed in the THtmlArea component is
 * specified or determined by using the <b>Text</b> property.
 *
 * To enable the visual editting on the client side, set the property
 * <b>EnableVisualEdit</b> to true (which is default value).
 * To set the size of the editor when the visual editting is enabled,
 * set the <b>Width</b> and <b>Height</b> properties instead of
 * <b>Columns</b> and <b>Rows</b> because the latter has no meaning
 * under the situation.
 *
 * The default editor gives only the basic tool bar. To change or add
 * additional tool bars, use the Options property to add additional
 * editor options with each options on a new line.
 * See http://tinymce.moxiecode.com/tinymce/docs/index.html
 * for a list of options. The options can be change/added as shown in the
 * following example.
 * <code>
 * <com:THtmlArea>
 *      <prop:Options>
 *           plugins : "contextmenu,paste"
 *           language : "zh_cn"
 *      </prop:Options>
 * </com:THtmlArea>
 * </code>
 *
 * Compatibility
 * The client-side visual editting capability is supported by
 * Internet Explorer 5.0+ for Windows and Gecko-based browser.
 * If the browser does not support the visual editting,
 * a traditional textarea will be displayed.
 *
 * Browser support
 *
 * <code>
 *                    Windows XP        MacOS X 10.4
 * ----------------------------------------------------
 * MSIE 6                  OK
 * MSIE 5.5 SP2            OK
 * MSIE 5.0                OK
 * Mozilla 1.7.x           OK              OK
 * Firefox 1.0.x           OK              OK
 * Firefox 1.5b2           OK              OK
 * Safari 2.0 (412)                        OK(1)
 * Opera 9 Preview 1       OK(1)           OK(1)
 * ----------------------------------------------------
 *    * (1) - Partialy working
 * ----------------------------------------------------
 * </code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI.WebControls
 * @since 3.0
 */
class THtmlArea extends TTextBox
{
	// Qiang: need to clean up the following (too inefficient)
	private $langs = array(
		'da' => array('da'),
		'fa' => array('fa'),
		'hu' => array('hu'),
		'nb' => array('nb'),
		'pt_br' => array('pt_BR'),
		'sk' => array('sk'),
		'zh_tw_utf8' => array('zh_TW', 'zh_HK'),
		'ar' => array('ar'),
		'de' => array('de'),
		'fi' => array('fi'),
		'is' => array('is'),
		'nl' => array('nl'),
		'sv' => array('sv'),
		'ca' => array('ca'),
		'el' => array('el'),
		'fr' => array('fr'),
		'it' => array('it'),
		'nn' => array('nn'), //what is nn?
//		'ru' => array('ru'),
		'th' => array('th'),
		'cs' => array('cs'),
		'en' => array('en'),
		'fr_ca' => array('fr_CA'),
		'ja' => array('ja'),
		'pl' => array('pl'),
//		'ru_KOI8-R' => array('ru'), /// what is this?
		'zh_cn' => array('zh_CN'),
		'cy' => array('cy'), //what is this?
		'es' => array('es'),
		'he' => array('he'),
		'ko' => array('ko'),
		'pt' => array('pt'),
		'ru_UTF-8' => array('ru'),
		'tr' => array('tr'),
		'si' => array('si'),
//		'zh_tw' => array('zh_TW'),
		);

	/**
	 * Constructor.
	 * Sets default width and height.
	 */
	public function __construct()
	{
		$this->setWidth('450px');
		$this->setHeight('250px');
	}

	/**
	 * Overrides the parent implementation.
	 * TextMode for THtmlArea control is always 'MultiLine'
	 * @return string the behavior mode of the THtmlArea component.
	 */
	public function getTextMode()
	{
		return 'MultiLine';
	}

	/**
	 * Overrides the parent implementation.
	 * TextMode for THtmlArea is always 'MultiLine' and cannot be changed to others.
	 * @param string the text mode
	 */
	public function setTextMode($value)
	{
		throw new TInvalidOperationException("htmlarea_textmode_readonly");
	}

	/**
	 * @return boolean whether change of the content should cause postback. Return false if EnableVisualEdit is true.
	 */
	public function getAutoPostBack()
	{
		return $this->getEnableVisualEdit() ? false : parent::getAutoPostBack();
	}

	/**
	 * @return boolean whether to show WYSIWYG text editor. Defaults to true.
	 */
	public function getEnableVisualEdit()
	{
		return $this->getViewState('EnableVisualEdit',true);
	}

	/**
	 * Sets whether to show WYSIWYG text editor.
	 * @param boolean whether to show WYSIWYG text editor
	 */
	public function setEnableVisualEdit($value)
	{
		$this->setViewState('EnableVisualEdit',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * Gets the current culture.
	 * @return string current culture, e.g. en_AU.
	 */
	public function getCulture()
	{
		return $this->getViewState('Culture', '');
	}

	/**
	 * Sets the culture/language for the html area
	 * @param string a culture string, e.g. en_AU.
	 */
	public function setCulture($value)
	{
		$this->setViewState('Culture', $value, '');
	}

	/**
	 * Gets the list of options for the WYSIWYG (TinyMCE) editor
	 * @see http://tinymce.moxiecode.com/tinymce/docs/index.html
	 * @return string options
	 */
	public function getOptions()
	{
		return $this->getViewState('Options', '');
	}

	/**
	 * Sets the list of options for the WYSIWYG (TinyMCE) editor
	 * @see http://tinymce.moxiecode.com/tinymce/docs/index.html
	 * @param string options
	 */
	public function setOptions($value)
	{
		$this->setViewState('Options', $value, '');
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This method overrides the parent implementation by registering
	 * additional javacript code.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if($this->getEnableVisualEdit())
		{
			$writer->addAttribute('id',$this->getClientID());
			$this->registerEditorClientScript($writer);
		}
		parent::addAttributesToRender($writer);
	}

	/**
	 * Registers the editor javascript file and code to initialize the editor.
	 */
	protected function registerEditorClientScript($writer)
	{
		$scripts = $this->getPage()->getClientScript();
		if(!$scripts->isScriptFileRegistered('prado:THtmlArea'))
			$scripts->registerScriptFile('prado:THtmlArea', $this->getScriptUrl());
		$options = TJavaScript::encode($this->getEditorOptions());
		$script = "if(tinyMCE){ tinyMCE.init($options); }";
		$scripts->registerEndScript('prado:THtmlArea'.$this->ClientID,$script);
	}

	/**
	 * @return string editor script URL.
	 */
	protected function getScriptUrl()
	{
		return $this->getScriptDeploymentPath().'/tiny_mce/tiny_mce_gzip.php';
	}

	/**
	 * Gets the editor script base URL by publishing the tarred source via TTarAssetManager.
	 * @return string URL base path to the published editor script
	 */
	protected function getScriptDeploymentPath()
	{
		$tarfile = Prado::getPathOfNamespace('System.3rdParty.TinyMCE.tiny_mce', '.tar');
		$md5sum = Prado::getPathOfNamespace('System.3rdParty.TinyMCE.tiny_mce', '.md5');
		if($tarfile===null || $md5sum===null)
			throw new TConfigurationException('htmlarea_tarfile_invalid');
		return $this->getApplication()->getAssetManager()->publishTarFile($tarfile, $md5sum);
	}

	/**
	 * Default editor options gives basic tool bar only.
	 * @return array editor initialization options.
	 */
	protected function getEditorOptions()
	{
		$options['mode'] = 'exact';
		$options['elements'] = $this->getClientID();
		$options['language'] = $this->getLanguageSuffix($this->getCulture());
		$options['theme'] = 'advanced';
		$options['theme_advanced_buttons1'] = 'formatselect,fontselect,fontsizeselect,separator,bold,italic,underline,strikethrough,sub,sup';
		$options['theme_advanced_buttons2'] = 'justifyleft,justifycenter,justifyright,justifyfull,separator,bullist,numlist,separator,outdent,indent,separator,forecolor,backcolor,separator,hr,link,unlink,image,charmap,separator,removeformat,code,help';
		$options['theme_advanced_buttons3'] = ' ';
		$options['theme_advanced_toolbar_location'] = 'top';
		$options['theme_advanced_toolbar_align'] = 'left';
		$options['theme_advanced_path_location'] = 'bottom';
		$options['extended_valid_elements'] = 'a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]';

		$options = array_merge($options, $this->parseEditorOptions($this->getOptions()));
		return $options;
	}

	/**
	 * Parse additional options set in the Options property.
	 * @return array additional custom options
	 */
	protected function parseEditorOptions($string)
	{
		$options = array();
		$substrings = preg_split('/\n|,\n/', trim($string));
		foreach($substrings as $bits)
		{
			$option = explode(":",$bits);
			if(count($option) == 2)
				$options[trim($option[0])] = trim(preg_replace('/\'|"/','',  $option[1]));
		}
		return $options;
	}

	/**
	 * @return string localized editor interface language extension.
	 */
	protected function getLanguageSuffix($culture)
	{
		$app = $this->getApplication()->getGlobalization();
		if(empty($culture) && !is_null($app))
				$culture = $app->getCulture();
		$variants = array();
		if(!is_null($app))
			$variants = $app->getCultureVariants($culture);

		//default the variant to "en"
		if(count($variants) == 0)
			$variants[] = empty($culture) ? 'en' : strtolower($culture);

		// TODO: triple loops???
		foreach($this->langs as $js => $langs)
		{
			foreach($variants as $variant)
			{
				if(in_array($variant, $langs))
					return $js;
			}
		}

		return 'en';
	}
}

?>