<?php
/**
 * TPage class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

use Prado\Collections\TList;
use Prado\Collections\TMap;
use Prado\Collections\TStack;
use Prado\Exceptions\TConfigurationException;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\Javascripts\TJavaScript;
use Prado\Web\UI\ActiveControls\TActivePageAdapter;
use Prado\Web\UI\ActiveControls\TCallbackClientScript;
use Prado\Web\UI\WebControls\THead;

/**
 * TPage class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.0
 */
class TPage extends TTemplateControl
{
	/**
	 * system post fields
	 */
	const FIELD_POSTBACK_TARGET = 'PRADO_POSTBACK_TARGET';
	const FIELD_POSTBACK_PARAMETER = 'PRADO_POSTBACK_PARAMETER';
	const FIELD_LASTFOCUS = 'PRADO_LASTFOCUS';
	const FIELD_PAGESTATE = 'PRADO_PAGESTATE';
	const FIELD_CALLBACK_TARGET = 'PRADO_CALLBACK_TARGET';
	const FIELD_CALLBACK_PARAMETER = 'PRADO_CALLBACK_PARAMETER';

	/**
	 * @var array system post fields
	 */
	private static $_systemPostFields = [
		'PRADO_POSTBACK_TARGET' => true,
		'PRADO_POSTBACK_PARAMETER' => true,
		'PRADO_LASTFOCUS' => true,
		'PRADO_PAGESTATE' => true,
		'PRADO_CALLBACK_TARGET' => true,
		'PRADO_CALLBACK_PARAMETER' => true
	];
	/**
	 * @var TForm form instance
	 */
	private $_form;
	/**
	 * @var THead head instance
	 */
	private $_head;
	/**
	 * @var array list of registered validators
	 */
	private $_validators = [];
	/**
	 * @var bool if validation has been performed
	 */
	private $_validated = false;
	/**
	 * @var TTheme page theme
	 */
	private $_theme;
	/**
	 * @var string page title set when Head is not in page yet
	 */
	private $_title;
	/**
	 * @var TTheme page stylesheet theme
	 */
	private $_styleSheet;
	/**
	 * @var TClientScriptManager client script manager
	 */
	private $_clientScript;
	/**
	 * @var TMap data post back by user
	 */
	protected $_postData;
	/**
	 * @var TMap postback data that is not handled during first invocation of LoadPostData.
	 */
	protected $_restPostData;
	/**
	 * @var array list of controls whose data have been changed due to the postback
	 */
	protected $_controlsPostDataChanged = [];
	/**
	 * @var array list of controls that need to load post data in the current request
	 */
	protected $_controlsRequiringPostData = [];
	/**
	 * @var array list of controls that need to load post data in the next postback
	 */
	protected $_controlsRegisteredForPostData = [];
	/**
	 * @var TControl control that needs to raise postback event
	 */
	private $_postBackEventTarget;
	/**
	 * @var string postback event parameter
	 */
	private $_postBackEventParameter;
	/**
	 * @var bool whether the form has been rendered
	 */
	protected $_formRendered = false;
	/**
	 * @var bool whether the current rendering is within a form
	 */
	protected $_inFormRender = false;
	/**
	 * @var string|TControl the control or the ID of the element on the page to be focused when the page is sent back to user
	 */
	private $_focus;
	/**
	 * @var string page path to this page
	 */
	private $_pagePath = '';
	/**
	 * @var bool whether page state should be HMAC validated
	 */
	private $_enableStateValidation = true;
	/**
	 * @var bool whether page state should be encrypted
	 */
	private $_enableStateEncryption = false;
	/**
	 * @var bool whether page state should be compressed
	 * @since 3.1.6
	 */
	private $_enableStateCompression = true;
	/**
	 * @var bool whether to use the igbinary serializer if available
	 * @since 4.1
	 */
	private $_enableStateIGBinary = true;
	/**
	 * @var string page state persister class name
	 */
	private $_statePersisterClass = '\Prado\Web\UI\TPageStatePersister';
	/**
	 * @var mixed page state persister
	 */
	private $_statePersister;
	/**
	 * @var TStack stack used to store currently active caching controls
	 */
	private $_cachingStack;
	/**
	 * @var string state string to be stored on the client side
	 */
	private $_clientState = '';
	/**
	 * @var bool true if loading post data.
	 */
	protected $_isLoadingPostData = false;
	/**
	 * @var bool whether client supports javascript
	 */
	private $_enableJavaScript = true;
	/**
	 * @var THtmlWriter current html render writer
	 */
	private $_writer;

	/**
	 * Constructor.
	 * Sets the page object to itself.
	 * Derived classes must call parent implementation.
	 */
	public function __construct()
	{
		$this->setPage($this);
	}

