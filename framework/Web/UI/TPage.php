<?php

Prado::using('System.Web.UI.*');
Prado::using('System.Web.UI.WebControls.*');

class TPage extends TTemplateControl
{
	const FIELD_POSTBACK_TARGET='PRADO_POSTBACK_TARGET';
	const FIELD_POSTBACK_PARAMETER='PRADO_POSTBACK_PARAMETER';
	const FIELD_LASTFOCUS='PRADO_LASTFOCUS';
	const FIELD_PAGESTATE='PRADO_PAGESTATE';
	const FIELD_SCROLLX='PRADO_SCROLLX';
	const FIELD_SCROLLY='PRADO_SCROLLY';
	/**
	 * @var array system post fields
	 */
	private static $_systemPostFields=array(
		self::FIELD_POSTBACK_TARGET=>true,
		self::FIELD_POSTBACK_PARAMETER=>true,
		self::FIELD_LASTFOCUS=>true,
		self::FIELD_PAGESTATE=>true,
		self::FIELD_SCROLLX=>true,
		self::FIELD_SCROLLY=>true,
		'__PREVPAGE','__CALLBACKID','__CALLBACKPARAM'
	);
	/**
	 * @var TApplication application instance
	 */
	private $_application;
	/**
	 * @var TPageService page service instance
	 */
	private $_pageService;
	/**
	 * @var TForm form instance
	 */
	private $_form=null;
	/**
	 * @var string template file name
	 */
	private $_templateFile=null;
	/**
	 * @var array list of registered validators
	 */
	private $_validators=array();
	/**
	 * @var boolean if validation has been performed
	 */
	private $_validated=false;
	/**
	 * @var TTheme page theme
	 */
	private $_theme=null;
	/**
	 * @var TTheme page stylesheet theme
	 */
	private $_styleSheet=null;
	/**
	 * @var TClientScriptManager client script manager
	 */
	private $_clientScript=null;
	/**
	 * @var TMap data post back by user
	 */
	private $_postData;
	/**
	 * @var TMap postback data that is not handled during first invocation of LoadPostData.
	 */
	private $_restPostData;
	/**
	 * @var array list of controls whose data have been changed due to the postback
	 */
	private $_controlsPostDataChanged=array();
	/**
	 * @var array list of controls that need to load post data in the current request
	 */
	private $_controlsRequiringPostBack=array();
	/**
	 * @var array list of controls that need to load post data in the next postback
	 */
	private $_controlsRegisteredForPostBack=array();
	/**
	 * @var TControl control that needs to raise postback event
	 */
	private $_postBackEventTarget=null;
	/**
	 * @var mixed postback event parameter
	 */
	private $_postBackEventParameter=null;
	/**
	 * @var boolean whether form has rendered
	 */
	private $_formRendered=false;
	/**
	 * @var boolean whether the current rendering is within a form
	 */
	private $_inFormRender=false;
	/**
	 * @var TControl the control to be focused when the page is sent back to user
	 */
	private $_focusedControl=null;
	/**
	 * @var boolean whether or not to maintain page scroll position
	 */
	private $_maintainScrollPosition=false;

	private $_maxPageStateFieldLength=10;
	private $_enableViewStateMac=true;
	private $_isCrossPagePostBack=false;
	private $_previousPagePath='';

	/**
	 * Constructor.
	 * If initial property values are given, they will be set to the page.
	 * @param array initial property values for the page.
	 */
	public function __construct($initProperties=null)
	{
		$this->_application=Prado::getApplication();
		$this->_pageService=$this->_application->getService();
		$this->setPage($this);
		if(is_array($initProperties))
		{
			foreach($initProperties as $name=>$value)
				$this->setSubProperty($name,$value);
		}
		parent::__construct();
	}

	/**
	 * Runs through the page lifecycles.
	 * This method runs through the page lifecycles.
	 * @param THtmlTextWriter the HTML writer
	 */
	public function run($writer)
	{
		$this->determinePostBackMode();

		$this->onPreInit(null);
		$this->initRecursive();
		$this->onInitComplete(null);

		if($this->getIsPostBack())
		{
			$this->_restPostData=new TMap;
			$this->loadPageState();
			$this->processPostData($this->_postData,true);
			$this->onPreLoad(null);
			$this->loadRecursive();
			$this->processPostData($this->_restPostData,false);
			$this->raiseChangedEvents();
			$this->raisePostBackEvent();
			$this->onLoadComplete(null);
		}
		else
		{
			$this->onPreLoad(null);
			$this->loadRecursive();
			$this->onLoadComplete(null);
		}

		$this->preRenderRecursive();
		$this->onPreRenderComplete(null);

		$this->savePageState();
		$this->onSaveStateComplete(null);

		$this->renderControl($writer);
		$this->unloadRecursive();
	}

