<?php
/**
 * TPage class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

//Prado::using('System.Web.UI.*');
Prado::using('System.Web.UI.WebControls.*');
Prado::using('System.Web.UI.TControl');
Prado::using('System.Web.UI.TTemplateControl');
Prado::using('System.Web.UI.TForm');
Prado::using('System.Web.UI.TClientScriptManager');

//Prado::using('System.Web

/**
 * TPage class
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
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
		'PRADO_POSTBACK_TARGET'=>true,
		'PRADO_POSTBACK_PARAMETER'=>true,
		'PRADO_LASTFOCUS'=>true,
		'PRADO_PAGESTATE'=>true,
		'PRADO_SCROLLX'=>true,
		'PRADO_SCROLLY'=>true,
		'__PREVPAGE','__CALLBACKID','__CALLBACKPARAM'
	);
	/**
	 * @var TForm form instance
	 */
	private $_form=null;
	/**
	 * @var THead head instance
	 */
	private $_head=null;
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
	private $_controlsRequiringPostData=array();
	/**
	 * @var array list of controls that need to load post data in the next postback
	 */
	private $_controlsRegisteredForPostData=array();
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
	 * @var TControl|string the control or the ID of the element on the page to be focused when the page is sent back to user
	 */
	private $_focus=null;
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
	 * Sets the page object to itself.
	 */
	public function __construct()
	{
		parent::__construct();
		$this->setPage($this);
	}

	/**
	 * Runs through the page lifecycles.
	 * This method runs through the page lifecycles.
	 * @param THtmlTextWriter the HTML writer
	 */
	public function run($writer)
	{
		Prado::trace("Running page life cycles",'System.Web.UI.TPage');
		$this->determinePostBackMode();

		Prado::trace("Page onPreInit()",'System.Web.UI.TPage');
		$this->onPreInit(null);
		Prado::trace("Page initRecursive()",'System.Web.UI.TPage');
		$this->initRecursive();
		Prado::trace("Page onInitComplete()",'System.Web.UI.TPage');
		$this->onInitComplete(null);

		if($this->getIsPostBack())
		{
			$this->_restPostData=new TMap;
			Prado::trace("Page loadPageState()",'System.Web.UI.TPage');
			$this->loadPageState();
			Prado::trace("Page processPostData()",'System.Web.UI.TPage');
			$this->processPostData($this->_postData,true);
			Prado::trace("Page onPreLoad()",'System.Web.UI.TPage');
			$this->onPreLoad(null);
			Prado::trace("Page loadRecursive()",'System.Web.UI.TPage');
			$this->loadRecursive();
			Prado::trace("Page processPostData()",'System.Web.UI.TPage');
			$this->processPostData($this->_restPostData,false);
			Prado::trace("Page raiseChangedEvents()",'System.Web.UI.TPage');
			$this->raiseChangedEvents();
			Prado::trace("Page raisePostBackEvent()",'System.Web.UI.TPage');
			$this->raisePostBackEvent();
			Prado::trace("Page onLoadComplete()",'System.Web.UI.TPage');
			$this->onLoadComplete(null);
		}
		else
		{
			Prado::trace("Page onPreLoad()",'System.Web.UI.TPage');
			$this->onPreLoad(null);
			Prado::trace("Page loadRecursive()",'System.Web.UI.TPage');
			$this->loadRecursive();
			Prado::trace("Page onLoadComplete()",'System.Web.UI.TPage');
			$this->onLoadComplete(null);
		}

		Prado::trace("Page preRenderRecursive()",'System.Web.UI.TPage');
		$this->preRenderRecursive();
		Prado::trace("Page onPreRenderComplete()",'System.Web.UI.TPage');
		$this->onPreRenderComplete(null);

		Prado::trace("Page savePageState()",'System.Web.UI.TPage');
		$this->savePageState();
		Prado::trace("Page onSaveStateComplete()",'System.Web.UI.TPage');
		$this->onSaveStateComplete(null);

		Prado::trace("Page renderControl()",'System.Web.UI.TPage');
		$this->renderControl($writer);
		Prado::trace("Page unloadRecursive()",'System.Web.UI.TPage');
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
			$template=$this->getService()->getTemplateManager()->getTemplateByFileName($this->_templateFile);
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
		if(($templateFile=Prado::getPathOfNamespace($value,TTemplateManager::TEMPLATE_FILE_EXT))===null || !is_file($templateFile))
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
	 * @return TList registered validators in the requested group. If the group is null, all validators will be returned.
	 */
	public function getValidators($validationGroup=null)
	{
		if(!$this->_validators)
			$this->_validators=new TList;
		if($validationGroup===null)
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
			Prado::trace("Page validate",'System.Web.UI.TPage');
			foreach($this->_validators as $validator)
			{
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
			$this->_theme=$this->getService()->getThemeManager()->getTheme($this->_theme);
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
			$this->_styleSheet=$this->getService()->getThemeManager()->getTheme($this->_styleSheet);
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
	 * Raises OnPreInit event.
	 * This method is invoked right before {@link onInit OnInit} stage.
	 * You may override this method to provide additional initialization that
	 * should be done before {@link onInit OnInit} (e.g. setting {@link setTheme Theme} or
	 * {@link setStyleSheetTheme StyleSheetTheme}).
	 * Remember to call the parent implementation to ensure PreInit event is raised.
	 * @param mixed event parameter
	 */
	public function onPreInit($param)
	{
		$this->raiseEvent('OnPreInit',$this,$param);
	}

	/**
	 * Raises OnInitComplete event.
	 * This method is invoked right after {@link onInit OnInit} stage and before {@link onLoad OnLoad} stage.
	 * You may override this method to provide additional initialization that
	 * should be done after {@link onInit OnInit}.
	 * Remember to call the parent implementation to ensure InitComplete event is raised.
	 * @param mixed event parameter
	 */
	public function onInitComplete($param)
	{
		$this->raiseEvent('OnInitComplete',$this,$param);
	}

	/**
	 * Raises OnPreLoad event.
	 * This method is invoked right before {@link onLoad OnLoad} stage.
	 * You may override this method to provide additional page loading logic that
	 * should be done before {@link onLoad OnLoad}.
	 * Remember to call the parent implementation to ensure PreLoad event is raised.
	 * @param mixed event parameter
	 */
	public function onPreLoad($param)
	{
		$this->raiseEvent('OnPreLoad',$this,$param);
	}

	/**
	 * Raises OnLoadComplete event.
	 * This method is invoked right after {@link onLoad OnLoad} stage.
	 * You may override this method to provide additional page loading logic that
	 * should be done after {@link onLoad OnLoad}.
	 * Remember to call the parent implementation to ensure LoadComplete event is raised.
	 * @param mixed event parameter
	 */
	public function onLoadComplete($param)
	{
		$this->raiseEvent('OnLoadComplete',$this,$param);
	}

	/**
	 * Raises OnPreRenderComplete event.
	 * This method is invoked right after {@link onPreRender OnPreRender} stage.
	 * You may override this method to provide additional preparation for page rendering
	 * that should be done after {@link onPreRender OnPreRender}.
	 * Remember to call the parent implementation to ensure PreRenderComplete event is raised.
	 * @param mixed event parameter
	 */
	public function onPreRenderComplete($param)
	{
		$this->raiseEvent('OnPreRenderComplete',$this,$param);
		$cs=$this->getClientScript();
		if($this->_theme instanceof ITheme)
		{
			foreach($this->_theme->getStyleSheetFiles() as $url)
				$cs->registerStyleSheetFile($url,$url);
			foreach($this->_theme->getJavaScriptFiles() as $url)
				$cs->registerHeadScriptFile($url,$url);
		}
		if($this->_styleSheet instanceof ITheme)
		{
			foreach($this->_styleSheet->getStyleSheetFiles() as $url)
				$cs->registerStyleSheetFile($url,$url);
			foreach($this->_styleSheet->getJavaScriptFiles() as $url)
				$cs->registerHeadScriptFile($url,$url);
		}
	}

	/**
	 * Raises OnSaveStateComplete event.
	 * This method is invoked right after {@link onSaveState OnSaveState} stage.
	 * You may override this method to provide additional logic after page state is saved.
	 * Remember to call the parent implementation to ensure SaveStateComplete event is raised.
	 * @param mixed event parameter
	 */
	public function onSaveStateComplete($param)
	{
		$this->raiseEvent('OnSaveStateComplete',$this,$param);
	}

	/**
	 * Determines whether the current page request is a postback.
	 * Call {@link getIsPostBack} to get the result.
	 */
	private function determinePostBackMode()
	{
		$postData=$this->getRequest();
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
	 * @return IStatePersister page state persister
	 */
	protected function getPageStatePersister()
	{
		return $this->getService()->getPageStatePersister();
	}

	/**
	 * This method is invoked when control state is to be saved.
	 * You can override this method to do last step state saving.
	 * Parent implementation must be invoked.
	 * @param TEventParameter event parameter
	 */
	public function onSaveState($param)
	{
		parent::onSaveState($param);
		$this->setViewState('ControlsRequiringPostBack',$this->_controlsRegisteredForPostData,array());
	}

	/**
	 * This method is invoked right after the control has loaded its state.
	 * You can override this method to initialize data from the control state.
	 * Parent implementation must be invoked.
	 * @param TEventParameter
	 */
	public function onLoadState($param)
	{
		$this->_controlsRequiringPostData=$this->getViewState('ControlsRequiringPostBack',array());
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
	public function registerRequiresPostData(TControl $control)
	{
		$this->_controlsRegisteredForPostData[$control->getUniqueID()]=true;
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
	 * @param boolean whether this method is invoked before {@link onLoad OnLoad}.
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
				unset($this->_controlsRequiringPostData[$key]);
			}
			else if($beforeLoad)
				$this->_restPostData->add($key,$value);
		}
		foreach($this->_controlsRequiringPostData as $key=>$value)
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
				unset($this->_controlsRequiringPostData[$key]);
			}
		}
	}

	/**
	 * Raises OnPostDataChangedEvent for controls whose data have been changed due to the postback.
	 */
	private function raiseChangedEvents()
	{
		foreach($this->_controlsPostDataChanged as $control)
			$control->raisePostDataChangedEvent();
	}

	/**
	 * Raises OnPostBack event.
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
	 * @throws TConfigurationException if the control is outside of the form
	 */
	public function ensureRenderInForm($control)
	{
		if(!$this->_inFormRender)
			throw new TConfigurationException('page_control_outofform',get_class($control),$control->getID(false));
	}

	/**
	 * @internal This method is invoked by TForm  at the beginning of its rendering
	 */
	public function beginFormRender($writer)
	{
		if($this->_formRendered)
			throw new TConfigurationException('page_singleform_required');
		$this->_formRendered=true;
		$this->_inFormRender=true;
		$cs=$this->getClientScript();
		$cs->renderHiddenFields($writer);
		$cs->renderBeginScripts($writer);
	}

	/**
	 * @internal This method is invoked by TForm  at the end of its rendering
	 */
	public function endFormRender($writer)
	{
		$cs=$this->getClientScript();
		if($this->getClientSupportsJavaScript())
		{
			if($this->_focus)
			{
				if(is_string($this->_focus))
					$cs->registerFocusScript($this->_focus);
				else if(($this->_focus instanceof TControl) && $this->_focus->getVisible(true))
					$cs->registerFocusScript($this->_focus->getClientID());
			}
			else if($this->_postData && ($lastFocus=$this->_postData->itemAt(self::FIELD_LASTFOCUS))!==null)
				$cs->registerFocusScript($lastFocus);
			if($this->_maintainScrollPosition && $this->_postData)
			{
				$x=TPropertyValue::ensureInteger($this->_postData->itemAt(self::PRADO_SCROLLX));
				$y=TPropertyValue::ensureInteger($this->_postData->itemAt(self::PRADO_SCROLLY));
				$cs->registerScrollScript($x,$y);
			}
			$cs->renderHiddenFields($writer);
			//$cs->renderArrayDeclarations($writer);
			//$cs->renderExpandoAttributes($writer);
			$cs->renderScriptFiles($writer);
			$cs->renderEndScripts($writer);
		}
		else
			$cs->renderHiddenFields($writer);
		$this->_inFormRender=false;
	}

	/**
	 * Sets input focus on a control after the page is rendered to users.
	 * @param TControl|string control to receive focus, or the ID of the element on the page to receive focus
	 */
	public function setFocus($value)
	{
		$this->_focus=$value;
	}

	/**
	 * @return boolean (TBD) whether to keep the page scroll position the same as users last see it
	 */
	public function getMaintainScrollPosition()
	{
		return $this->_maintainScrollPosition;
	}

	/**
	 * @param boolean (TBD) whether to keep the page scroll position the same as users last see it
	 */
	public function setMaintainScrollPosition($value)
	{
		$this->_maintainScrollPosition=TPropertyValue::ensureBoolean($value);
	}

	public function getClientSupportsJavaScript()
	{
		// todo
		return true;
	}

	protected function initializeCulture()
	{
	}

	public function getHead()
	{
		return $this->_head;
	}

	public function setHead(THead $value)
	{
		if($this->_head)
			throw new TInvalidOperationException('page_head_duplicated');
		$this->_head=$value;
	}

	public function getTitle()
	{
		return $this->getViewState('Title','');
	}

	public function setTitle($value)
	{
		$this->setViewState('Title',$value,'');
	}
}

?>