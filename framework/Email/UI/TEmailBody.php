<?php

/**
 * TEmailBody class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Email\UI;

use Prado\Email\IEmailBody;
use Prado\Email\TEmail;
use Prado\Email\TEmailModule;
use Prado\Exceptions\TConfigurationException;
use Prado\IO\TTextWriter;
use Prado\TPropertyValue;
use Prado\Web\TMediaType;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Web\UI\ActiveControls\ICallbackEventHandler;
use Prado\Web\UI\IPostBackDataHandler;
use Prado\Web\UI\IPostBackEventHandler;
use Prado\Web\UI\ITheme;
use Prado\Web\UI\TClientScriptManager;
use Prado\Web\UI\TControl;
use Prado\Web\UI\TForm;
use Prado\Web\UI\THtmlWriter;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TTheme;

/**
 * TEmailBody class.
 *
 * TEmailBody renders the content of a {@see TEmail} from a ".email" template.  It
 * is the base class of the email classes of an application:
 *
 * <code>
 * // Emails/Order/Shipped.php
 * class Shipped extends TEmailBody
 * {
 *     public function setOrder($order) { ... }
 *     public function onLoad($param) { parent::onLoad($param); $this->Items->DataSource = ...; }
 * }
 * </code>
 * <code>
 * <!-- Emails/Order/Shipped.email -->
 * <%@ MasterClass="Application.Layouts.EmailLayout" Email.Subject="Your order shipped" %>
 * <com:TContent ID="Main">Hello {{NAME}}, ...</com:TContent>
 * </code>
 *
 * A body is a page to its controls: it runs the page life cycle once, from
 * onPreInit to onUnload, without a request, a response, view state, or a page
 * service, so it renders in a web request, a cron task, and the shell alike.  A
 * control that needs a postback or a callback cannot be used in it.
 *
 * {@see getEmail} is the email being rendered; the body and its controls set the
 * subject, files, and tracked links on it.  {@see renderEmail} sets the Html of
 * the email, and the Text when the email has a ".text" file and no Text.
 *
 * | Property | Effect |
 * |---|---|
 * | {@see setPublishStyle PublishStyle} | how template files reach the recipient ({@see TEmailPublishStyle}) |
 * | {@see setAbsoluteUrl AbsoluteUrl} | the base of the asset URLs of the URL publish style |
 * | {@see setInlineStyles InlineStyles} | copies style sheet rules into style attributes ({@see TEmailCssInliner}) |
 * | Theme, StyleSheetTheme | the style sheets of the theme are written into the head of the HTML |
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TEmailBody extends TPage implements IEmailBody
{
	/** @var ?TEmail the email being rendered. */
	private ?TEmail $_email = null;

	/** @var ?TEmailModule the module that created the body. */
	private ?TEmailModule $_emailModule = null;

	/** @var string the email path of the body. */
	private string $_emailPath = '';

	/** @var ?string the path of the plain-text file. */
	private ?string $_textFile = null;

	/** @var string the TEmailPublishStyle of template files. */
	private string $_publishStyle = TEmailPublishStyle::URL;

	/** @var ?string the base of the asset URLs. */
	private ?string $_absoluteUrl = null;

	/** @var bool whether style sheet rules are copied into style attributes. */
	private bool $_inlineStyles = true;

	/** @var null|ITheme|string the theme or its name. */
	private $_emailTheme;

	/** @var null|ITheme|string the style sheet theme or its name. */
	private $_emailStyleSheetTheme;

	/** @var ?TClientScriptManager the client script manager. */
	private ?TClientScriptManager $_emailClientScript = null;

	/** @var ?TForm the form on the body. */
	private ?TForm $_emailForm = null;

	/** @var bool whether a form is rendering. */
	private bool $_emailInFormRender = false;

	/**
	 * Turns off view state, client scripts, and state validation, which an email has no use for.
	 */
	public function __construct()
	{
		$this->setEnableViewState(false);
		$this->setClientSupportsJavaScript(false);
		$this->setEnableStateValidation(false);
		parent::__construct();
	}

	/**
	 * Renders the template into the Html of the email, and sets the Text from the
	 * ".text" file when the email has no Text.  A body renders once.
	 * @param TEmail $email the email that receives the content.
	 */
	public function renderEmail(TEmail $email): void
	{
		$this->_email = $email;
		$writer = new TTextWriter();
		$this->processNormalRequest(new THtmlWriter($writer));
		$html = $writer->flush();
		if ($html !== '') {
			$email->setHtml($this->getInlineStyles() ? (new TEmailCssInliner())->inline($html) : $html);
		}
		if ($email->getText() === null && $this->_textFile !== null && is_file($this->_textFile)) {
			$email->setText((string) file_get_contents($this->_textFile));
		}
	}

	/**
	 * @return TEmail the email being rendered; a body used alone gets a new one.
	 */
	public function getEmail(): TEmail
	{
		return $this->_email ??= new TEmail();
	}

	/**
	 * @param TEmail $email the email being rendered.
	 */
	public function setEmail(TEmail $email)
	{
		$this->_email = $email;
	}

	/**
	 * @return ?TEmailModule the module that created the body, null for a body used alone.
	 */
	public function getEmailModule(): ?TEmailModule
	{
		return $this->_emailModule;
	}

	/**
	 * @param ?TEmailModule $module the module that created the body.
	 */
	public function setEmailModule(?TEmailModule $module)
	{
		$this->_emailModule = $module;
	}

	/**
	 * @return string the email path of the body, e.g. "Order.Shipped". Defaults to ''.
	 */
	public function getEmailPath(): string
	{
		return $this->_emailPath;
	}

	/**
	 * @param string $value the email path of the body.
	 */
	public function setEmailPath($value)
	{
		$this->_emailPath = TPropertyValue::ensureString($value);
	}

	/**
	 * @return ?string the path of the plain-text file, null when there is none.
	 */
	public function getTextFile(): ?string
	{
		return $this->_textFile;
	}

	/**
	 * @param ?string $value the path of the file that holds the plain text of the email.
	 */
	public function setTextFile($value)
	{
		$this->_textFile = ($value === null || $value === '') ? null : TPropertyValue::ensureString($value);
	}

	/**
	 * Publishes a file by the {@see getPublishStyle PublishStyle} and returns its URL in the email.
	 * @param string $fullPath the absolute path of the file.
	 * @param bool $checkTimestamp whether a published asset is republished when the file is newer.
	 * @throws TConfigurationException when the style is URL and the AbsoluteUrl is not set.
	 * @return string an absolute URL, "cid:...", or a data URI.
	 */
	public function publishEmailFile($fullPath, $checkTimestamp = false)
	{
		$style = $this->getPublishStyle();
		if ($style === TEmailPublishStyle::Embed) {
			$type = TMediaType::mimeTypeFromFilename($fullPath) ?? 'application/octet-stream';
			return 'data:' . $type . ';base64,' . base64_encode((string) file_get_contents($fullPath));
		}
		if ($style === TEmailPublishStyle::Attachment) {
			return 'cid:' . $this->getEmail()->embed($fullPath);
		}
		if (($base = $this->getAbsoluteUrl()) === null) {
			throw new TConfigurationException('emailbody_absoluteurl_required');
		}
		$url = $this->getApplication()->getAssetManager()->publishFilePath($fullPath, $checkTimestamp);
		if (preg_match('/^[a-z][a-z0-9+.\-]*:\/\//i', $url)) {
			return $url;
		}
		return $base . '/' . ltrim(preg_replace('/^\.\//', '', $url), '/');
	}

	/**
	 * Publishes a file by the {@see getPublishStyle PublishStyle}.  {@see publishAsset}
	 * and the `<%~ file %>` template tag call it.
	 * @param string $fullPath the absolute path of the file.
	 * @param bool $checkTimestamp whether a published asset is republished when the file is newer.
	 * @return string the URL of the file in the email.
	 */
	public function publishFilePath($fullPath, $checkTimestamp = false)
	{
		return $this->publishEmailFile($fullPath, $checkTimestamp);
	}

	/**
	 * @return string the {@see TEmailPublishStyle} of template files. Defaults to TEmailPublishStyle::URL.
	 */
	public function getPublishStyle(): string
	{
		return $this->_publishStyle;
	}

	/**
	 * @param string $value the {@see TEmailPublishStyle} of template files.
	 */
	public function setPublishStyle($value)
	{
		$this->_publishStyle = TPropertyValue::ensureEnum($value, TEmailPublishStyle::class);
	}

	/**
	 * @return ?string the base of the asset URLs, without a trailing slash; null when it is not set.
	 */
	public function getAbsoluteUrl(): ?string
	{
		return $this->_absoluteUrl;
	}

	/**
	 * @param ?string $value the base of the asset URLs, e.g. "https://www.example.com"; the URL publish style requires it.
	 */
	public function setAbsoluteUrl($value)
	{
		$value = rtrim(TPropertyValue::ensureString($value), '/');
		$this->_absoluteUrl = $value === '' ? null : $value;
	}

	/**
	 * @return bool whether style sheet rules are copied into style attributes. Defaults to true.
	 */
	public function getInlineStyles(): bool
	{
		return $this->_inlineStyles;
	}

	/**
	 * @param bool|string $value whether style sheet rules are copied into style attributes.
	 */
	public function setInlineStyles($value)
	{
		$this->_inlineStyles = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Checks the controls and writes the style sheets of the themes into the head.
	 * A mail client does not load a linked style sheet.
	 * @param mixed $param the event parameter.
	 * @throws TConfigurationException when a control needs a postback or a callback.
	 */
	public function onPreRenderComplete($param)
	{
		$this->ensureEmailControls($this);
		$this->raiseEvent('OnPreRenderComplete', $this, $param);
		$style = '';
		foreach ([$this->getTheme(), $this->getStyleSheetTheme()] as $theme) {
			if ($theme instanceof TTheme) {
				$length = strlen($theme->getBaseUrl());
				foreach ($theme->getStyleSheetFiles() as $url) {
					$file = $theme->getBasePath() . str_replace('/', DIRECTORY_SEPARATOR, substr($url, $length));
					$style .= is_file($file) ? file_get_contents($file) . "\n" : '';
				}
			}
		}
		if ($style !== '' && ($head = $this->getHead()) !== null) {
			$head->getControls()->add("<style>\n" . $style . "</style>");
		}
	}

	/**
	 * Checks that no descendant needs a postback or a callback, which an email cannot make.
	 * @param TControl $control the control whose descendants are checked.
	 * @throws TConfigurationException when such a control is found.
	 */
	protected function ensureEmailControls(TControl $control): void
	{
		foreach ($control->getHasControls() ? $control->getControls() : [] as $child) {
			if (!($child instanceof TControl)) {
				continue;
			}
			if ($child instanceof IActiveControl || $child instanceof ICallbackEventHandler || $child instanceof IPostBackEventHandler || $child instanceof IPostBackDataHandler) {
				throw new TConfigurationException('emailbody_control_unsupported', $child::class, $child->getUniqueID());
			}
			$this->ensureEmailControls($child);
		}
	}

	/**
	 * @return ?ITheme the theme, from the theme manager of the application.
	 */
	public function getTheme()
	{
		if (is_string($this->_emailTheme)) {
			$this->_emailTheme = $this->getApplication()->getThemeManager()->getTheme($this->_emailTheme);
		}
		return $this->_emailTheme;
	}

	/**
	 * @param null|ITheme|string $value the theme or its name.
	 */
	public function setTheme($value)
	{
		$this->_emailTheme = empty($value) ? null : $value;
	}

	/**
	 * @return ?ITheme the style sheet theme, from the theme manager of the application.
	 */
	public function getStyleSheetTheme()
	{
		if (is_string($this->_emailStyleSheetTheme)) {
			$this->_emailStyleSheetTheme = $this->getApplication()->getThemeManager()->getTheme($this->_emailStyleSheetTheme);
		}
		return $this->_emailStyleSheetTheme;
	}

	/**
	 * @param null|ITheme|string $value the style sheet theme or its name.
	 */
	public function setStyleSheetTheme($value)
	{
		$this->_emailStyleSheetTheme = empty($value) ? null : $value;
	}

	/**
	 * @return TClientScriptManager the client script manager; its scripts are not rendered.
	 */
	public function getClientScript()
	{
		return $this->_emailClientScript ??= new TClientScriptManager($this);
	}

	/**
	 * @return bool false; a body does not listen to global events.
	 */
	public function getAutoGlobalListen()
	{
		return false;
	}

	/**
	 * Does nothing; {@see renderEmail} runs the body.
	 * @param THtmlWriter $writer the writer.
	 */
	public function run($writer)
	{
	}

	/**
	 * @return bool false; an email has no postback.
	 */
	public function getIsPostBack()
	{
		return false;
	}

	/**
	 * @return bool false; an email has no callback.
	 */
	public function getIsCallback()
	{
		return false;
	}

	/**
	 * @return ?TControl null; an email has no postback.
	 */
	public function getPostBackEventTarget()
	{
		return null;
	}

	/**
	 * @return ?string null; an email has no postback.
	 */
	public function getPostBackEventParameter()
	{
		return null;
	}

	/**
	 * Does nothing; an email has no input to validate.
	 * @param ?string $validationGroup the validation group.
	 */
	public function validate($validationGroup = null)
	{
	}

	/**
	 * @return bool true; an email has no input to validate.
	 */
	public function getIsValid()
	{
		return true;
	}

	/**
	 * Does nothing; a body keeps no control state.
	 */
	public function saveState()
	{
	}

	/**
	 * Does nothing; a body keeps no control state.
	 */
	public function loadState()
	{
	}

	/**
	 * Does nothing; a body keeps no page state.
	 */
	protected function loadPageState()
	{
	}

	/**
	 * Does nothing; a body keeps no page state.
	 */
	protected function savePageState()
	{
	}

	/**
	 * Does nothing; an email has no post data.
	 * @param mixed $postData the post data.
	 * @param bool $beforeLoad whether the call is before onLoad.
	 */
	protected function processPostData($postData, $beforeLoad)
	{
	}

	/**
	 * @return ?TForm the form on the body.
	 */
	public function getForm()
	{
		return $this->_emailForm;
	}

	/**
	 * @param TForm $form the form on the body.
	 */
	public function setForm(TForm $form)
	{
		$this->_emailForm = $form;
	}

	/**
	 * @return bool whether a form is rendering.
	 */
	public function getInFormRender()
	{
		return $this->_emailInFormRender;
	}

	/**
	 * Does nothing; a control in an email may render outside a form.
	 * @param TControl $control the control being rendered.
	 */
	public function ensureRenderInForm($control)
	{
	}

	/**
	 * Marks the start of a form rendering.
	 * @param THtmlWriter $writer the writer.
	 */
	public function beginFormRender($writer)
	{
		$this->_emailInFormRender = true;
	}

	/**
	 * Marks the end of a form rendering.
	 * @param THtmlWriter $writer the writer.
	 */
	public function endFormRender($writer)
	{
		$this->_emailInFormRender = false;
	}
}
