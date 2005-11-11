<?php

Prado::using('System.Web.*');
Prado::using('System.Web.UI.*');
Prado::using('System.Web.UI.WebControls.*');

class TPage extends TTemplateControl
{
	private $_application;
	private $_contentTemplateCollection=null;
	private $_maxPageStateFieldLength=10;
	private $_enableViewStateMac=true;
	private $_performPreRendering=true;
	private $_performRendering=true;
	private $_supportsStyleSheet=true;
	private $_theme=null;
	private $_themeName='';
	private $_styleSheet=null;
	private $_styleSheetName='';

	private $_clientScript=null;
	private $_form=null;
	private $_formRendered=false;
	private $_inFormRender=false;
	private $_pageState='';
	private $_requirePostBackScript=false;
	private $_postBackScriptRendered=false;
	private $_isCrossPagePostBack=false;
	private $_previousPagePath='';
	private $_preInitWorkComplete=false;
	private $_changedPostDataConsumers=array();
	private $_postData;
	private $_restPostData;
	private $_pageStateChanged=false;
	private $_controlsRequiringPostBack=array();
	private $_registeredControlThatRequireRaiseEvent=null;
	private $_registeredControlsThatRequirePostBack=null;
	private $_validators=array();
	private $_validated=false;
	private $_autoPostBackControl=null;
	private $_webFormsScriptRendered=false;
	private $_requireWebFormsScript=false;
	private static $_systemPostFields=array('__EVENTTARGET','__EVENTPARAM','__STATE','__PREVPAGE','__CALLBACKID','__CALLBACKPARAM','__LASTFOCUS');
	private $_contents=array();
	private $_templateFile=null;

	public function __construct($initProperties=null)
	{
		$this->_application=Prado::getApplication();
		$this->setPage($this);
		if(is_array($initProperties))
		{
			foreach($initProperties as $name=>$value)
				$this->setSubProperty($name,$value);
		}
		parent::__construct();
	}

	/**
	 * Loads and parses the control template
	 * @return ITemplate the parsed template structure
	 */
	protected function loadTemplate()
	{
		if($this->_templateFile===null)
			return parent::loadTemplate();
		else
		{
			$template=Prado::getApplication()->getService()->getTemplateManager()->loadTemplateByFileName(Prado::getPathOfNamespace($this->_templateFile,'.tpl'));
			$this->setTemplate($template);
			return $template;
		}
	}

	public function getTemplateFile()
	{
		return $this->_templateFile;
	}

	public function setTemplateFile($value)
	{
		$this->_templateFile=$value;
	}

	final public function setForm($form)
	{
		$this->_form=$form;
	}

	final public function getForm()
	{
		return $this->_form;
	}

