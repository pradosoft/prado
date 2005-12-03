<?php

Prado::using('System.Web.UI.*');
Prado::using('System.Web.UI.WebControls.*');

class TPage extends TTemplateControl
{
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

	private $_maxPageStateFieldLength=10;
	private $_enableViewStateMac=true;
	private $_performPreRendering=true;
	private $_performRendering=true;

	private $_formRendered=false;
	private $_inFormRender=false;
	private $_requirePostBackScript=false;
	private $_postBackScriptRendered=false;
	private $_isCrossPagePostBack=false;
	private $_previousPagePath='';
	private $_preInitWorkComplete=false;
	private $_changedPostDataConsumers=array();
	private $_controlsRequiringPostBack=array();
	private $_registeredControlThatRequireRaiseEvent=null;
	private $_registeredControlsThatRequirePostBack=null;
	private $_autoPostBackControl=null;
	private $_webFormsScriptRendered=false;
	private $_requireWebFormsScript=false;
	private static $_systemPostFields=array('__EVENTTARGET','__EVENTPARAM','__STATE','__PREVPAGE','__CALLBACKID','__CALLBACKPARAM','__LASTFOCUS');

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
		if($postData->contains(TClientScriptManager::FIELD_PAGE_STATE) || $postData->contains(TClientScriptManager::FIELD_POSTBACK_TARGET))
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

	public function RegisterEnabledControl($control)
	{
		$this->getEna.EnabledControls.Add(control);
	}



	/**
	 * @internal
	 */
	public function registerPostBackScript()
	{
		if($this->getClientSupportsJavaScript() && !$this->_postBackScriptRendered)
		{
			if(!$this->_requirePostBackScript)
			{
				$this->getClientScript()->registerHiddenField('__EVENTTARGET','');
				$this->getClientScript()->registerHiddenField('__EVENTPARAM','');
				$this->_requirePostBackScript=true;
			}
		}
	}

	public function registerWebFormsScript()
	{
		if($this->getClientSupportsJavaScript() && !$this->_webFormsScriptRendered)
		{
			$this->registerPostBackScript();
			$this->_requireWebFormsScript=true;
		}
	}


	public function ensureRenderInForm($control)
	{
		if(!$this->_inFormRender)
			throw new THttpException('control_not_in_form',$control->getUniqueID());
	}

	private function renderPostBackScript($writer)
	{
		$id=$this->_form->getUniqueID();
		$str=<<<EOD
\n<script type="text/javascript">
<!--
var theForm=document.forms['$id'];
if(!theForm)
	theForm=document.$id;
function __doPostBack(eventTarget,eventParam) {
	if(!theForm.onsubmit || (theForm.onsubmit()!=false)) {
		theForm.__EVENTTARGET.value = eventTarget;
		theForm.__EVENTPARAM.value = eventParam;
		theForm.submit();
	}
}
// -->
</script>\n
EOD;
		$writer->write($str);
		$this->_postBackScriptRendered=true;
	}

	private function renderWebFormsScript($writer)
	{
		$writer->write("\n<script src=\"js/WebForms.js\" type=\"text/javascript\"></script>\n");
		$this->_webFormsScriptRendered=true;
	}

	final public function getClientSupportsJavaScript()
	{
		// todo
		return true;
	}

	/**
	 * @internal
	 */
	final public function beginFormRender($writer)
	{
		if($this->_formRendered)
			throw new THttpException('multiple_form_not_allowed');
		$this->_formRendered=true;
		$this->_inFormRender=true;

		$this->getClientScript()->renderHiddenFields($writer);
		if($this->getClientSupportsJavaScript())
		{
			/*
			if($this->getMaintainScrollPositionOnPostBack() && !$this->_requireScrollScript)
			{
				$cs=$this->getClientScript();
				$cs->registerHiddenField('_SCROLLPOSITIONX',$this->_scrollPositionX);
				$cs->registerHiddenField('_SCROLLPOSITIONY',$this->_scrollPositionY);
				$cs->registerStartupScript(get_class($this),"PageScrollPositionScript", "\r\nvar WebForm_ScrollPositionSubmit = theForm.submit;\r\ntheForm.submit = WebForm_SaveScrollPositionSubmit;\r\n\r\nvar WebForm_ScrollPositionOnSubmit = theForm.onsubmit;\r\ntheForm.onsubmit = WebForm_SaveScrollPositionOnSubmit;\r\n\r\nvar WebForm_ScrollPositionLoad = window.onload;\r\nwindow.onload = WebForm_RestoreScrollPosition;\r\n", true);
				$this->registerWebFormScript();
				$this->_requireScrollScript=true;
			}
			*/
			if($this->_requirePostBackScript)
				$this->renderPostBackScript($writer,$this->_form->getUniqueID());
			if($this->_requireWebFormsScript)
				$this->renderWebFormsScript($writer);
		}
		$this->getClientScript()->renderScriptBlocks($writer);
		// todo: more ....
	}