	/**
	 * Loads and parses the page template.
	 * This method overrides the parent implementation by allowing loading
	 * a page template from a specified template file.
	 * @return ITemplate the parsed template structure
	 */
	protected function loadTemplate()
	{
		if($this->_templateFile===null)
			return parent::loadTemplate();
		else
		{
			$template=$this->_pageService->getTemplateManager()->getTemplateByFileName($this->_templateFile);
			$this->setTemplate($template);
			return $template;
		}
	}

	/**
	 * @return string the user-specified template file, defaults to null.
	 */
	public function getTemplateFile()
	{
		return $this->_templateFile;
	}

	/**
	 * Sets the user-specified template file.
	 * The template file must be specified in a namespace format.
	 * @param string the user-specified template file.
	 * @throws TInvalidDataValueException if the file is not in namespace format.
	 */
	public function setTemplateFile($value)
	{
		if(($templateFile=Prado::getPathOfNamespace($value,TTemplateManager::TEMPLATE_FILE_EXT))===null)
			throw new TInvalidDataValueException('page_templatefile_invalid',$value);
		else
			$this->_templateFile=$templateFile;
	}

	/**
	 * Registers a TForm instance to the page.
	 * Note, a page can contain at most one TForm instance.
	 * @param TForm the form on the page
	 * @throws TInvalidOperationException if this method is invoked twice or more.
	 */
	public function setForm(TForm $form)
	{
		if($this->_form===null)
			$this->_form=$form;
		else
			throw new TInvalidOperationException('page_form_duplicated');
	}

	/**
	 * @return TForm the form on the page
	 */
	public function getForm()
	{
		return $this->_form;
	}

	/**
	 * Returns a list of registered validators.
	 * If validation group is specified, only the validators in that group will be returned.
	 * @param string validation group
	 * @return TList registered validators
	 */
	public function getValidators($validationGroup='')
	{
		if(!$this->_validators)
			$this->_validators=new TList;
		if($validationGroup==='')
			return $this->_validators;
		else
		{
			$list=new TList;
			foreach($this->_validators as $validator)
				if($validator->getValidationGroup()===$validationGroup)
					$list->add($validator);
			return $list;
		}
	}

	/**
	 * Performs input validation.
	 * This method will invoke the registered validators to perform the actual validation.
	 * If validation group is specified, only the validators in that group will be invoked.
	 * @param string validation group
	 */
	public function validate($validationGroup='')
	{
		$this->_validated=true;
		if($this->_validators && $this->_validators->getCount())
		{
			if($validationGroup==='')
			{
				foreach($this->_validators as $validator)
					$validator->validate();
			}
			else
			{
				foreach($this->_validators as $validator)
					if($validator->getValidationGroup()===$validationGroup)
						$validator->validate();
			}
		}
	}

	/**
	 * Returns whether user input is valid or not.
	 * This method must be invoked after {@link validate} is called.
	 * @return boolean whether the user input is valid or not.
	 * @throws TInvalidOperationException if {@link validate} is not invoked yet.
	 */
	public function getIsValid()
	{
		if($this->_validated)
		{
			if($this->_validators && $this->_validators->getCount())
			{
				foreach($this->_validators as $validator)
					if(!$validator->getIsValid())
						return false;
			}
			return true;
		}
		else
			throw new TInvalidOperationException('page_isvalid_unknown');
	}

	/**
	 * @return TTheme the theme used for the page. Defaults to null.
	 */
	public function getTheme()
	{
		if(is_string($this->_theme))
			$this->_theme=$this->_pageService->getThemeManager()->getTheme($this->_theme);
		return $this->_theme;
	}

	/**
	 * Sets the theme to be used for the page.
	 * @param string|TTheme the theme name or the theme object to be used for the page.
	 * @throws TInvalidDataTypeException if the parameter is neither a string nor a TTheme object
	 */
	public function setTheme($value)
	{
		$this->_theme=$value;
	}