	/**
	 * Runs through the page lifecycles.
	 * @param THtmlTextWriter $writer the HTML writer
	 */
	public function run($writer)
	{
		Prado::trace("Running page life cycles", 'Prado\Web\UI\TPage');
		$this->_writer = $writer;

		$this->determinePostBackMode();

		if ($this->getIsPostBack()) {
			if ($this->getIsCallback()) {
				$this->processCallbackRequest($writer);
			} else {
				$this->processPostBackRequest($writer);
			}
		} else {
			$this->processNormalRequest($writer);
		}

		$this->_writer = null;
	}

	protected function processNormalRequest($writer)
	{
		Prado::trace("Page onPreInit()", 'Prado\Web\UI\TPage');
		$this->onPreInit(null);

		Prado::trace("Page initRecursive()", 'Prado\Web\UI\TPage');
		$this->initRecursive();

		Prado::trace("Page onInitComplete()", 'Prado\Web\UI\TPage');
		$this->onInitComplete(null);

		Prado::trace("Page onPreLoad()", 'Prado\Web\UI\TPage');
		$this->onPreLoad(null);
		Prado::trace("Page loadRecursive()", 'Prado\Web\UI\TPage');
		$this->loadRecursive();
		Prado::trace("Page onLoadComplete()", 'Prado\Web\UI\TPage');
		$this->onLoadComplete(null);

		Prado::trace("Page preRenderRecursive()", 'Prado\Web\UI\TPage');
		$this->preRenderRecursive();
		Prado::trace("Page onPreRenderComplete()", 'Prado\Web\UI\TPage');
		$this->onPreRenderComplete(null);

		Prado::trace("Page savePageState()", 'Prado\Web\UI\TPage');
		$this->savePageState();
		Prado::trace("Page onSaveStateComplete()", 'Prado\Web\UI\TPage');
		$this->onSaveStateComplete(null);

		Prado::trace("Page renderControl()", 'Prado\Web\UI\TPage');
		$this->renderControl($writer);
		Prado::trace("Page unloadRecursive()", 'Prado\Web\UI\TPage');
		$this->unloadRecursive();
	}

	protected function processPostBackRequest($writer)
	{
		Prado::trace("Page onPreInit()", 'Prado\Web\UI\TPage');
		$this->onPreInit(null);

		Prado::trace("Page initRecursive()", 'Prado\Web\UI\TPage');
		$this->initRecursive();

		Prado::trace("Page onInitComplete()", 'Prado\Web\UI\TPage');
		$this->onInitComplete(null);

		$this->_restPostData = new TMap;
		Prado::trace("Page loadPageState()", 'Prado\Web\UI\TPage');
		$this->loadPageState();
		Prado::trace("Page processPostData()", 'Prado\Web\UI\TPage');
		$this->processPostData($this->_postData, true);
		Prado::trace("Page onPreLoad()", 'Prado\Web\UI\TPage');
		$this->onPreLoad(null);
		Prado::trace("Page loadRecursive()", 'Prado\Web\UI\TPage');
		$this->loadRecursive();
		Prado::trace("Page processPostData()", 'Prado\Web\UI\TPage');
		$this->processPostData($this->_restPostData, false);
		Prado::trace("Page raiseChangedEvents()", 'Prado\Web\UI\TPage');
		$this->raiseChangedEvents();
		Prado::trace("Page raisePostBackEvent()", 'Prado\Web\UI\TPage');
		$this->raisePostBackEvent();
		Prado::trace("Page onLoadComplete()", 'Prado\Web\UI\TPage');
		$this->onLoadComplete(null);

		Prado::trace("Page preRenderRecursive()", 'Prado\Web\UI\TPage');
		$this->preRenderRecursive();
		Prado::trace("Page onPreRenderComplete()", 'Prado\Web\UI\TPage');
		$this->onPreRenderComplete(null);

		Prado::trace("Page savePageState()", 'Prado\Web\UI\TPage');
		$this->savePageState();
		Prado::trace("Page onSaveStateComplete()", 'Prado\Web\UI\TPage');
		$this->onSaveStateComplete(null);

		Prado::trace("Page renderControl()", 'Prado\Web\UI\TPage');
		$this->renderControl($writer);
		Prado::trace("Page unloadRecursive()", 'Prado\Web\UI\TPage');
		$this->unloadRecursive();
	}

	protected static function decodeUTF8($data, $enc)
	{
		if (is_array($data)) {
			foreach ($data as $k => $v) {
				$data[$k] = self::decodeUTF8($v, $enc);
			}
			return $data;
		} elseif (is_string($data)) {
			return iconv('UTF-8', $enc . '//IGNORE', $data);
		} else {
			return $data;
		}
	}