	final public function getIsPostBackEventControlRegistered()
	{
		return $this->_registeredControlThatRequireRaiseEvent!==null;
	}

	/**
	 * @internal
	 */
	final public function endFormRender($writer)
	{
		$cs=$this->getClientScript();
		if($this->getClientSupportsJavaScript())
			$cs->renderArrayDeclarations($writer);
		$cs->renderHiddenFields($writer);
		if($this->getClientSupportsJavaScript())
		{
			if($this->_requirePostBackScript && !$this->_postBackScriptRendered)
				$this->renderPostBackScript($writer);
			if($this->_requireWebFormsScript && !$this->_webFormsScriptRendered)
				$this->renderWebFormsScript($writer);
		}
		$cs->renderStartupScripts($writer);
		$this->_inFormRender=false;
	}

	final public function getClientOnSubmitEvent()
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

	final public function registerRequiresPostBack($control)
	{
		if(!$this->_registeredControlsThatRequirePostBack)
			$this->_registeredControlsThatRequirePostBack=new TList;
		$this->_registeredControlsThatRequirePostBack->add($control->getUniqueID());
	}

	final public function registerRequiresRaiseEvent($control)
	{
		$this->_registeredControlThatRequireRaiseEvent=$control;
	}

	protected function processPostData($postData,$beforeLoad)
	{
		$eventTarget=$postData->itemAt('__EVENTTARGET');
		foreach($postData as $key=>$value)
		{
			if(in_array($key,self::$_systemPostFields))
				continue;
			else if($control=$this->findControl($key))
			{
				if($control instanceof IPostBackDataHandler)
				{
					if($control->loadPostData($key,$this->_postData))
						$this->_changedPostDataConsumers[]=$control;
					unset($this->_controlsRequiringPostBack[$key]);
				}
				else
				{
					if(empty($eventTarget))
					{
						if($control instanceof IPostBackEventHandler)
							$this->registerRequiresRaiseEvent($control);
					}
					else
						unset($this->_controlsRequiringPostBack[$key]);
				}
			}
			else if($beforeLoad)
				$this->_restPostData->add($key,$value);
		}
		$list=new TMap;
		foreach($this->_controlsRequiringPostBack as $key=>$value)
		{
			if($control=$this->findControl($key))
			{
				if($control instanceof IPostBackDataHandler)
				{
					if($control->loadPostData($key,$this->_postData))
						$this->_changedPostDataConsumers->add($control);
				}
				else
					throw new THttpException('postback_control_not_found',$key);
			}
			else if($beforeLoad)
				$list->add($key,null);
		}
		$this->_controlsRequiringPostBack=$list;
	}

	final public function getAutoPostBackControl()
	{
		return $this->_autoPostBackControl;
	}

	final public function setAutoPostBackControl($control)
	{
		$this->_autoPostBackControl=$control;
	}

	private function raiseChangedEvents()
	{
		foreach($this->_changedPostDataConsumers as $control)
			$control->raisePostDataChangedEvent();
	}

	private function raisePostBackEvent($postData)
	{
		if($this->_registeredControlThatRequireRaiseEvent)
		{
			$this->_registeredControlThatRequireRaiseEvent->raisePostBackEvent(null);
		}
		else
		{
			$eventTarget=$postData->itemAt('__EVENTTARGET');
			if(!empty($eventTarget) || $this->getAutoPostBackControl())
			{
				if(!empty($eventTarget))
					$control=$this->findControl($eventTarget);
				else
					$control=null;
				if($control instanceof IPostBackEventHandler)
					$control->raisePostBackEvent($postData->itemAt('__EVENTPARAM'));
			}
			else
				$this->validate();
		}
	}

	public function run($writer)
	{
		$this->determinePostBackMode();
		$this->_restPostData=new TMap;

		$this->onPreInit(null);
		$this->_preInitWorkComplete=true;

		$this->initRecursive(null);
		$this->onInitComplete(null);

		if($this->getIsPostBack())
		{
			$this->loadPageState();
			$this->processPostData($this->_postData,true);
		}

		$this->onPreLoad(null);
		$this->loadRecursive(null);
		if($this->getIsPostBack())
		{
			$this->processPostData($this->_restPostData,false);
			$this->raiseChangedEvents();
			$this->raisePostBackEvent($this->_postData);
		}
		$this->onLoadComplete(null);

		$this->preRenderRecursive();
		$this->onPreRenderComplete(null);

		$this->savePageState();
		$this->onSaveStateComplete(null);

		$this->renderControl($writer);
		$this->unloadRecursive();
	}

}

?>