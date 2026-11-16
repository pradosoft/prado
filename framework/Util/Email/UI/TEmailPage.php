<?php

/**
 * TEmailPage class file
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util\Email\UI;

use Prado\Exceptions\TConfigurationException;
use Prado\IO\TTextWriter;
use Prado\TPropertyValue;
use Prado\Util\Email\TEmailAddress;
use Prado\Util\Email\TEmailModule;
use Prado\Util\Email\TEmail;
use Prado\Web\UI\ITheme;
use Prado\Web\UI\TPage;
use Prado\Web\UI\TForm;
use Prado\Web\UI\THtmlWriter;
use Prado\Collections\TMap;

/**
 * TEmailPage class
 *
 * This is a subclass of TPage without any dependency upon Request, Response,
 * Session, etc.  It renders the message body from its TTemplateControl.  It acts
 * as a passthrough to its internal {@link getEmail TEmail}.
 *
 * If there is no text version of the email, the HTML is rendered into text.
 *
 * All the fields for an email are present: To, From, Sender, ReplyTo, ReturnPath,
 * CC, BCC, date, priority, charset, Subject, and attachments.
 *
 * An email can have 3 different file publish styles: URL, Attachment, and Embed.
 *    * URL publish style publish assets like normal web pages, but an AbsoluteUrl
 * 		is required for the asset URL to work properly
 *    * Attachment publish style attaches all page assets to the email as file
 *		Attachments.
 *	  * Embed publish style encodes the asset data into the webpage.
 *
 * The ".email" can have directives specifying its Subject.  The associated ".text"
 * file is the text only version of the email.
 *
 * All emails are localized like TPage for globalization.
 *
 * Methods that TEmailPage does not define pass through to its TEmail without
 * rendering the body; these are the ones the email controls use.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.3.3
 * @method ?\Prado\Util\Email\TEmailModule getEmailerModule()
 * @method void setEmailerModule(\Prado\Util\Email\TEmailModule $module)
 * @method string getTrackingId()
 * @method int addTrackedLink(string $url)
 * @method void setListUnsubscribe(?string $url, ?string $mailto = null, bool $oneClick = true)
 * @method void addAttachment(string $filename, array|string $attachment)
 * @method void addInlineAttachment(string $cid, string $path, ?string $mime = null)
 * @method void setTextBody(?string $textBody)
 * @method void setTo(mixed $address)
 */
class TEmailPage extends TPage
{
	/** @var \Prado\Util\Email\TEmail  */
	private $_email;

	/** @var null|string the cached body */
	private $_body;

	/** @var bool cache the body of the message, render once */
	private $_cacheBody = true;

	/** @var string the style of publishing TTemplate files/images */
	private $_publishStyle = TEmailPublishStyle::URL;

	/** @var string absolute base url of the published files */
	private $_absoluteUrl;

	/** @var bool whether style sheet rules are copied into style attributes */
	private $_inlineStyles = true;

	/* ** minimum TPage Implementation ** */
	/** @var TForm form instance */
	private $_form;

	/** @var bool whether the current rendering is within a form */
	protected $_inFormRender = false;

	/**
	 * Constructor.
	 * Disables View State, Client doesn't support JavaScript, and disable state
	 * validation.  This creates a TEmail to load up
	 * Derived classes must call parent implementation.
	 */
	public function __construct()
	{
		$this->setEnableViewState(false);
		$this->setClientSupportsJavaScript(false);
		$this->setEnableStateValidation(false);
		$this->_email = new TEmail();
		parent::__construct();
	}

	/**
	 * This generates the contents of the page body and returns the {@link TEmail}.
	 * @return \Prado\Util\Email\TEmail Email of the page.
	 */
	public function getEmail()
	{
		if (!$this->getCacheBody() || !$this->_email->getBody()) {
			$this->_email->setBody($this->getBody());
		}
		return $this->_email;
	}

	/**
	 * Sets the email of the TEmailPage.
	 * @param \Prado\Util\Email\TEmail $email Email of the page.
	 */
	public function setEmail($email)
	{
		$this->_email = $email;
	}