	/**
	 * Sets Adapter to TActivePageAdapter and calls apter to process the
	 * callback request.
	 * @param mixed $writer
	 */
	protected function processCallbackRequest($writer)
	{
		$this->setAdapter(new TActivePageAdapter($this));

		$callbackEventParameter = $this->getRequest()->itemAt(TPage::FIELD_CALLBACK_PARAMETER);
		if (strlen($callbackEventParameter) > 0) {
			$this->_postData[TPage::FIELD_CALLBACK_PARAMETER] = TJavaScript::jsonDecode((string) $callbackEventParameter);
		}

		// Decode Callback postData from UTF-8 to current Charset
		if (($g = $this->getApplication()->getGlobalization(false)) !== null &&
			strtoupper($enc = $g->getCharset()) != 'UTF-8') {
			foreach ($this->_postData as $k => $v) {
				$this->_postData[$k] = self::decodeUTF8($v, $enc);
			}
		}

		Prado::trace("Page onPreInit()", 'Prado\Web\UI\TPage');
		$this->onPreInit(null);

		Prado::trace("Page initRecursive()", 'Prado\Web\UI\TPage');
		$this->initRecursive();

		Prado::trace("Page onInitComplete()", 'Prado\Web\UI\TPage');
		$this->onInitComplete(null);

		$this->_restPostData = new TMap;
		Prado::trace("Page loadPageState()", 'Prado\Web\UI\TPage');
		$this->loadPageState();
		Prado::trace("Page processPostData()", 'Prado\Web\UI\TPage');
		$this->processPostData($this->_postData, true);
		Prado::trace("Page onPreLoad()", 'Prado\Web\UI\TPage');
		$this->onPreLoad(null);
		Prado::trace("Page loadRecursive()", 'Prado\Web\UI\TPage');
		$this->loadRecursive();

		Prado::trace("Page processPostData()", 'Prado\Web\UI\TPage');
		$this->processPostData($this->_restPostData, false);

		Prado::trace("Page raiseChangedEvents()", 'Prado\Web\UI\TPage');
		$this->raiseChangedEvents();


		$this->getAdapter()->processCallbackEvent($writer);

		/*
				Prado::trace("Page raisePostBackEvent()",'Prado\Web\UI\TPage');
				$this->raisePostBackEvent();
		*/
		Prado::trace("Page onLoadComplete()", 'Prado\Web\UI\TPage');
		$this->onLoadComplete(null);

		Prado::trace("Page preRenderRecursive()", 'Prado\Web\UI\TPage');
		$this->preRenderRecursive();
		Prado::trace("Page onPreRenderComplete()", 'Prado\Web\UI\TPage');
		$this->onPreRenderComplete(null);

		Prado::trace("Page savePageState()", 'Prado\Web\UI\TPage');
		$this->savePageState();
		Prado::trace("Page onSaveStateComplete()", 'Prado\Web\UI\TPage');
		$this->onSaveStateComplete(null);

		/*
				Prado::trace("Page renderControl()",'Prado\Web\UI\TPage');
				$this->renderControl($writer);
		*/
		$this->getAdapter()->renderCallbackResponse($writer);

		Prado::trace("Page unloadRecursive()", 'Prado\Web\UI\TPage');
		$this->unloadRecursive();
	}

	/**
	 * Gets the callback client script handler that allows javascript functions
	 * to be executed during the callback response.
	 * @return TCallbackClientScript interface to client-side javascript code.
	 */
	public function getCallbackClient()
	{
		if ($this->getAdapter() !== null) {
			return $this->getAdapter()->getCallbackClientHandler();
		} else {
			return new TCallbackClientScript();
		}
	}

	/**
	 * Set a new callback client handler.
	 * @param TCallbackClientScript $client new callback client script handler.
	 */
	public function setCallbackClient($client)
	{
		$this->getAdapter()->setCallbackClientHandler($client);
	}

	/**
	 * @return TControl the control responsible for the current callback event,
	 * null if nonexistent
	 */
	public function getCallbackEventTarget()
	{
		return $this->getAdapter()->getCallbackEventTarget();
	}

	/**
	 * Registers a control to raise callback event in the current request.
	 * @param TControl $control control registered to raise callback event.
	 */
	public function setCallbackEventTarget(TControl $control)
	{
		$this->getAdapter()->setCallbackEventTarget($control);
	}

	/**
	 * Callback parameter is decoded assuming JSON encoding.
	 * @return string callback event parameter
	 */
	public function getCallbackEventParameter()
	{
		return $this->getAdapter()->getCallbackEventParameter();
	}

