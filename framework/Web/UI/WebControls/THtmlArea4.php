<?php
/**
 * THtmlArea4 class file.
 *
 * @author Fabio Bas <ctrlaltca[at]gmail[dot]com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI\WebControls
 */

namespace Prado\Web\UI\WebControls;

use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TApplicationMode;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;

/**
 * THtmlArea4 class
 *
 * THtmlArea4 wraps the visual editing functionalities provided by the
 * version 4 of TinyMCE project {@link http://tinymce.com/}. It has been
 * developed as a plug'n'play substitute for {@link THtmlArea}, that is
 * based on the previous iteration (version 3) of the same project.
 * Please note that both components can't be used together in the same page.
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
 * See http://www.tinymce.com/wiki.php/Configuration
 * for a list of options. The options can be change/added as shown in the
 * following example.
 * <code>
 * <com:THtmlArea>
 *       <prop:Options>
 *         language : "de"
 *         plugins: [ advlist anchor autolink autoresize autosave bbcode charmap code contextmenu directionality emoticons fullpage fullscreen hr image importcss insertdatetime layer legacyoutput link lists media nonbreaking noneditable pagebreak paste preview print save searchreplace spellchecker tabfocus table template textcolor visualblocks visualchars wordcount ]
 *         toolbar: "undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image     | print preview media",
 *         statusbar: false
 *      </prop:Options>
 * </com:THtmlArea>
 * </code>
 *
 * @author Wei Zhuo <weizhuo[at]gmail[dot]com>
 * @package Prado\Web\UI\WebControls
 * @since 3.0
 */
class THtmlArea4 extends TTextBox
{
	/**
	 * @var array list of locale => language file pairs.
	 */
	private static $_langs = [
			'ar' => 'ar',
			'bg_BG' => 'bg_BG',
			'bs' => 'bs',
			'ca' => 'ca',
			'cs' => 'cs',
			'cy' => 'cy',
			'da' => 'da',
			'de' => 'de',
			'de_AT' => 'de_AT',
			'el' => 'el',
			'es' => 'es',
			'et' => 'et',
			'eu' => 'eu',
			'fa' => 'fa',
			'fi' => 'fi',
			'fo' => 'fo',
			'fr_FR' => 'fr_FR',
			'gl' => 'gl',
			'he_IL' => 'he_IL',
			'hr' => 'hr',
			'hu_HU' => 'hu_HU',
			'id' => 'id',
			'it' => 'it',
			'ja' => 'ja',
			'ka_GE' => 'ka_GE',
			'ko_KR' => 'ko_KR',
			'lb' => 'lb',
			'lt' => 'lt',
			'lv' => 'lv',
			'nb_NO' => 'nb_NO',
			'nl' => 'nl',
			'pl' => 'pl',
			'pt_BR' => 'pt_BR',
			'pt_PT' => 'pt_PT',
			'ro' => 'ro',
			'ru' => 'ru',
			'si_LK' => 'si_LK',
			'sk' => 'sk',
			'sl_SI' => 'sl_SI',
			'sr' => 'sr',
			'sv_SE' => 'sv_SE',
			'ta' => 'ta',
			'ta_IN' => 'ta_IN',
			'th_TH' => 'th_TH',
			'tr_TR' => 'tr_TR',
			'ug' => 'ug',
			'uk' => 'uk',
			'uk_UA' => 'uk_UA',
			'vi' => 'vi',
			'vi_VN' => 'vi_VN',
			'zh_CN' => 'zh_CN',
			'zh_TW' => 'zh_TW',
		];

	/**
	 * @var array list of default plugins to load, override using getAvailablePlugins();
	 */
	private static $_plugins = [
		'advlist',
		'anchor',
		'autolink',
		'autoresize',
		'autosave',
		'bbcode',
		'charmap',
		'code',
		'contextmenu',
		'directionality',
		'emoticons',
		'fullpage',
		'fullscreen',
		'hr',
		'image',
		'importcss',
		'insertdatetime',
		'layer',
		'legacyoutput',
		'link',
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
		'tabfocus',
		'table',
		'template',
		'textcolor',
		'visualblocks',
		'visualchars',
		'wordcount',
	];

	/**
	 * @var array default themes to load
	 */
	private static $_themes = [
		'modern',
	];

	/**
	 * Constructor.
	 * Sets default width and height.
	 */
	public function __construct()
	{
		$this->setWidth('600px');
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
	 * @return string current culture, e.g. de_AT.
	 */
	public function getCulture()
	{
		return $this->getViewState('Culture', '');
	}

	/**
	 * Sets the culture/language for the html area
	 * @param string $value a culture string, e.g. de_AT.
	 */
	public function setCulture($value)
	{
		$this->setViewState('Culture', $value, '');
	}

	/**
	 * Gets the list of options for the WYSIWYG (TinyMCE) editor
	 * @see http://www.tinymce.com/wiki.php/Configuration
	 * @return string options
	 */
	public function getOptions()
	{
		return $this->getViewState('Options', '');
	}

	/**
	 * Sets the list of options for the WYSIWYG (TinyMCE) editor
	 * @see http://www.tinymce.com/wiki.php/Configuration
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
		$scripts->registerPradoScript('htmlarea4');
		$this->copyCustomPlugins();
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
		$scripts->registerEndScript('prado:THtmlArea4' . $this->ClientID, $script);
	}

	protected function copyCustomPlugins()
	{
		if ($plugins = $this->getCustomPluginPath()) {
			$basepath = $this->getPage()->getClientScript()->getPradoScriptAssetPath('tinymce');
			$assets = $this->getApplication()->getAssetManager();
			$path = is_dir($plugins) ? $plugins : Prado::getPathOfNameSpace($plugins);
			$name = basename($path);
			$dest = $basepath . '/plugins/' . $name;
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
		//$options['theme'] = 'modern'; //default
		// mimic previous (tinyMCE3) sizing behaviour
		$options['width'] = $this->getWidth();
		$options['height'] = $this->getHeight();
		$options['resize'] = 'both';
		$options['menubar'] = false;
		if ($this->getReadOnly()) {
			$options['readonly'] = true;
			$options['toolbar'] = false;
			$options['menubar'] = false;
			$options['statusbar'] = false;
		}

		$options['extended_valid_elements'] = 'a[name|href|target|title|onclick],img[class|src|border=0|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name],hr[class|width|size|noshade],font[face|size|color|style],span[class|align|style]';

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
		return 'Prado.WebUI.THtmlArea4';
	}
}