	/**
	 * Generates the body and sends the email.
	 * @param null|array $customData text replacement ["$key" => $value]
	 * @param bool $many
	 * @return bool successful sent.
	 */
	public function send($customData = null, $many = false)
	{
		$email = $this->getEmail();
		return $email->send($customData, $many);
	}

	/**
	 * We want a TEmailPage to look like its TEmail.
	 * @param string $method method name being called
	 * @param array $args arguments to the method
	 * @return mixed return values
	 */
	public function __call($method, $args)
	{
		if ($this->_email && method_exists($this->_email, $method)) {
			return call_user_func_array([$this->_email, $method], $args);
		}
		return parent::__call($method, $args);
	}

	/**
	 * We want a TEmailPage to look like its TEmail.
	 * @param string $name name of the variable to get.
	 * @return mixed return values
	 */
	public function __get($name)
	{
		if ($this->_email && method_exists($this->_email, $getter = 'get' . $name)) {
			return $this->_email->$getter();
		}
		return parent::__get($name);
	}

	/**
	 * We want a TEmailPage to look like its TEmail.
	 * @param string $name name of the variable being set.
	 * @param null|mixed $value the value being set.
	 */
	public function __set($name, $value)
	{
		if ($this->_email && method_exists($this->_email, $setter = 'set' . $name)) {
			$this->_email->$setter($value);
			return;
		}
		parent::__set($name, $value);
	}

	/**
	 * We want a TEmailPage to look like its TEmail.
	 * @param string $name name of the variable being checked for null.
	 * @return bool is the value set
	 */
	public function __isset($name)
	{
		if ($this->_email && method_exists($this->_email, $getter = 'get' . $name)) {
			return $this->_email->$getter() !== null;
		}
		return parent::__isset($name);
	}


	/**
	 * Renders the body once, or on every call when CacheBody is false, and inlines its styles.
	 * @return string the body rendered from the template
	 */
	protected function getBody()
	{
		if (!$this->getCacheBody() || !$this->_body) {
			$writer = new TTextWriter();
			$this->processNormalRequest(new THtmlWriter($writer));
			$body = $writer->flush();
			if ($this->getInlineStyles()) {
				$body = (new TEmailCssInliner())->inline($body);
			}
			$this->_body = $body;
		}
		return $this->_body;
	}

	/**
	 * @return bool whether style sheet rules are copied into style attributes. Defaults to true.
	 * @see TEmailCssInliner
	 */
	public function getInlineStyles()
	{
		return $this->_inlineStyles;
	}

