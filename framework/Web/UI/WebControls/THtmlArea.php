<?php
/**
 * THtmlArea class file.
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

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
 * additional tool bars, use the {@link setOptions Options} property to add additional
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
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class THtmlArea extends TTextBox
{
	/**
	 * @var array list of locale => language file pairs.
	 */
	private static $_langs = [
			'ar' => 'ar',
			'az' => 'az',
			'be' => 'be',
			'bg' => 'bg',
			'bn' => 'bn',
			'br' => 'br',
			'bs' => 'bs',
			'ca' => 'ca',
			'ch' => 'ch',
			'cn' => 'cn',
			'cs' => 'cs',
			'cy' => 'cy',
			'da' => 'da',
			'de' => 'de',
			'dv' => 'dv',
			'el' => 'el',
			'en' => 'en',
			'eo' => 'eo',
			'es' => 'es',
			'et' => 'et',
			'eu' => 'eu',
			'fa' => 'fa',
			'fi' => 'fi',
			'fr' => 'fr',
			'gl' => 'gl',
			'gu' => 'gu',
			'he' => 'he',
			'hi' => 'hi',
			'hr' => 'hr',
			'hu' => 'hu',
			'hy' => 'hy',
			'ia' => 'ia',
			'id' => 'id',
			'is' => 'is',
			'it' => 'it',
			'ja' => 'ja',
			'ka' => 'ka',
			'kk' => 'kk',
			'kl' => 'kl',
			'km' => 'km',
			'ko' => 'ko',
			'lb' => 'lb',
			'lt' => 'lt',
			'lv' => 'lv',
			'mk' => 'mk',
			'ml' => 'ml',
			'mn' => 'mn',
			'ms' => 'ms',
			'my' => 'my',
			'nb' => 'nb',
			'nl' => 'nl',
			'nn' => 'nn',
			'no' => 'no',
			'pl' => 'pl',
			'ps' => 'ps',
			'pt' => 'pt',
			'ro' => 'ro',
			'ru' => 'ru',
			'sc' => 'sc',
			'se' => 'se',
			'si' => 'si',
			'sk' => 'sk',
			'sl' => 'sl',
			'sq' => 'sq',
			'sr' => 'sr',
			'sv' => 'sv',
			'sy' => 'sy',
			'ta' => 'ta',
			'te' => 'te',
			'th' => 'th',
			'tn' => 'tn',
			'tr' => 'tr',
			'tt' => 'tt',
			'tw' => 'tw',
			'uk' => 'vi',
			'ur' => 'vi',
			'vi' => 'vi',
			'zh_CN' => 'zh-cn',
			'zh_TW' => 'zh-tw',
			'zh' => 'zh',
			'zu' => 'zu',
		];

	/**
	 * @var array list of default plugins to load, override using getAvailablePlugins();
	 */
	private static $_plugins = [
		'advhr',
		'advimage',
		'advlink',
		'advlist',
		'autolink',
		'autoresize',
		'autosave',
		'bbcode',
		'contextmenu',
		'directionality',
		'emotions',
		'example',
		'fullpage',
		'fullscreen',
		'iespell',
		'inlinepopups',
		'insertdatetime',
		'layer',
		'legacyoutput',
		'lists',
		'media',
		'nonbreaking',
		'noneditable',
		'pagebreak',
		'paste',
		'preview',
		'print',
		'save',
		'searchreplace',
		'spellchecker',
		'style',
		'tabfocus',
		'table',
		'template',
		'visualchars',
		'wordc',
		'wordcount',
		'xhtmlxtras'
	];

	/**
	 * @var array default themes to load
	 */
	private static $_themes = [
		'simple',
		'advanced'
	];

	/**
	 * Constructor.
	 * Sets default width and height.
	 */
	public function __construct()
	{
		$this->setWidth('470px');
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
	 * @param string $value the text mode
	 */
	public function setTextMode($value)
	{
		throw new TInvalidOperationException("htmlarea_textmode_readonly");
	}

	/**
	 * @return bool whether change of the content should cause postback. Return false if EnableVisualEdit is true.
	 */
	public function getAutoPostBack()
	{
		return $this->getEnableVisualEdit() ? false : parent::getAutoPostBack();
	}

	/**
	 * @return bool whether to show WYSIWYG text editor. Defaults to true.
	 */
	public function getEnableVisualEdit()
	{
		return $this->getViewState('EnableVisualEdit', true);
	}

	/**
	 * Sets whether to show WYSIWYG text editor.
	 * @param bool $value whether to show WYSIWYG text editor
	 */
	public function setEnableVisualEdit($value)
	{
		$this->setViewState('EnableVisualEdit', TPropertyValue::ensureBoolean($value), true);
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
	 * @param string $value a culture string, e.g. en_AU.
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
	 * @param string $value options
	 */
	public function setOptions($value)
	{
		$this->setViewState('Options', $value, '');
	}

	/**
	 * @param string $value path to custom plugins to be copied.
	 */
	public function setCustomPluginPath($value)
	{
		$this->setViewState('CustomPluginPath', $value);
	}

	/**
	 * @return string path to custom plugins to be copied.
	 */
	public function getCustomPluginPath()
	{
		return $this->getViewState('CustomPluginPath');
	}

	/**
	 * Adds attribute name-value pairs to renderer.
	 * This method overrides the parent implementation by registering
	 * additional javacript code.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	protected function addAttributesToRender($writer)
	{
		if ($this->getEnableVisualEdit() && $this->getEnabled(true)) {
			$writer->addAttribute('id', $this->getClientID());
			$this->registerEditorClientScript($writer);
		}

		parent::addAttributesToRender($writer);
	}

	/**
	 * Returns a list of plugins to be loaded.
	 * Override this method to customize.
	 * @return array list of plugins to be loaded
	 */
	public function getAvailablePlugins()
	{
		return self::$_plugins;
	}

	/**
	 * @return array list of available themese
	 */
	public function getAvailableThemes()
	{
		return self::$_themes;
	}

	protected function loadJavascriptLibrary()
	{
		$scripts = $this->getPage()->getClientScript();
		$scripts->registerPradoScript('htmlarea');
		$scripts->registerScriptFile('prado:THtmlArea', $this->getScriptUrl());
	}

	/**
	 * Registers the editor javascript file and code to initialize the editor.
	 * @param mixed $writer
	 */
	protected function registerEditorClientScript($writer)
	{
		$this->loadJavascriptLibrary();
		$scripts = $this->getPage()->getClientScript();
		$options = [
			'EditorOptions' => $this->getEditorOptions()
		];

		$options = TJavaScript::encode($options, true, true);
		$script = "new {$this->getClientClassName()}($options)";
		$scripts->registerEndScript('prado:THtmlArea' . $this->ClientID, $script);
	}

	/**
	 * @return string editor script URL.
	 */
	protected function getScriptUrl()
	{
		return $this->getScriptDeploymentPath() . '/tiny_mce/tiny_mce.js';
	}

	/**
	 * Gets the editor script base URL by publishing the tarred source via TTarAssetManager.
	 * @return string URL base path to the published editor script
	 */
	protected function getScriptDeploymentPath()
	{
		$tarfile = Prado::getPathOfNamespace('Vendor.pradosoft.prado-tinymce3.tiny_mce', '.tar');
		$md5sum = Prado::getPathOfNamespace('Vendor.pradosoft.prado-tinymce3.tiny_mce', '.md5');
		if ($tarfile === null || $md5sum === null) {
			throw new TConfigurationException('htmlarea_tarfile_invalid');
		}
		$url = $this->getApplication()->getAssetManager()->publishTarFile($tarfile, $md5sum);
		$this->copyCustomPlugins($url);
		return $url;
	}

	protected function copyCustomPlugins($url)
	{
		if ($plugins = $this->getCustomPluginPath()) {
			$assets = $this->getApplication()->getAssetManager();
			$path = is_dir($plugins) ? $plugins : Prado::getPathOfNameSpace($plugins);
			$dest = $assets->getBasePath() . '/' . basename($url) . '/tiny_mce/plugins/' . basename($path);
			if (!is_dir($dest) || $this->getApplication()->getMode() !== TApplicationMode::Performance) {
				$assets->copyDirectory($path, $dest);
			}
		}
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

		//make it basic advanced to fit into 1 line of buttons.
		//$options['theme_advanced_buttons1'] = 'bold,italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright, justifyfull,separator,bullist,numlist,separator,undo,redo,separator,link,unlink,separator,charmap,separator,code,help';
		//$options['theme_advanced_buttons2'] = ' ';
		$options['theme_advanced_buttons1'] = 'formatselect,fontselect,fontsizeselect,separator,bold,italic,underline,strikethrough,sub,sup';
		$options['theme_advanced_buttons2'] = 'justifyleft,justifycenter,justifyright,justifyfull,separator,bullist,numlist,separator,outdent,indent,separator,forecolor,backcolor,separator,hr,link,unlink,image,charmap,separator,removeformat,code,help';
		$options['theme_advanced_buttons3'] = '';

		$options['theme_advanced_toolbar_location'] = 'top';
		$options['theme_advanced_toolbar_align'] = 'left';
		$options['theme_advanced_path_location'] = 'bottom';
		$options['extended_valid_elements'] = 'a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]';
		if ($this->getReadOnly()) {
			$options['readonly'] = true;
		}

		$options = array_merge($options, $this->parseEditorOptions($this->getOptions()));
		return $options;
	}

	/**
	 * Parse additional options set in the Options property.
	 * @param mixed $string
	 * @return array additional custom options
	 */
	protected function parseEditorOptions($string)
	{
		$options = [];
		$substrings = preg_split('/,\s*\n|\n/', trim($string));
		foreach ($substrings as $bits) {
			$option = explode(":", $bits, 2);

			if (count($option) == 2) {
				$value = trim(trim($option[1]), "'\"");
				if (($s = strtolower($value)) === 'false') {
					$value = false;
				} elseif ($s === 'true') {
					$value = true;
				}
				$options[trim($option[0])] = $value;
			}
		}
		return $options;
	}

	/**
	 * @param mixed $culture
	 * @return string localized editor interface language extension.
	 */
	protected function getLanguageSuffix($culture)
	{
		$app = $this->getApplication()->getGlobalization();
		if (empty($culture) && ($app !== null)) {
			$culture = $app->getCulture();
		}
		$variants = [];
		if ($app !== null) {
			$variants = $app->getCultureVariants($culture);
		}

		foreach ($variants as $variant) {
			if (isset(self::$_langs[$variant])) {
				return self::$_langs[$variant];
			}
		}

		return 'en';
	}

	/**
	 * Gets the name of the javascript class responsible for performing postback for this control.
	 * This method overrides the parent implementation.
	 * @return string the javascript class name
	 */
	protected function getClientClassName()
	{
		return 'Prado.WebUI.THtmlArea';
	}
}