	/**
	 * @return TTheme the stylesheet theme used for the page. Defaults to null.
	 */
	public function getStyleSheetTheme()
	{
		if(is_string($this->_styleSheet))
			$this->_styleSheet=$this->_pageService->getThemeManager()->getTheme($this->_styleSheet);
		return $this->_styleSheet;
	}

	/**
	 * Sets the stylesheet theme to be used for the page.
	 * @param string|TTheme the stylesheet theme name or the stylesheet theme object to be used for the page.
	 * @throws TInvalidDataTypeException if the parameter is neither a string nor a TTheme object
	 */
	public function setStyleSheetTheme($value)
	{
		$this->_styleSheet=$value;
	}

	/**
	 * Applies a skin in the current theme to a control.
	 * This method should only be used by framework developers.
	 * @param TControl a control to be applied skin with
	 */
	public function applyControlSkin($control)
	{
		if(($theme=$this->getTheme())!==null)
			$theme->applySkin($control);
	}

	/**
	 * Applies a stylesheet skin in the current theme to a control.
	 * This method should only be used by framework developers.
	 * @param TControl a control to be applied stylesheet skin with
	 */
	public function applyControlStyleSheet($control)
	{
		if(($theme=$this->getStyleSheetTheme())!==null)
			$theme->applySkin($control);
	}

	/**
	 * @return TClientScriptManager client script manager
	 */
	public function getClientScript()
	{
		if(!$this->_clientScript)
			$this->_clientScript=new TClientScriptManager($this);
		return $this->_clientScript;
	}

	/**
	 * Raises PreInit event.
	 * This method is invoked right before {@link onInit Init} stage.
	 * You may override this method to provide additional initialization that
	 * should be done before {@link onInit Init} (e.g. setting {@link setTheme Theme} or
	 * {@link setStyleSheetTheme StyleSheetTheme}).
	 * Remember to call the parent implementation to ensure PreInit event is raised.
	 * @param mixed event parameter
	 */
	protected function onPreInit($param)
	{
		$this->raiseEvent('PreInit',$this,$param);
	}

	/**
	 * Raises InitComplete event.
	 * This method is invoked right after {@link onInit Init} stage and before {@link onLoad Load} stage.
	 * You may override this method to provide additional initialization that
	 * should be done after {@link onInit Init}.
	 * Remember to call the parent implementation to ensure InitComplete event is raised.
	 * @param mixed event parameter
	 */
	protected function onInitComplete($param)
	{
		$this->raiseEvent('InitComplete',$this,$param);
	}

	/**
	 * Raises PreLoad event.
	 * This method is invoked right before {@link onLoad Load} stage.
	 * You may override this method to provide additional page loading logic that
	 * should be done before {@link onLoad Load}.
	 * Remember to call the parent implementation to ensure PreLoad event is raised.
	 * @param mixed event parameter
	 */
	protected function onPreLoad($param)
	{
		$this->raiseEvent('PreLoad',$this,$param);
	}

	/**
	 * Raises LoadComplete event.
	 * This method is invoked right after {@link onLoad Load} stage.
	 * You may override this method to provide additional page loading logic that
	 * should be done after {@link onLoad Load}.
	 * Remember to call the parent implementation to ensure LoadComplete event is raised.
	 * @param mixed event parameter
	 */
	protected function onLoadComplete($param)
	{
		$this->raiseEvent('LoadComplete',$this,$param);
	}

	/**
	 * Raises PreRenderComplete event.
	 * This method is invoked right after {@link onPreRender PreRender} stage.
	 * You may override this method to provide additional preparation for page rendering
	 * that should be done after {@link onPreRender PreRender}.
	 * Remember to call the parent implementation to ensure PreRenderComplete event is raised.
	 * @param mixed event parameter
	 */
	protected function onPreRenderComplete($param)
	{
		$this->raiseEvent('PreRenderComplete',$this,$param);
	}

	/**
	 * Raises SaveStateComplete event.
	 * This method is invoked right after {@link onSaveState SaveState} stage.
	 * You may override this method to provide additional logic after page state is saved.
	 * Remember to call the parent implementation to ensure SaveStateComplete event is raised.
	 * @param mixed event parameter
	 */
	protected function onSaveStateComplete($param)
	{
		$this->raiseEvent('SaveStateComplete',$this,$param);
	}