	/**
	 * This can be set in the template directives.
	 * @param bool $value whether style sheet rules are copied into style attributes.
	 */
	public function setInlineStyles($value)
	{
		$this->_inlineStyles = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * Publishes a file by the {@see getPublishStyle PublishStyle} of the email and returns its URL.
	 *
	 * | PublishStyle | URL |
	 * |---|---|
	 * | URL | The asset URL, prefixed with {@see getAbsoluteUrl AbsoluteUrl}. |
	 * | Attachment | "cid:..." of an inline attachment added to the email. |
	 * | Embed | A base64 data URI. |
	 *
	 * @param string $fullPath the absolute path of the file.
	 * @param bool $checkTimestamp whether a published asset is republished when the file is newer.
	 * @throws TConfigurationException when the style is URL and AbsoluteUrl is not set.
	 * @return string the URL of the file in the email.
	 */
	public function publishEmailFile($fullPath, $checkTimestamp = false)
	{
		$style = $this->getPublishStyle();
		if ($style === TEmailPublishStyle::Embed) {
			return 'data:' . (mime_content_type($fullPath) ?: 'application/octet-stream') . ';base64,' . base64_encode(file_get_contents($fullPath));
		} elseif ($style === TEmailPublishStyle::Attachment) {
			$cid = substr(md5($fullPath), 0, 8) . '.' . basename($fullPath);
			$this->_email->addInlineAttachment($cid, $fullPath);
			return 'cid:' . $cid;
		}
		if (!$this->getAbsoluteUrl()) {
			throw new TConfigurationException('emailtemplate_absolute_url_required');
		}
		$url = $this->getApplication()->getAssetManager()->publishFilePath($fullPath, $checkTimestamp);
		if ($url !== '' && $url[0] === '.' && ($url[1] ?? '') !== '.') {
			$url = substr($url, 1);
		}
		return $this->getAbsoluteUrl() . $url;
	}

	/**
	 * Publishes a file by the {@see getPublishStyle PublishStyle} of the email.  Template
	 * asset expressions, `<%~ ./file %>`, and {@see publishAsset} call this method.
	 * @param string $fullPath the absolute path of the file.
	 * @param bool $checkTimestamp whether a published asset is republished when the file is newer.
	 * @return string the URL of the file in the email.
	 */
	public function publishFilePath($fullPath, $checkTimestamp = false)
	{
		return $this->publishEmailFile($fullPath, $checkTimestamp);
	}

	/**
	 * Checks that no control on the page needs a postback or callback.  An email cannot
	 * post back to the page that rendered it.
	 * @param \Prado\Web\UI\TControl $control the control whose descendants are checked.
	 * @throws TConfigurationException when a postback, callback, or active control is found.
	 */
	protected function ensureEmailControls($control)
	{
		foreach ($control->getHasControls() ? $control->getControls() : [] as $child) {
			if (!($child instanceof \Prado\Web\UI\TControl)) {
				continue;
			}
			if ($child instanceof \Prado\Web\UI\ActiveControls\IActiveControl
				|| $child instanceof \Prado\Web\UI\ActiveControls\ICallbackEventHandler
				|| $child instanceof \Prado\Web\UI\IPostBackEventHandler
				|| $child instanceof \Prado\Web\UI\IPostBackDataHandler) {
				throw new TConfigurationException('emailpage_control_unsupported', $child::class, $child->getUniqueID());
			}
			$this->ensureEmailControls($child);
		}
	}

	/**
	 * @return bool render the body once and cache.
	 */
	public function getCacheBody()
	{
		return $this->_cacheBody;
	}

	/**
	 * This can be set in the template directives.
	 * @param bool $cacheBody render the body once and cache.
	 */
	public function setCacheBody($cacheBody)
	{
		$this->_cacheBody = TPropertyValue::ensureBoolean($cacheBody);
	}


	/**
	 * @return string the file Attachments of the email
	 */
	public function getPublishStyle()
	{
		return $this->_publishStyle;
	}

	/**
	 * @param string $style the file Attachments of the email
	 */
	public function setPublishStyle($style)
	{
		$this->_publishStyle = TPropertyValue::ensureEnum($style, '\Prado\Util\Email\UI\TEmailPublishStyle');
	}


	/**
	 * @return ?string Base URL for absolute links in the email, without a trailing slash.
	 */
	public function getAbsoluteUrl()
	{
		return $this->_absoluteUrl;
	}

	/**
	 * @param string $url Base URL for absolute links in the email; a trailing slash is removed.
	 */
	public function setAbsoluteUrl($url)
	{
		if (substr($url, -1) === '/') {
			$url = substr($url, 0, strlen($url) - 1);
		}
		$this->_absoluteUrl = $url;
	}



	/** *********************************
	 * 	  Capture TPage to work without Service, Request, or Response.
	 * Hard return some of the responses for Emails
	 */

	/**
	 * TEmailPage does not autoGlobalListen.
	 * @return bool returns false
	 */
	public function getAutoGlobalListen()
	{
		return false;
	}

	/**
	 * Stops execution because function isn't dependent upon the page being a get or post.
	 * This prevents _postData from getting loaded.
	 * @param \Prado\Web\UI\THtmlWriter $writer the HTML writer
	 */
	public function run($writer)
	{
	}

	protected function processPostBackRequest($writer)
	{
	}

	protected function processCallbackRequest($writer)
	{
	}

	/**
	 * @return TForm the form on the page
	 */
	public function getForm()
	{
		return $this->_form;
	}

	/**
	 * Registers a TForm instance to the page.
	 * Note, a page can contain at most one TForm instance.
	 * @param TForm $form the form on the page
	 * @throws \Prado\Exceptions\TInvalidOperationException if this method is invoked twice or more.
	 */
	public function setForm(TForm $form)
	{
		$this->_form = $form;
	}

	/**
	 * Performs input validation.
	 * This method will invoke the registered validators to perform the actual validation.
	 * If validation group is specified, only the validators in that group will be invoked.
	 * @param string $validationGroup validation group. If null, all validators will perform validation.
	 */
	public function validate($validationGroup = null)
	{
	}

	/**
	 * Returns whether user input is valid or not.
	 * This method must be invoked after {@link validate} is called.
	 * @throws \Prado\Exceptions\TInvalidOperationException if {@link validate} is not invoked yet.
	 * @return bool whether the user input is valid or not.
	 */
	public function getIsValid()
	{
		return true;
	}

	/**
	 * Raises OnPreRenderComplete event.  Replaces parent to inject theme css rather than
	 * linking to it.  Does not call parent::onPreRenderComplete.
	 * @param mixed $param event parameter
	 * @see https://developers.google.com/gmail/design/css for more info on css in emails
	 */
	public function onPreRenderComplete($param)
	{
		$this->ensureEmailControls($this);
		$this->raiseEvent('OnPreRenderComplete', $this, $param);

		$theme = $this->getTheme();
		$style = '';
		if ($theme instanceof ITheme) {
			$baseUrl = $theme->getBaseUrl();
			$basePath = $theme->getBasePath();
			$len = strlen($baseUrl);

			foreach ($theme->getStyleSheetFiles() as $url) {
				$path = $basePath . str_replace('/', DIRECTORY_SEPARATOR, substr($url, $len));
				$style .= $data = file_get_contents($path);
			}
		}
		$styleSheet = $this->getStyleSheetTheme();
		if ($styleSheet instanceof ITheme) {
			$baseUrl = $styleSheet->getBaseUrl();
			$basePath = $styleSheet->getBasePath();
			$len = strlen($baseUrl);

			foreach ($styleSheet->getStyleSheetFiles() as $url) {
				$path = $basePath . str_replace('/', DIRECTORY_SEPARATOR, substr($url, $len));
				$style .= $data = file_get_contents($path);
			}
		}
		if ($style !== '' && ($head = $this->getHead()) !== null) {
			$head->getControls()->add("<style>\n" . $style . "\n</style>");
		}
	}

	/**
	 * @return bool false
	 */
	public function getIsPostBack()
	{
		return false;
	}

	/**
	 * @return bool false
	 */
	public function getIsCallback()
	{
		return false;
	}

	/**
	 * This method is invoked when control state is to be saved.
	 * You can override this method to do last step state saving.
	 * Parent implementation must be invoked.
	 */
	public function saveState()
	{
	}

	/**
	 * This method is invoked right after the control has loaded its state.
	 * You can override this method to initialize data from the control state.
	 * Parent implementation must be invoked.
	 */
	public function loadState()
	{
	}

	/**
	 * Loads page state from persistent storage.
	 */
	protected function loadPageState()
	{
	}

	/**
	 * Saves page state from persistent storage.
	 */
	protected function savePageState()
	{
	}

	/**
	 * @return ?\Prado\Web\UI\TControl null; an email has no postback.
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
	 * Should not be called, but vacated anyway just in case a subclass gets "creative."
	 * @param TMap $postData post data to be processed
	 * @param bool $beforeLoad whether this method is invoked before {@link onLoad OnLoad}.
	 */
	protected function processPostData($postData, $beforeLoad)
	{
	}

	/**
	 * @return bool Whether form rendering is in progress
	 */
	public function getInFormRender()
	{
		return $this->_inFormRender;
	}

	/**
	 * Ensures the control is rendered within a form.
	 * @param \Prado\Web\UI\TControl $control the control to be rendered
	 * @throws TConfigurationException if the control is outside of the form
	 */
	public function ensureRenderInForm($control)
	{
		if (!$this->_inFormRender) {
			//throw new TConfigurationException('page_control_outofform', get_class($control), $control ? $control->getUniqueID() : null);
		}
	}

	/**
	 * @internal This method is invoked by TForm at the beginning of its rendering
	 * @param mixed $writer
	 */
	public function beginFormRender($writer)
	{
		$this->_inFormRender = true;
	}

	/**
	 * @internal This method is invoked by TForm  at the end of its rendering
	 * @param mixed $writer
	 */
	public function endFormRender($writer)
	{
		$this->_inFormRender = false;
	}
}