	public function validate($validationGroup='')
	{
		$this->_validated=true;
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

	/**
	 * @internal
	 */
	final protected function addContentTemplate($name,$template)
	{
		if(!$this->_contentTemplateCollection)
			$this->_contentTemplateCollection=new TMap;
		if($this->_contentTemplateCollection->has($name))
			throw new Exception("Content '$name' duplicated.");
		$this->_contentTemplateCollection->add($name,$template);
	}

	/**
	 * @internal
	 */
	final public function applyControlSkin($control)
	{
		if($this->_theme)
			$this->_theme->applySkin($control);
	}

	/**
	 * @internal
	 */
	final public function applyControlStyleSheet($control)
	{
		if($this->_styleSheet)
		{
			$this->_styleSheet->applySkin($control);
			return true;
		}
		else
			return false;
	}

	private function renderStateFields($writer)
	{
		$writer->write("\n<input type=\"hidden\" name=\"__STATE\" id=\"__STATE\" value=\"".$this->_pageState."\" />\n");
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
		//$this->renderStateFields($writer);
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
		$this->getClientScript()->renderClientScriptBlocks($writer);
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
		$cs->renderClientStartupScripts($writer);
		$this->_inFormRender=false;
	}

	final public function getClientScript()
	{
		if(!$this->_clientScript)
			$this->_clientScript=new TClientScriptManager($this);
		return $this->_clientScript;
	}

	final public function getClientOnSubmitEvent()
	{
		// todo
		if($this->getClientScript()->getHasSubmitStatements())
			return 'javascript:return WebForm_OnSubmit();';
		else
			return '';
	}

	final public function getValidators($validationGroup='')
	{
		if(!$this->_validators)
			$this->_validators=new TList;
		if($validationGroup==='')
			return $this->_validators;
		$list=new TList;
		foreach($this->_validators as $validator)
			if($validator->getValidationGroup()===$validationGroup)
				$list->add($validator);
		return $list;
	}

	protected function initializeCulture()
	{
	}

	/**
	 * @internal
	 */
	public function initializeStyleSheet()
	{
		if($this->_styleSheet!=='')
			$this->_styleSheet=new TTheme($this->_styleSheetName);
	}

	private function initializeThemes()
	{
		if($this->_themeName!=='')
			$this->_theme=new TTheme($this->_themeName);
		if($this->_styleSheetName!=='')
			$this->_styleSheet=new TTheme($this->_styleSheetName);
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

	protected function onInit($param)
	{
		parent::onInit($param);/*
		if($this->_theme)
			$this->_theme->setStyleSheet();
		if($this->_styleSheet)
			$this->_styleSheet->setStyleSheet();*/
	}

	protected function onInitComplete($param)
	{
		$this->raiseEvent('InitComplete',$this,$param);
	}

	protected function onLoadComplete($param)
	{
		$this->raiseEvent('LoadComplete',$this,$param);
	}

	protected function onPreInit($param)
	{
		$this->raiseEvent('PreInit',$this,$param);
	}

	protected function onPreLoad($param)
	{
		$this->raiseEvent('PreLoad',$this,$param);
	}

	protected function onPreRenderComplete($param)
	{
		$this->raiseEvent('PreRenderComplete',$this,$param);
	}

	protected function onSaveStateComplete($param)
	{
		$this->raiseEvent('SaveStateComplete',$this,$param);
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

	public function getApplication()
	{
		return $this->_application;
	}

	public function loadStateField()
	{
		return base64_decode($this->_postData->itemAt('__STATE'));
	}

	public function saveStateField($state)
	{
		$this->getClientScript()->registerHiddenField('__STATE',base64_encode($state));
	}

	protected function determinePostBackMode()
	{
		/*
		$application=$this->getApplication();
		if($application->getPreventPostBack())
			return null;
		*/
		$postData=new TMap($this->_application->getRequest()->getItems());
		if($postData->itemAt('__STATE')!==null || $postData->itemAt('__EVENTTARGET')!==null)
			return $postData;
		else
			return null;
	}

	final public function getIsPostBack()
	{
		if($this->_postData)
		{
			if($this->_isCrossPagePostBack)
				return true;
			if($this->_previousPagePath!=='')
				return false;
			return !$this->_pageStateChanged;
		}
		else
			return false;
	}

	protected function getPageStatePersister()
	{
		require_once(PRADO_DIR.'/Web/UI/THiddenFieldPageStatePersister.php');
		return new THiddenFieldPageStatePersister($this);
	}

	protected function loadPageState()
	{
		$persister=$this->getPageStatePersister();
		$state=$persister->load();
		$this->loadStateRecursive($state,$this->getEnableViewState());
	}

	protected function savePageState()
	{
		$state=&$this->saveStateRecursive($this->getEnableViewState());
		$persister=$this->getPageStatePersister();
		$persister->save($state);
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
		$this->_postData=$this->determinePostBackMode();
		$this->_restPostData=new TMap;

		$this->onPreInit(null);
		$this->initializeThemes();
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

	public function getTheme()
	{
		return $this->_themeName;
	}

	public function setTheme($value)
	{
		$this->_themeName=$value;
	}

	public function getStyleSheetTheme()
	{
		return $this->_styleSheetName;
	}

	public function setStyleSheetTheme($value)
	{
		$this->_styleSheetName=$value;
	}

	public function getContainsTheme()
	{
		return $this->_theme!==null;
	}
}

?>