	/**
	 * Determines whether the current page request is a postback.
	 * Call {@link getIsPostBack} to get the result.
	 */
	private function determinePostBackMode()
	{
		$postData=$this->_application->getRequest()->getItems();
		if($postData->contains(self::FIELD_PAGESTATE) || $postData->contains(self::FIELD_POSTBACK_TARGET))
			$this->_postData=$postData;
	}

	/**
	 * @return boolean whether the current page request is a postback
	 */
	public function getIsPostBack()
	{
		return $this->_postData!==null;
	}

	/**
	 * @return IPageStatePersister page state persister
	 */
	protected function getPageStatePersister()
	{
		return $this->_pageService->getPageStatePersister();
	}

	/**
	 * This method is invoked when control state is to be saved.
	 * You can override this method to do last step state saving.
	 * Parent implementation must be invoked.
	 * @param TEventParameter event parameter
	 */
	protected function onSaveState($param)
	{
		parent::onSaveState($param);
		$this->setViewState('ControlsRequiringPostBack',$this->_controlsRegisteredForPostBack,array());
	}

	/**
	 * This method is invoked right after the control has loaded its state.
	 * You can override this method to initialize data from the control state.
	 * Parent implementation must be invoked.
	 * @param TEventParameter
	 */
	protected function onLoadState($param)
	{
		$this->_controlsRequiringPostBack=$this->getViewState('ControlsRequiringPostBack',array());
		parent::onLoadState($param);
	}

	/**
	 * Loads page state from persistent storage.
	 */
	protected function loadPageState()
	{
		$state=$this->getPageStatePersister()->load();
		$this->loadStateRecursive($state,$this->getEnableViewState());
	}

	/**
	 * Saves page state from persistent storage.
	 */
	protected function savePageState()
	{
		$state=&$this->saveStateRecursive($this->getEnableViewState());
		$this->getPageStatePersister()->save($state);
	}

	/**
	 * @param string the field name
	 * @return boolean whether the specified field is a system field in postback data
	 */
	protected function isSystemPostField($field)
	{
		return isset(self::$_systemPostFields[$field]);
	}

	/**
	 * Registers a control for loading post data in the next postback.
	 * @param TControl control registered for loading post data
	 */
	public function registerRequiresPostBack(TControl $control)
	{
		$this->_controlsRegisteredForPostBack[$control->getUniqueID()]=true;
	}

	/**
	 * @return TControl the control responsible for the current postback event, null if nonexistent
	 */
	public function getPostBackEventTarget()
	{
		if($this->_postBackEventTarget===null)
		{
			$eventTarget=$this->_postData->itemAt(self::FIELD_POSTBACK_TARGET);
			if(!empty($eventTarget))
				$this->_postBackEventTarget=$this->findControl($eventTarget);
		}
		return $this->_postBackEventTarget;
	}

	/**
	 * Registers a control to raise postback event in the current request.
	 * @param TControl control registered to raise postback event.
	 */
	public function setPostBackEventTarget(TControl $control)
	{
		$this->_postBackEventTarget=$control;
	}

	/**
	 * @return mixed postback event parameter
	 */
	public function getPostBackEventParameter()
	{
		if($this->_postBackEventParameter===null)
			$this->_postBackEventParameter=$this->_postData->itemAt(self::FIELD_POSTBACK_PARAMETER);
		return $this->_postBackEventParameter;
	}

	/**
	 * @param mixed postback event parameter
	 */
	public function setPostBackEventParameter($value)
	{
		$this->_postBackEventParameter=$value;
	}

	/**
	 * Registers a control as the
	 */
	public function registerAutoPostBackControl(TControl $control)
	{
		$this->_autoPostBackControl=$control;
	}

	/**
	 * Processes post data.
	 * @param TMap post data to be processed
	 * @param boolean whether this method is invoked before {@link onLoad Load}.
	 */
	protected function processPostData($postData,$beforeLoad)
	{
		if($beforeLoad)
			$this->_restPostData=new TMap;
		foreach($postData as $key=>$value)
		{
			if($this->isSystemPostField($key))
				continue;
			else if($control=$this->findControl($key))
			{
				if($control instanceof IPostBackDataHandler)
				{
					if($control->loadPostData($key,$postData))
						$this->_controlsPostDataChanged[]=$control;
				}
				else if($control instanceof IPostBackEventHandler)
					$this->setPostBackEventTarget($control);
				unset($this->_controlsRequiringPostBack[$key]);
			}
			else if($beforeLoad)
				$this->_restPostData->add($key,$value);
		}
		foreach($this->_controlsRequiringPostBack as $key=>$value)
		{
			if($control=$this->findControl($key))
			{
				if($control instanceof IPostBackDataHandler)
				{
					if($control->loadPostData($key,$this->_postData))
						$this->_controlsPostDataChanged[]=$control;
				}
				else
					throw new TInvalidDataValueException('page_postbackcontrol_invalid',$key);
				unset($this->_controlsRequiringPostBack[$key]);
			}
		}
	}