	/**
	 * @param mixed $value callback event parameter
	 */
	public function setCallbackEventParameter($value)
	{
		$this->getAdapter()->setCallbackEventParameter($value);
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
	 * @throws TInvalidOperationException if this method is invoked twice or more.
	 */
	public function setForm(TForm $form)
	{
		if ($this->_form === null) {
			$this->_form = $form;
		} else {
			throw new TInvalidOperationException('page_form_duplicated');
		}
	}

	/**
	 * Returns a list of registered validators.
	 * If validation group is specified, only the validators in that group will be returned.
	 * @param null|string $validationGroup validation group
	 * @return TList registered validators in the requested group. If the group is null, all validators will be returned.
	 */
	public function getValidators($validationGroup = null)
	{
		if (!$this->_validators) {
			$this->_validators = new TList;
		}
		if (empty($validationGroup) === true) {
			return $this->_validators;
		} else {
			$list = new TList;
			foreach ($this->_validators as $validator) {
				if ($validator->getValidationGroup() === $validationGroup) {
					$list->add($validator);
				}
			}
			return $list;
		}
	}

	/**
	 * Performs input validation.
	 * This method will invoke the registered validators to perform the actual validation.
	 * If validation group is specified, only the validators in that group will be invoked.
	 * @param string $validationGroup validation group. If null, all validators will perform validation.
	 */
	public function validate($validationGroup = null)
	{
		Prado::trace("Page validate()", 'Prado\Web\UI\TPage');
		$this->_validated = true;
		if ($this->_validators && $this->_validators->getCount()) {
			if ($validationGroup === null) {
				foreach ($this->_validators as $validator) {
					$validator->validate();
				}
			} else {
				foreach ($this->_validators as $validator) {
					if ($validator->getValidationGroup() === $validationGroup) {
						$validator->validate();
					}
				}
			}
		}
	}

	/**
	 * Returns whether user input is valid or not.
	 * This method must be invoked after {@link validate} is called.
	 * @throws TInvalidOperationException if {@link validate} is not invoked yet.
	 * @return bool whether the user input is valid or not.
	 */
	public function getIsValid()
	{
		if ($this->_validated) {
			if ($this->_validators && $this->_validators->getCount()) {
				foreach ($this->_validators as $validator) {
					if (!$validator->getIsValid()) {
						return false;
					}
				}
			}
			return true;
		} else {
			throw new TInvalidOperationException('page_isvalid_unknown');
		}
	}

	/**
	 * @return TTheme the theme used for the page. Defaults to null.
	 */
	public function getTheme()
	{
		if (is_string($this->_theme)) {
			$this->_theme = $this->getService()->getThemeManager()->getTheme($this->_theme);
		}
		return $this->_theme;
	}

	/**
	 * Sets the theme to be used for the page.
	 * @param string|TTheme $value the theme name or the theme object to be used for the page.
	 */
	public function setTheme($value)
	{
		$this->_theme = empty($value) ? null : $value;
	}


	/**
	 * @return TTheme the stylesheet theme used for the page. Defaults to null.
	 */
	public function getStyleSheetTheme()
	{
		if (is_string($this->_styleSheet)) {
			$this->_styleSheet = $this->getService()->getThemeManager()->getTheme($this->_styleSheet);
		}
		return $this->_styleSheet;
	}

	/**
	 * Sets the stylesheet theme to be used for the page.
	 * @param string|TTheme $value the stylesheet theme name or the stylesheet theme object to be used for the page.
	 */
	public function setStyleSheetTheme($value)
	{
		$this->_styleSheet = empty($value) ? null : $value;
	}

	/**
	 * Applies a skin in the current theme to a control.
	 * This method should only be used by framework developers.
	 * @param TControl $control a control to be applied skin with
	 */
	public function applyControlSkin($control)
	{
		if (($theme = $this->getTheme()) !== null) {
			$theme->applySkin($control);
		}
	}

	/**
	 * Applies a stylesheet skin in the current theme to a control.
	 * This method should only be used by framework developers.
	 * @param TControl $control a control to be applied stylesheet skin with
	 */
	public function applyControlStyleSheet($control)
	{
		if (($theme = $this->getStyleSheetTheme()) !== null) {
			$theme->applySkin($control);
		}
	}

	/**
	 * @return TClientScriptManager client script manager
	 */
	public function getClientScript()
	{
		if (!$this->_clientScript) {
			$className = $classPath = $this->getService()->getClientScriptManagerClass();

			if ($className !== '\Prado\Web\UI\TClientScriptManager' && !is_subclass_of($className, '\Prado\Web\UI\TClientScriptManager')) {
				throw new THttpException(404, 'page_csmanagerclass_invalid', $className);
			}

			$this->_clientScript = new $className($this);
		}
		return $this->_clientScript;
	}

	/**
	 * Raises OnPreInit event.
	 * This method is invoked right before {@link onInit OnInit} stage.
	 * You may override this method to provide additional initialization that
	 * should be done before {@link onInit OnInit} (e.g. setting {@link setTheme Theme} or
	 * {@link setStyleSheetTheme StyleSheetTheme}).
	 * Remember to call the parent implementation to ensure OnPreInit event is raised.
	 * @param mixed $param event parameter
	 */
	public function onPreInit($param)
	{
		$this->raiseEvent('OnPreInit', $this, $param);
	}

	/**
	 * Raises OnInitComplete event.
	 * This method is invoked right after {@link onInit OnInit} stage and before {@link onLoad OnLoad} stage.
	 * You may override this method to provide additional initialization that
	 * should be done after {@link onInit OnInit}.
	 * Remember to call the parent implementation to ensure OnInitComplete event is raised.
	 * @param mixed $param event parameter
	 */
	public function onInitComplete($param)
	{
		$this->raiseEvent('OnInitComplete', $this, $param);
	}

	/**
	 * Raises OnPreLoad event.
	 * This method is invoked right before {@link onLoad OnLoad} stage.
	 * You may override this method to provide additional page loading logic that
	 * should be done before {@link onLoad OnLoad}.
	 * Remember to call the parent implementation to ensure OnPreLoad event is raised.
	 * @param mixed $param event parameter
	 */
	public function onPreLoad($param)
	{
		$this->raiseEvent('OnPreLoad', $this, $param);
	}

	/**
	 * Raises OnLoadComplete event.
	 * This method is invoked right after {@link onLoad OnLoad} stage.
	 * You may override this method to provide additional page loading logic that
	 * should be done after {@link onLoad OnLoad}.
	 * Remember to call the parent implementation to ensure OnLoadComplete event is raised.
	 * @param mixed $param event parameter
	 */
	public function onLoadComplete($param)
	{
		$this->raiseEvent('OnLoadComplete', $this, $param);
	}

	/**
	 * Raises OnPreRenderComplete event.
	 * This method is invoked right after {@link onPreRender OnPreRender} stage.
	 * You may override this method to provide additional preparation for page rendering
	 * that should be done after {@link onPreRender OnPreRender}.
	 * Remember to call the parent implementation to ensure OnPreRenderComplete event is raised.
	 * @param mixed $param event parameter
	 */
	public function onPreRenderComplete($param)
	{
		$this->raiseEvent('OnPreRenderComplete', $this, $param);
		$cs = $this->getClientScript();
		$theme = $this->getTheme();
		if ($theme instanceof ITheme) {
			foreach ($theme->getStyleSheetFiles() as $url) {
				$cs->registerStyleSheetFile($url, $url, $this->getCssMediaType($url));
			}
			foreach ($theme->getJavaScriptFiles() as $url) {
				$cs->registerHeadScriptFile($url, $url);
			}
		}
		$styleSheet = $this->getStyleSheetTheme();
		if ($styleSheet instanceof ITheme) {
			foreach ($styleSheet->getStyleSheetFiles() as $url) {
				$cs->registerStyleSheetFile($url, $url, $this->getCssMediaType($url));
			}
			foreach ($styleSheet->getJavaScriptFiles() as $url) {
				$cs->registerHeadScriptFile($url, $url);
			}
		}

		if ($cs->getRequiresHead() && $this->getHead() === null) {
			throw new TConfigurationException('page_head_required');
		}
	}

	/**
	 * Determines the media type of the CSS file.
	 * The media type is determined according to the following file name pattern:
	 *        xxx.media-type.extension
	 * For example, 'mystyle.print.css' means its media type is 'print'.
	 * @param string $url CSS URL
	 * @return string media type of the CSS file
	 */
	private function getCssMediaType($url)
	{
		$segs = explode('.', basename($url));
		if (isset($segs[2])) {
			return $segs[count($segs) - 2];
		} else {
			return '';
		}
	}

	/**
	 * Raises OnSaveStateComplete event.
	 * This method is invoked right after {@link onSaveState OnSaveState} stage.
	 * You may override this method to provide additional logic after page state is saved.
	 * Remember to call the parent implementation to ensure OnSaveStateComplete event is raised.
	 * @param mixed $param event parameter
	 */
	public function onSaveStateComplete($param)
	{
		$this->raiseEvent('OnSaveStateComplete', $this, $param);
	}

	/**
	 * Determines whether the current page request is a postback.
	 * Call {@link getIsPostBack} to get the result.
	 */
	private function determinePostBackMode()
	{
		$postData = $this->getRequest();
		if ($postData->contains(self::FIELD_PAGESTATE) || $postData->contains(self::FIELD_POSTBACK_TARGET)) {
			$this->_postData = $postData;
		}
	}

	/**
	 * @return bool whether the current page request is a postback
	 */
	public function getIsPostBack()
	{
		return $this->_postData !== null;
	}

	/**
	 * @return bool whether this is a callback request
	 */
	public function getIsCallback()
	{
		return $this->getIsPostBack() && $this->getRequest()->contains(self::FIELD_CALLBACK_TARGET);
	}

	/**
	 * This method is invoked when control state is to be saved.
	 * You can override this method to do last step state saving.
	 * Parent implementation must be invoked.
	 */
	public function saveState()
	{
		parent::saveState();
		$this->setViewState('ControlsRequiringPostBack', $this->_controlsRegisteredForPostData, []);
	}

	/**
	 * This method is invoked right after the control has loaded its state.
	 * You can override this method to initialize data from the control state.
	 * Parent implementation must be invoked.
	 */
	public function loadState()
	{
		parent::loadState();
		$this->_controlsRequiringPostData = $this->getViewState('ControlsRequiringPostBack', []);
	}

	/**
	 * Loads page state from persistent storage.
	 */
	protected function loadPageState()
	{
		Prado::trace("Loading state", 'Prado\Web\UI\TPage');
		$state = $this->getStatePersister()->load();
		$this->loadStateRecursive($state, $this->getEnableViewState());
	}

	/**
	 * Saves page state from persistent storage.
	 */
	protected function savePageState()
	{
		Prado::trace("Saving state", 'Prado\Web\UI\TPage');
		$state = &$this->saveStateRecursive($this->getEnableViewState());
		$this->getStatePersister()->save($state);
	}

	/**
	 * @param string $field the field name
	 * @return bool whether the specified field is a system field in postback data
	 */
	protected function isSystemPostField($field)
	{
		return isset(self::$_systemPostFields[$field]);
	}

	/**
	 * Registers a control for loading post data in the next postback.
	 * This method needs to be invoked if the control to load post data
	 * may not have a post variable in some cases. For example, a checkbox,
	 * if not checked, will not have a post value.
	 * @param TControl $control control registered for loading post data
	 */
	public function registerRequiresPostData($control)
	{
		$id = is_string($control) ? $control : $control->getUniqueID();
		$this->_controlsRegisteredForPostData[$id] = true;
		$params = func_get_args();
		foreach ($this->getCachingStack() as $item) {
			$item->registerAction('Page', 'registerRequiresPostData', [$id]);
		}
	}

	/**
	 * @return TControl the control responsible for the current postback event, null if nonexistent
	 */
	public function getPostBackEventTarget()
	{
		if ($this->_postBackEventTarget === null && $this->_postData !== null) {
			$eventTarget = $this->_postData->itemAt(self::FIELD_POSTBACK_TARGET);
			if (!empty($eventTarget)) {
				$this->_postBackEventTarget = $this->findControl($eventTarget);
			}
		}
		return $this->_postBackEventTarget;
	}

	/**
	 * Registers a control to raise postback event in the current request.
	 * @param TControl $control control registered to raise postback event.
	 */
	public function setPostBackEventTarget(TControl $control)
	{
		$this->_postBackEventTarget = $control;
	}

	/**
	 * @return string postback event parameter
	 */
	public function getPostBackEventParameter()
	{
		if ($this->_postBackEventParameter === null && $this->_postData !== null) {
			if (($this->_postBackEventParameter = $this->_postData->itemAt(self::FIELD_POSTBACK_PARAMETER)) === null) {
				$this->_postBackEventParameter = '';
			}
		}
		return $this->_postBackEventParameter;
	}

	/**
	 * @param string $value postback event parameter
	 */
	public function setPostBackEventParameter($value)
	{
		$this->_postBackEventParameter = $value;
	}

	/**
	 * Processes post data.
	 * @param TMap $postData post data to be processed
	 * @param bool $beforeLoad whether this method is invoked before {@link onLoad OnLoad}.
	 */
	protected function processPostData($postData, $beforeLoad)
	{
		$this->_isLoadingPostData = true;
		if ($beforeLoad) {
			$this->_restPostData = new TMap;
		}
		foreach ($postData as $key => $value) {
			if ($this->isSystemPostField($key)) {
				continue;
			} elseif ($control = $this->findControl($key)) {
				if ($control instanceof \Prado\Web\UI\IPostBackDataHandler) {
					if ($control->loadPostData($key, $postData)) {
						$this->_controlsPostDataChanged[] = $control;
					}
				} elseif ($control instanceof IPostBackEventHandler &&
					empty($this->_postData[self::FIELD_POSTBACK_TARGET])) {
					// not calling setPostBackEventTarget() because the control may be removed later
					$this->_postData->add(self::FIELD_POSTBACK_TARGET, $key);
				}
				unset($this->_controlsRequiringPostData[$key]);
			} elseif ($beforeLoad) {
				$this->_restPostData->add($key, $value);
			}
		}

		foreach ($this->_controlsRequiringPostData as $key => $value) {
			if ($control = $this->findControl($key)) {
				if ($control instanceof \Prado\Web\UI\IPostBackDataHandler) {
					if ($control->loadPostData($key, $this->_postData)) {
						$this->_controlsPostDataChanged[] = $control;
					}
				} else {
					throw new TInvalidDataValueException('page_postbackcontrol_invalid', $key);
				}
				unset($this->_controlsRequiringPostData[$key]);
			}
		}
		$this->_isLoadingPostData = false;
	}

	/**
	 * @return bool true if loading post data.
	 */
	public function getIsLoadingPostData()
	{
		return $this->_isLoadingPostData;
	}

	/**
	 * Raises OnPostDataChangedEvent for controls whose data have been changed due to the postback.
	 */
	protected function raiseChangedEvents()
	{
		foreach ($this->_controlsPostDataChanged as $control) {
			$control->raisePostDataChangedEvent();
		}
	}

	/**
	 * Raises PostBack event.
	 */
	protected function raisePostBackEvent()
	{
		if (($postBackHandler = $this->getPostBackEventTarget()) === null) {
			$this->validate();
		} elseif ($postBackHandler instanceof IPostBackEventHandler) {
			$postBackHandler->raisePostBackEvent($this->getPostBackEventParameter());
		}
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
	 * @param TControl $control the control to be rendered
	 * @throws TConfigurationException if the control is outside of the form
	 */
	public function ensureRenderInForm($control)
	{
		if (!$this->getIsCallback() && !$this->_inFormRender) {
			throw new TConfigurationException('page_control_outofform', get_class($control), $control ? $control->getUniqueID() : null);
		}
	}

	/**
	 * @internal This method is invoked by TForm at the beginning of its rendering
	 * @param mixed $writer
	 */
	public function beginFormRender($writer)
	{
		if ($this->_formRendered) {
			throw new TConfigurationException('page_form_duplicated');
		}
		$this->_formRendered = true;
		$this->getClientScript()->registerHiddenField(self::FIELD_PAGESTATE, $this->getClientState());
		$this->_inFormRender = true;
	}

	/**
	 * @internal This method is invoked by TForm  at the end of its rendering
	 * @param mixed $writer
	 */
	public function endFormRender($writer)
	{
		if ($this->_focus) {
			if (($this->_focus instanceof TControl) && $this->_focus->getVisible(true)) {
				$focus = $this->_focus->getClientID();
			} else {
				$focus = $this->_focus;
			}
			$this->getClientScript()->registerFocusControl($focus);
		} elseif ($this->_postData && ($lastFocus = $this->_postData->itemAt(self::FIELD_LASTFOCUS)) !== null) {
			$this->getClientScript()->registerFocusControl($lastFocus);
		}
		$this->_inFormRender = false;
	}

	/**
	 * Sets input focus on a control after the page is rendered to users.
	 * @param string|TControl $value control to receive focus, or the ID of the element on the page to receive focus
	 */
	public function setFocus($value)
	{
		$this->_focus = $value;
	}

	/**
	 * @return bool whether client supports javascript. Defaults to true.
	 */
	public function getClientSupportsJavaScript()
	{
		return $this->_enableJavaScript;
	}

	/**
	 * @param bool $value whether client supports javascript. If false, javascript will not be generated for controls.
	 */
	public function setClientSupportsJavaScript($value)
	{
		$this->_enableJavaScript = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return THead page head, null if not available
	 */
	public function getHead()
	{
		return $this->_head;
	}

	/**
	 * @param THead $value page head
	 * @throws TInvalidOperationException if a head already exists
	 */
	public function setHead(THead $value)
	{
		if ($this->_head) {
			throw new TInvalidOperationException('page_head_duplicated');
		}
		$this->_head = $value;
		if ($this->_title !== null) {
			$this->_head->setTitle($this->_title);
			$this->_title = null;
		}
	}

	/**
	 * @return string page title.
	 */
	public function getTitle()
	{
		if ($this->_head) {
			return $this->_head->getTitle();
		} else {
			return $this->_title === null ? '' : $this->_title;
		}
	}

	/**
	 * Sets the page title.
	 * Note, a {@link THead} control needs to place on the page
	 * in order that this title be rendered.
	 * @param string $value page title. This will override the title set in {@link getHead Head}.
	 */
	public function setTitle($value)
	{
		if ($this->_head) {
			$this->_head->setTitle($value);
		} else {
			$this->_title = $value;
		}
	}

	/**
	 * Returns the state to be stored on the client side.
	 * This method should only be used by framework and control developers.
	 * @return string the state to be stored on the client side
	 */
	public function getClientState()
	{
		return $this->_clientState;
	}

	/**
	 * Sets the state to be stored on the client side.
	 * This method should only be used by framework and control developers.
	 * @param string $state the state to be stored on the client side
	 */
	public function setClientState($state)
	{
		$this->_clientState = $state;
	}

	/**
	 * @return string the state postback from client side
	 */
	public function getRequestClientState()
	{
		return $this->getRequest()->itemAt(self::FIELD_PAGESTATE);
	}

	/**
	 * @return string class name of the page state persister. Defaults to TPageStatePersister.
	 */
	public function getStatePersisterClass()
	{
		return $this->_statePersisterClass;
	}

	/**
	 * @param string $value class name of the page state persister.
	 */
	public function setStatePersisterClass($value)
	{
		$this->_statePersisterClass = $value;
	}

	/**
	 * @return IPageStatePersister page state persister
	 */
	public function getStatePersister()
	{
		if ($this->_statePersister === null) {
			$this->_statePersister = Prado::createComponent($this->_statePersisterClass);
			if (!($this->_statePersister instanceof IPageStatePersister)) {
				throw new TInvalidDataTypeException('page_statepersister_invalid');
			}
			$this->_statePersister->setPage($this);
		}
		return $this->_statePersister;
	}

	/**
	 * @return bool whether page state should be HMAC validated. Defaults to true.
	 */
	public function getEnableStateValidation()
	{
		return $this->_enableStateValidation;
	}

	/**
	 * @param bool $value whether page state should be HMAC validated.
	 */
	public function setEnableStateValidation($value)
	{
		$this->_enableStateValidation = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether page state should be encrypted. Defaults to false.
	 */
	public function getEnableStateEncryption()
	{
		return $this->_enableStateEncryption;
	}

	/**
	 * @param bool $value whether page state should be encrypted.
	 */
	public function setEnableStateEncryption($value)
	{
		$this->_enableStateEncryption = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether page state should be compressed. Defaults to true.
	 * @since 3.1.6
	 */
	public function getEnableStateCompression()
	{
		return $this->_enableStateCompression;
	}

	/**
	 * @param bool $value whether page state should be compressed.
	 * @since 3.1.6
	 */
	public function setEnableStateCompression($value)
	{
		$this->_enableStateCompression = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return bool whether page state should be serialized using igbinary if available. Defaults to true.
	 * @since 4.1
	 */
	public function getEnableStateIGBinary()
	{
		return $this->_enableStateIGBinary;
	}

	/**
	 * @param bool $value whether page state should be serialized using igbinary if available.
	 * @since 4.1
	 */
	public function setEnableStateIGBinary($value)
	{
		$this->_enableStateIGBinary = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * @return string the requested page path for this page
	 */
	public function getPagePath()
	{
		return $this->_pagePath;
	}

	/**
	 * @param string $value the requested page path for this page
	 */
	public function setPagePath($value)
	{
		$this->_pagePath = $value;
	}

	/**
	 * Registers an action associated with the content being cached.
	 * The registered action will be replayed if the content stored
	 * in the cache is served to end-users.
	 * @param string $context context of the action method. This is a property-path
	 * referring to the context object (e.g. Page, Page.ClientScript).
	 * @param string $funcName method name of the context object
	 * @param array $funcParams list of parameters to be passed to the action method
	 */
	public function registerCachingAction($context, $funcName, $funcParams)
	{
		if ($this->_cachingStack) {
			foreach ($this->_cachingStack as $cache) {
				$cache->registerAction($context, $funcName, $funcParams);
			}
		}
	}

	/**
	 * @return TStack stack of {@link TOutputCache} objects
	 */
	public function getCachingStack()
	{
		if (!$this->_cachingStack) {
			$this->_cachingStack = new TStack;
		}
		return $this->_cachingStack;
	}

	/**
	 * Flushes output
	 */
	public function flushWriter()
	{
		if ($this->_writer) {
			$this->Response->write($this->_writer->flush());
		}
	}
}