	/**
	 * Raises PostDataChangedEvent for controls whose data have been changed due to the postback.
	 */
	private function raiseChangedEvents()
	{
		foreach($this->_controlsPostDataChanged as $control)
			$control->raisePostDataChangedEvent();
	}

	/**
	 * Raises PostBack event.
	 */
	private function raisePostBackEvent()
	{
		if(($postBackHandler=$this->getPostBackEventTarget())===null)
			$this->validate();
		else if($postBackHandler instanceof IPostBackEventHandler)
			$postBackHandler->raisePostBackEvent($this->getPostBackEventParameter());
	}

	/**
	 * Ensures the control is rendered within a form.
	 * @param TControl the control to be rendered
	 * @throws TInvalidConfigurationException if the control is outside of the form
	 */
	public function ensureRenderInForm($control)
	{
		if(!$this->_inFormRender)
			throw new TInvalidConfigurationException('page_control_outofform',get_class($control),$control->getID(false));
	}

	/**
	 * @internal
	 */
	public function beginFormRender($writer)
	{
		if($this->_formRendered)
			throw new TInvalidConfigurationException('page_singleform_required');
		$this->_formRendered=true;
		$this->_inFormRender=true;
		$this->getClientScript()->renderBeginScripts($writer);
	}

	/**
	 * @internal
	 */
	public function endFormRender($writer)
	{
		$cs=$this->getClientScript();
		if($this->getClientSupportsJavaScript())
		{
			if($this->_focusedControl && $this->_focusedControl->getVisible(true))
				$cs->registerFocusScript($this->_focusedControl->getClientID());
			else if($this->_postData && ($lastFocus=$this->_postData->itemAt(self::FIELD_LASTFOCUS))!==null)
				$cs->registerFocusScript($lastFocus);
			if($this->_maintainScrollPosition && $this->_postData)
			{
				$x=TPropertyValue::ensureInteger($this->_postData->itemAt(self::PRADO_SCROLLX));
				$y=TPropertyValue::ensureInteger($this->_postData->itemAt(self::PRADO_SCROLLY));
				$cs->registerScrollScript($x,$y);
			}
			$cs->renderHiddenFields($writer);
			$cs->renderArrayDeclarations($writer);
			$cs->renderExpandoAttributes($writer);
			$cs->renderScriptIncludes($writer);
			$cs->renderEndScripts($writer);
		}
		else
			$cs->renderHiddenFields($writer);
		$this->_inFormRender=false;
	}

	public function setFocus(TControl $value)
	{
		$this->_focusedControl=$value;
	}

	public function getMaintainScrollPosition()
	{
		return $this->_maintainScrollPosition;
	}

	public function setMaintainScrollPosition($value)
	{
		$this->_maintainScrollPosition=TPropertyValue::ensureBoolean($value);
	}

	public function getClientSupportsJavaScript()
	{
		// todo
		return true;
	}


	public function getClientOnSubmitEvent()
	{
		// todo
		if($this->getClientScript()->getHasSubmitStatements())
			return 'javascript:return WebForm_OnSubmit();';
		else
			return '';
	}

	protected function initializeCulture()
	{
	}

	/**
	 * @internal
	 */
	public function loadScrollPosition()
	{
		if($this->_previousPagePath==='' && $this->_requestValueCollection)
		{
			if(isset($_REQUEST['__SCROLLPOSITIONX']))
				$this->_scrollPositionX=(integer)$_REQUEST['__SCROLLPOSITIONX'];
			if(isset($_REQUEST['__SCROLLPOSITIONY']))
				$this->_scrollPositionX=(integer)$_REQUEST['__SCROLLPOSITIONY'];
		}
	}


	final public function registerAsyncTask()
	{
	}
}

?>