<?php
/**
 * TControl, TControlList, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 */

/**
 * TControl class
 *
 * TControl is the base class for all components on a page hierarchy.
 * It implements the following features for UI-related functionalities:
 * - databinding feature
 * - parent and child relationship
 * - naming container and containee relationship
 * - viewstate and controlstate features
 * - rendering scheme
 * - control lifecycles
 *
 * A property can be data-bound with an expression. By calling {@link dataBind},
 * expressions bound to properties will be evaluated and the results will be
 * set to the corresponding properties.
 *
 * Parent and child relationship determines how the presentation of controls are
 * enclosed within each other. A parent will determine where to place
 * the presentation of its child controls. For example, a TPanel will enclose
 * all its child controls' presentation within a div html tag. A control's parent
 * can be obtained via {@link getParent Parent} property, and its
 * {@link getControls Controls} property returns a list of the control's children,
 * including controls and static texts. The property can be manipulated
 * like an array for adding or removing a child (see {@link TList} for more details).
 *
 * A naming container control implements INamingContainer and ensures that
 * its containee controls can be differentiated by their ID property values.
 * Naming container and containee realtionship specifies a protocol to uniquely
 * identify an arbitrary control on a page hierarchy by an ID path (concatenation
 * of all naming containers' IDs and the target control's ID).
 *
 * Viewstate and controlstate are two approaches to preserve state across
 * page postback requests. ViewState is mainly related with UI specific state
 * and can be disabled if not needed. ControlState represents crucial logic state
 * and cannot be disabled.
 *
 * A control is rendered via its {@link render()} method (the method is invoked
 * by the framework.) Descendant control classes may override this method for
 * customized rendering. By default, {@link render()} invokes {@link renderChildren()}
 * which is responsible for rendering of children of the control.
 * Control's {@link getVisible Visible} property governs whether the control
 * should be rendered or not.
 *
 * Each control on a page will undergo a series of lifecycles, including
 * control construction, Init, Load, PreRender, Render, and OnUnload.
 * They work together with page lifecycles to process a page request.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TControl extends TComponent
{
	/**
	 * format of control ID
	 */
	const ID_FORMAT='/^[a-zA-Z_]\\w*$/';
	/**
	 * separator char between IDs in a UniqueID
	 */
	const ID_SEPARATOR='$';
	/**
	 * separator char between IDs in a ClientID
	 */
	const CLIENT_ID_SEPARATOR='_';
	/**
	 * prefix to an ID automatically generated
	 */
	const AUTOMATIC_ID_PREFIX='ctl';

	/**
	 * the stage of lifecycles that the control is currently at
	 */
	const CS_CONSTRUCTED=0;
	const CS_CHILD_INITIALIZED=1;
	const CS_INITIALIZED=2;
	const CS_STATE_LOADED=3;
	const CS_LOADED=4;
	const CS_PRERENDERED=5;

	/**
	 * State bits.
	 */
	const IS_ID_SET=0x01;
	const IS_DISABLE_VIEWSTATE=0x02;
	const IS_SKIN_APPLIED=0x04;
	const IS_STYLESHEET_APPLIED=0x08;
	const IS_DISABLE_THEMING=0x10;
	const IS_CHILD_CREATED=0x20;
	const IS_CREATING_CHILD=0x40;

	/**
	 * Indexes for the rare fields.
	 * In order to save memory, rare fields will only be created if they are needed.
	 */
	const RF_CONTROLS=0;			// cihld controls
	const RF_CHILD_STATE=1;			// child state field
	const RF_NAMED_CONTROLS=2;		// list of controls whose namingcontainer is this control
	const RF_NAMED_CONTROLS_ID=3;	// counter for automatic id
	const RF_SKIN_ID=4;				// skin ID
	const RF_DATA_BINDINGS=5;		// data bindings
	const RF_EVENTS=6;				// event handlers
	const RF_CONTROLSTATE=7;		// controlstate
	const RF_NAMED_OBJECTS=8;		// controls declared with ID on template

	/**
	 * @var string control ID
	 */
	private $_id='';
	/**
	 * @var string control unique ID
	 */
	private $_uid='';
	/**
	 * @var TControl parent of the control
	 */
	private $_parent=null;
	/**
	 * @var TPage page that the control resides in
	 */
	private $_page=null;
	/**
	 * @var TControl naming container of the control
	 */
	private $_namingContainer=null;
	/**
	 * @var TTemplateControl control whose template contains the control
	 */
	private $_tplControl=null;
	/**
	 * @var TMap viewstate data
	 */
	private $_viewState=array();
	/**
	 * @var integer the current stage of the control lifecycles
	 */
	private $_stage=0;
	/**
	 * @var integer representation of the state bits
	 */
	private $_flags=0;
	/**
	 * @var array a collection of rare control data
	 */
	private $_rf=array();


	/**
	 * Returns a property value by name or a control by ID.
	 * This overrides the parent implementation by allowing accessing
	 * a control via its ID using the following syntax,
	 * <code>
	 * $menuBar=$this->menuBar;
	 * </code>
	 * Note, the control must be configured in the template
	 * with explicit ID. If the name matches both a property and a control ID,
	 * the control ID will take the precedence.
	 *
	 * @param string the property name or control ID
	 * @return mixed the property value or the target control
	 * @throws TInvalidOperationException if the property is not defined.
	 * @see registerObject
	 */
	public function __get($name)
	{
		if(isset($this->_rf[self::RF_NAMED_OBJECTS][$name]))
			return $this->_rf[self::RF_NAMED_OBJECTS][$name];
		else
			return parent::__get($name);
	}

	/**
	 * @return TControl the parent of this control
	 */
	public function getParent()
	{
		return $this->_parent;
	}

	/**
	 * @return TControl the naming container of this control
	 */
	public function getNamingContainer()
	{
		if(!$this->_namingContainer && $this->_parent)
		{
			if($this->_parent instanceof INamingContainer)
				$this->_namingContainer=$this->_parent;
			else
				$this->_namingContainer=$this->_parent->getNamingContainer();
		}
		return $this->_namingContainer;
	}

	/**
	 * @return TPage the page that contains this control
	 */
	public function getPage()
	{
		if(!$this->_page)
		{
			if($this->_parent)
				$this->_page=$this->_parent->getPage();
			else if($this->_tplControl)
				$this->_page=$this->_tplControl->getPage();
		}
		return $this->_page;
	}

	/**
	 * Sets the page for a control.
	 * Only framework developers should use this method.
	 * @param TPage the page that contains this control
	 */
	public function setPage($page)
	{
		$this->_page=$page;
	}

	/**
	 * Sets the control whose template contains this control.
	 * Only framework developers should use this method.
	 * @param TTemplateControl the control whose template contains this control
	 */
	public function setTemplateControl($control)
	{
		$this->_tplControl=$control;
	}

	/**
	 * @return TTemplateControl the control whose template contains this control
	 */
	public function getTemplateControl()
	{
		if(!$this->_tplControl && $this->_parent)
			$this->_tplControl=$this->_parent->getTemplateControl();
		return $this->_tplControl;
	}

	/**
	 * @return TApplication the application object that the current page is using
	 */
	public function getApplication()
	{
		return Prado::getApplication();
	}

	/**
	 * @return TPageService the page service
	 */
	public function getService()
	{
		return Prado::getApplication()->getService();
	}

	/**
	 * @return THttpRequest the current user request
	 */
	public function getRequest()
	{
		return Prado::getApplication()->getRequest();
	}

	/**
	 * @return THttpResponse the response
	 */
	public function getResponse()
	{
		return Prado::getApplication()->getResponse();
	}

	/**
	 * @return THttpSession user session
	 */
	public function getSession()
	{
		return Prado::getApplication()->getSession();
	}

	/**
	 * @return IUser user
	 */
	public function getUser()
	{
		return Prado::getApplication()->getUser();
	}

	/**
	 * Publishes a private asset and gets its URL.
	 * This method will publish a private asset (file or directory)
	 * and gets the URL to the asset. Note, if the asset refers to
	 * a directory, all contents under that directory will be published.
	 * @param string path of the asset that is relative to the directory containing the control class file.
	 * @return string URL to the asset path.
	 */
	public function getAsset($assetPath)
	{
		$class=new ReflectionClass(get_class($this));
		$assetPath=dirname($class->getFileName()).'/'.$assetPath;
		return $this->getService()->getAssetManager()->publishFilePath($assetPath);
	}

	/**
	 * Returns the id of the control.
	 * Control ID can be either manually set or automatically generated.
	 * If $hideAutoID is true, automatically generated ID will be returned as an empty string.
	 * @param boolean whether to hide automatically generated ID
	 * @return string the ID of the control
	 */
	public function getID($hideAutoID=true)
	{
		if($hideAutoID)
			return ($this->_flags & self::IS_ID_SET) ? $this->_id : '';
		else
			return $this->_id;
	}

	/**
	 * @param string the new control ID. The value must consist of word characters [a-zA-Z0-9_] only
	 * @throws TInvalidDataValueException if ID is in a bad format
	 */
	public function setID($id)
	{
		if(!preg_match(self::ID_FORMAT,$id))
			throw new TInvalidDataValueException('control_id_invalid',get_class($this),$id);
		$this->_id=$id;
		$this->_flags |= self::IS_ID_SET;
		$this->clearCachedUniqueID($this instanceof INamingContainer);
		if($this->_namingContainer)
			$this->_namingContainer->clearNameTable();
	}

	/**
	 * Returns a unique ID that identifies the control in the page hierarchy.
	 * A unique ID is the contenation of all naming container controls' IDs and the control ID.
	 * These IDs are separated by '$' character.
	 * Control users should not rely on the specific format of UniqueID, however.
	 * @return string a unique ID that identifies the control in the page hierarchy
	 */
	public function getUniqueID()
	{
		if($this->_uid==='')	// need to build the UniqueID
		{
			if($namingContainer=$this->getNamingContainer())
			{
				if($this->getPage()===$namingContainer)
					return ($this->_uid=$this->_id);
				else if(($prefix=$namingContainer->getUniqueID())==='')
					return $this->_id;
				else
					return ($this->_uid=$prefix.self::ID_SEPARATOR.$this->_id);
			}
			else	// no naming container
				return $this->_id;
		}
		else
			return $this->_uid;
	}

	/**
	 * Sets input focus to this control.
	 */
	public function focus()
	{
		$this->getPage()->setFocus($this);
	}

	/**
	 * Returns the client ID of the control.
	 * The client ID can be used to uniquely identify
	 * the control in client-side scripts (such as JavaScript).
	 * Do not rely on the explicit format of the return ID.
	 * @return string the client ID of the control
	 */
	public function getClientID()
	{
		return strtr($this->getUniqueID(),self::ID_SEPARATOR,self::CLIENT_ID_SEPARATOR);
	}

	/**
	 * @return string the skin ID of this control, '' if not set
	 */
	public function getSkinID()
	{
		return isset($this->_rf[self::RF_SKIN_ID])?$this->_rf[self::RF_SKIN_ID]:'';
	}

	/**
	 * @param string the skin ID of this control
	 * @throws TInvalidOperationException if the SkinID is set in a stage later than PreInit, or if the skin is applied already.
	 */
	public function setSkinID($value)
	{
		if(($this->_flags & self::IS_SKIN_APPLIED) || $this->_stage>=self::CS_CHILD_INITIALIZED)
			throw new TInvalidOperationException('control_skinid_unchangeable',get_class($this));
		else
			$this->_rf[self::RF_SKIN_ID]=$value;
	}

	/**
	 * @return boolean whether theming is enabled for this control.
	 * The theming is enabled if the control and all its parents have it enabled.
	 */
	public function getEnableTheming()
	{
		if($this->_flags & self::IS_DISABLE_THEMING)
			return false;
		else
			return $this->_parent?$this->_parent->getEnableTheming():true;
	}

	/**
	 * @param boolean whether to enable theming
	 * @throws TInvalidOperationException if this method is invoked after OnPreInit
	 */
	public function setEnableTheming($value)
	{
		if($this->_stage>=self::CS_CHILD_INITIALIZED)
			throw new TInvalidOperationException('control_enabletheming_unchangeable',get_class($this),$this->getUniqueID());
		else if(TPropertyValue::ensureBoolean($value))
			$this->_flags &= ~self::IS_DISABLE_THEMING;
		else
			$this->_flags |= self::IS_DISABLE_THEMING;
	}

	/**
	 * @return boolean whether the control has child controls
	 */
	public function getHasControls()
	{
		return isset($this->_rf[self::RF_CONTROLS]) && $this->_rf[self::RF_CONTROLS]->getCount()>0;
	}

	/**
	 * @return TControlList the child control collection
	 */
	public function getControls()
	{
		if(!isset($this->_rf[self::RF_CONTROLS]))
			$this->_rf[self::RF_CONTROLS]=new TControlList($this);
		return $this->_rf[self::RF_CONTROLS];
	}

	/**
	 * Checks if a control is visible.
	 * If parent check is required, then a control is visible only if the control
	 * and all its ancestors are visible.
	 * @param boolean whether the parents should also be checked if visible
	 * @return boolean whether the control is visible (default=true).
	 */
	public function getVisible($checkParents=true)
	{
		if($checkParents)
		{
			for($control=$this;$control;$control=$control->_parent)
				if(!$control->getViewState('Visible',true))
					return false;
			return true;
		}
		else
			return $this->getViewState('Visible',true);
	}

	/**
	 * @param boolean whether the control is visible
	 */
	public function setVisible($value)
	{
		$this->setViewState('Visible',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * Returns a value indicating whether the control is enabled.
	 * A control is enabled if it allows client user interaction.
	 * If $checkParents is true, all parent controls will be checked,
	 * and unless they are all enabled, false will be returned.
	 * The property Enabled is mainly used for {@link TWebControl}
	 * derived controls.
	 * @param boolean whether the parents should also be checked enabled
	 * @return boolean whether the control is enabled.
	 */
	public function getEnabled($checkParents=false)
	{
		if($checkParents)
		{
			for($control=$this;$control;$control=$control->_parent)
				if(!$control->getViewState('Enabled',true))
					return false;
			return true;
		}
		else
			return $this->getViewState('Enabled',true);
	}

	/**
	 * @param boolean whether the control is to be enabled.
	 */
	public function setEnabled($value)
	{
		$this->setViewState('Enabled',TPropertyValue::ensureBoolean($value),true);
	}

	/**
	 * @return boolean whether the control has custom attributes
	 */
	public function getHasAttributes()
	{
		if($attributes=$this->getViewState('Attributes',null))
			return $attributes->getCount()>0;
		else
			return false;
	}

	/**
	 * Returns the list of custom attributes.
	 * Custom attributes are name-value pairs that may be rendered
	 * as HTML tags' attributes.
	 * @return TMap the list of custom attributes
	 */
	public function getAttributes()
	{
		if($attributes=$this->getViewState('Attributes',null))
			return $attributes;
		else
		{
			$attributes=new TMap;
			$this->setViewState('Attributes',$attributes,null);
			return $attributes;
		}
	}

	/**
	 * @return boolean whether the named attribute exists
	 */
	public function hasAttribute($name)
	{
		if($attributes=$this->getViewState('Attributes',null))
			return $attributes->contains($name);
		else
			return false;
	}

	/**
	 * @return string attribute value, '' if attribute does not exist
	 */
	public function getAttribute($name)
	{
		if($attributes=$this->getViewState('Attributes',null))
			return $attributes->itemAt($name);
		else
			return '';
	}

	/**
	 * @param string attribute name
	 * @param string value of the attribute
	 */
	public function setAttribute($name,$value)
	{
		$this->getAttributes()->add($name,$value);
	}

	/**
	 * Removes the named attribute.
	 * @param string the name of the attribute to be removed.
	 * @return string attribute value removed, empty string if attribute does not exist.
	 */
	public function removeAttribute($name)
	{
		if($attributes=$this->getViewState('Attributes',null))
			return $attributes->remove($name);
		else
			return '';
	}

	/**
	 * Returns a value indicating whether this control type can take attributes in template.
	 * This method can be overriden.
	 * Only framework developers and control developers should use this method.
	 * @return boolean whether the control allows attributes in template (default=true)
	 */
	public function getAllowCustomAttributes()
	{
		return true;
	}

	/**
	 * @return boolean whether viewstate is enabled
	 */
	public function getEnableViewState($checkParents=false)
	{
		if($checkParents)
		{
			for($control=$this;$control!==null;$control=$control->getParent())
				if($control->_flags & self::IS_DISABLE_VIEWSTATE)
					return false;
			return true;
		}
		else
			return !($this->_flags & self::IS_DISABLE_VIEWSTATE);
	}

	/**
	 * @param boolean set whether to enable viewstate
	 */
	public function setEnableViewState($value)
	{
		if(TPropertyValue::ensureBoolean($value))
			$this->_flags &= ~self::IS_DISABLE_VIEWSTATE;
		else
			$this->_flags |= self::IS_DISABLE_VIEWSTATE;
	}

	/**
	 * Returns a controlstate value.
	 *
	 * This function is mainly used in defining getter functions for control properties
	 * that must be kept in controlstate.
	 * @param string the name of the controlstate value to be returned
	 * @param mixed the default value. If $key is not found in controlstate, $defaultValue will be returned
	 * @return mixed the controlstate value corresponding to $key
	 */
	protected function getControlState($key,$defaultValue=null)
	{
		return isset($this->_rf[self::RF_CONTROLSTATE][$key])?$this->_rf[self::RF_CONTROLSTATE][$key]:$defaultValue;
	}

	/**
	 * Sets a controlstate value.
	 *
	 * This function is very useful in defining setter functions for control properties
	 * that must be kept in controlstate.
	 * Make sure that the controlstate value must be serializable and unserializable.
	 * @param string the name of the controlstate value
	 * @param mixed the controlstate value to be set
	 * @param mixed default value. If $value===$defaultValue, the item will be cleared from controlstate
	 */
	protected function setControlState($key,$value,$defaultValue=null)
	{
		if($value===$defaultValue)
			unset($this->_rf[self::RF_CONTROLSTATE][$key]);
		else
			$this->_rf[self::RF_CONTROLSTATE][$key]=$value;
	}

	/**
	 * Clears a controlstate value.
	 * @param string the name of the controlstate value to be cleared
	 */
	protected function clearControlState($key)
	{
		unset($this->_rf[self::RF_CONTROLSTATE][$key]);
	}

	/**
	 * Returns a viewstate value.
	 *
	 * This function is very useful in defining getter functions for component properties
	 * that must be kept in viewstate.
	 * @param string the name of the viewstate value to be returned
	 * @param mixed the default value. If $key is not found in viewstate, $defaultValue will be returned
	 * @return mixed the viewstate value corresponding to $key
	 */
	protected function getViewState($key,$defaultValue=null)
	{
		return isset($this->_viewState[$key])?$this->_viewState[$key]:$defaultValue;
	}

	/**
	 * Sets a viewstate value.
	 *
	 * This function is very useful in defining setter functions for control properties
	 * that must be kept in viewstate.
	 * Make sure that the viewstate value must be serializable and unserializable.
	 * @param string the name of the viewstate value
	 * @param mixed the viewstate value to be set
	 * @param mixed default value. If $value===$defaultValue, the item will be cleared from the viewstate.
	 */
	protected function setViewState($key,$value,$defaultValue=null)
	{
		if($value===$defaultValue)
			unset($this->_viewState[$key]);
		else
			$this->_viewState[$key]=$value;
	}

	/**
	 * Clears a viewstate value.
	 * @param string the name of the viewstate value to be cleared
	 */
	protected function clearViewState($key)
	{
		unset($this->_viewState[$key]);
	}

	/**
	 * Sets up the binding between a property (or property path) and an expression.
	 * The context of the expression is the control itself.
	 * @param string the property name, or property path
	 * @param string the expression
	 */
	public function bindProperty($name,$expression)
	{
		$this->_rf[self::RF_DATA_BINDINGS][$name]=$expression;
	}

	/**
	 * Breaks the binding between a property (or property path) and an expression.
	 * @param string the property name (or property path)
	 */
	public function unbindProperty($name)
	{
		unset($this->_rf[self::RF_DATA_BINDINGS][$name]);
	}

	/**
	 * Performs the databinding for this control.
	 */
	public function dataBind()
	{
		$this->dataBindProperties();
		$this->onDataBinding(null);
		$this->dataBindChildren();
	}

	/**
	 * Databinding properties of the control.
	 */
	protected function dataBindProperties()
	{
		if(isset($this->_rf[self::RF_DATA_BINDINGS]))
		{
			foreach($this->_rf[self::RF_DATA_BINDINGS] as $property=>$expression)
				$this->setSubProperty($property,$this->evaluateExpression($expression));
		}
	}

	/**
	 * Databinding child controls.
	 */
	protected function dataBindChildren()
	{
		if(isset($this->_rf[self::RF_CONTROLS]))
		{
			foreach($this->_rf[self::RF_CONTROLS] as $control)
				if($control instanceof TControl)
					$control->dataBind();
		}
	}

	/**
	 * @return boolean whether child controls have been created
	 */
	final protected function getChildControlsCreated()
	{
		return ($this->_flags & self::IS_CHILD_CREATED)!==0;
	}

	/**
	 * Sets a value indicating whether child controls are created.
	 * If false, any existing child controls will be cleared up.
	 * @param boolean whether child controls are created
	 */
	final protected function setChildControlsCreated($value)
	{
		if($value)
			$this->_flags |= self::IS_CHILD_CREATED;
		else
		{
			if($this->hasControl() && ($this->_flags & self::IS_CHILD_CREATED))
				$this->getControls()->clear();
			$this->_flags &= ~self::IS_CHILD_CREATED;
		}
	}

	/**
	 * Ensures child controls are created.
	 * If child controls are not created yet, this method will invoke
	 * {@link createChildControls} to create them.
	 */
	public function ensureChildControls()
	{
		if(!($this->_flags & self::IS_CHILD_CREATED) && !($this->_flags & self::IS_CREATING_CHILD))
		{
			try
			{
				$this->_flags |= self::IS_CREATING_CHILD;
				$this->createChildControls();
				$this->_flags &= ~self::IS_CREATING_CHILD;
				$this->_flags |= self::IS_CHILD_CREATED;
			}
			catch(Exception $e)
			{
				$this->_flags &= ~self::IS_CREATING_CHILD;
				$this->_flags |= self::IS_CHILD_CREATED;
				throw $e;
			}
		}
	}

	/**
	 * Creates child controls.
	 * This method can be overriden for controls who want to have their controls.
	 * Do not call this method directly. Instead, call {@link ensureChildControls}
	 * to ensure child controls are created only once.
	 */
	protected function createChildControls()
	{
	}

	/**
	 * Finds a control by ID path within the current naming container.
	 * The current naming container is either the control itself
	 * if it implements {@link INamingContainer} or the control's naming container.
	 * The ID path is an ID sequence separated by {@link TControl::ID_SEPARATOR}.
	 * For example, 'Repeater1.Item1.Button1' looks for a control with ID 'Button1'
	 * whose naming container is 'Item1' whose naming container is 'Repeater1'.
	 * @param string ID of the control to be looked up
	 * @return TControl|null the control found, null if not found
	 * @throws TInvalidDataValueException if a control's ID is found not unique within its naming container.
	 */
	public function findControl($id)
	{
		$id=strtr($id,'.',self::ID_SEPARATOR);
		$container=($this instanceof INamingContainer)?$this:$this->getNamingContainer();
		if(!$container || !$container->getHasControls())
			return null;
		if(!isset($container->_rf[self::RF_NAMED_CONTROLS]))
		{
			$container->_rf[self::RF_NAMED_CONTROLS]=array();
			$container->fillNameTable($container,$container->_rf[self::RF_CONTROLS]);
		}
		if(($pos=strpos($id,self::ID_SEPARATOR))===false)
			return isset($container->_rf[self::RF_NAMED_CONTROLS][$id])?$container->_rf[self::RF_NAMED_CONTROLS][$id]:null;
		else
		{
			$cid=substr($id,0,$pos);
			$sid=substr($id,$pos+1);
			if(isset($container->_rf[self::RF_NAMED_CONTROLS][$cid]))
				return $container->_rf[self::RF_NAMED_CONTROLS][$cid]->findControl($sid);
			else
				return null;
		}
	}

	/**
	 * Resets the control as a naming container.
	 * Only framework developers should use this method.
	 */
	public function clearNamingContainer()
	{
		unset($this->_rf[self::RF_NAMED_CONTROLS_ID]);
		$this->clearNameTable();
	}

	/**
	 * Registers an object by a name.
	 * A registered object can be accessed like a public member variable.
	 * This method should only be used by framework and control developers.
	 * @param string name of the object
	 * @param object object to be declared
	 * @see __get
	 */
	public function registerObject($name,$object)
	{
		$this->_rf[self::RF_NAMED_OBJECTS][$name]=$object;
	}

	/**
	 * Unregisters an object by name.
	 * @param string name of the object
	 * @see registerObject
	 */
	public function unregisterObject($name)
	{
		unset($this->_rf[self::RF_NAMED_OBJECTS][$name]);
	}

	/**
	 * @return boolean whether an object has been registered with the name
	 * @see registerObject
	 */
	public function isObjectRegistered($name)
	{
		return isset($this->_rf[self::RF_NAMED_OBJECTS][$name]);
	}

	/**
	 * This method is invoked after the control is instantiated by a template.
	 * When this method is invoked, the control should have a valid TemplateControl
	 * and has its properties initialized according to template configurations.
	 * The control, however, has not been added to the page hierarchy yet.
	 * The default implementation of this method will invoke
	 * the potential parent control's {@link addParsedObject} to add the control as a child.
	 * This method can be overriden.
	 * @param TControl potential parent of this control
	 * @see addParsedObject
	 */
	public function createdOnTemplate($parent)
	{
		$parent->addParsedObject($this);
	}

	/**
	 * Processes an object that is created during parsing template.
	 * The object can be either a component or a static text string.
	 * By default, the object will be added into the child control collection.
	 * This method can be overriden to customize the handling of newly created objects in template.
	 * Only framework developers and control developers should use this method.
	 * @param string|TComponent text string or component parsed and instantiated in template
	 * @see createdOnTemplate
	 */
	public function addParsedObject($object)
	{
		$this->getControls()->add($object);
	}

	/**
	 * Clears up the child state data.
	 * After a control loads its state, those state that do not belong to
	 * any existing child controls are stored as child state.
	 * This method will remove these state.
	 * Only frameworker developers and control developers should use this method.
	 */
	final protected function clearChildState()
	{
		unset($this->_rf[self::RF_CHILD_STATE]);
	}

	/**
	 * @param TControl the potential ancestor control
	 * @return boolean if the control is a descendent (parent, parent of parent, etc.)
	 * of the specified control
	 */
	final protected function isDescendentOf($ancestor)
	{
		$control=$this;
		while($control!==$ancestor && $control->_parent)
			$control=$control->_parent;
		return $control===$ancestor;
	}

	/**
	 * Adds a control into the child collection of the control.
	 * Control lifecycles will be caught up during the addition.
	 * Only framework developers should use this method.
	 * @param TControl the new child control
	 */
	public function addedControl($control)
	{
		if($control->_parent)
			$control->_parent->getControls()->remove($control);
		$control->_parent=$this;
		$control->_page=$this->getPage();
		$namingContainer=($this instanceof INamingContainer)?$this:$this->_namingContainer;
		if($namingContainer)
		{
			$control->_namingContainer=$namingContainer;
			if($control->_id==='')
				$control->generateAutomaticID();
			else
				$namingContainer->clearNameTable();
		}

		if($this->_stage>=self::CS_INITIALIZED)
		{
			$control->initRecursive($namingContainer);
			if($this->_stage>=self::CS_STATE_LOADED)
			{
				if(isset($this->_rf[self::RF_CHILD_STATE]))
					$state=$this->_rf[self::RF_CHILD_STATE]->remove($control->_id);
				else
					$state=null;
				$control->loadStateRecursive($state,!($this->_flags & self::IS_DISABLE_VIEWSTATE));
				if($this->_stage>=self::CS_LOADED)
				{
					$control->loadRecursive();
					if($this->_stage>=self::CS_PRERENDERED)
						$control->preRenderRecursive();
				}
			}
		}
	}

	/**
	 * Removes a control from the child collection of the control.
	 * Only framework developers should use this method.
	 * @param TControl the child control removed
	 */
	public function removedControl($control)
	{
		if($this->_namingContainer)
			$this->_namingContainer->clearNameTable();
		$control->unloadRecursive();
		$control->_parent=null;
		$control->_page=null;
		$control->_namingContainer=null;
		$control->_tplControl=null;
		if(!($control->_flags & self::IS_ID_SET))
			$control->_id='';
		$control->clearCachedUniqueID(true);
	}

	/**
	 * Performs the Init step for the control and all its child controls.
	 * Only framework developers should use this method.
	 * @param TControl the naming container control
	 */
	protected function initRecursive($namingContainer=null)
	{
		$this->ensureChildControls();
		if($this->getHasControls())
		{
			if($this instanceof INamingContainer)
				$namingContainer=$this;
			$page=$this->getPage();
			foreach($this->_rf[self::RF_CONTROLS] as $control)
			{
				if($control instanceof TControl)
				{
					$control->_namingContainer=$namingContainer;
					$control->_page=$page;
					if($control->_id==='' && $namingContainer)
						$control->generateAutomaticID();
					$control->initRecursive($namingContainer);
				}
			}
		}
		if($this->_stage<self::CS_INITIALIZED)
		{
			$this->_stage=self::CS_CHILD_INITIALIZED;
			if(($page=$this->getPage()) && $this->getEnableTheming() && !($this->_flags & self::IS_SKIN_APPLIED))
			{
				$page->applyControlSkin($this);
				$this->_flags |= self::IS_SKIN_APPLIED;
			}
			$this->onInit(null);
			$this->_stage=self::CS_INITIALIZED;
		}
	}

	/**
	 * Performs the Load step for the control and all its child controls.
	 * Only framework developers should use this method.
	 */
	protected function loadRecursive()
	{
		if($this->_stage<self::CS_LOADED)
			$this->onLoad(null);
		if($this->getHasControls())
		{
			foreach($this->_rf[self::RF_CONTROLS] as $control)
				if($control instanceof TControl)
					$control->loadRecursive();
		}
		if($this->_stage<self::CS_LOADED)
			$this->_stage=self::CS_LOADED;
	}

	/**
	 * Performs the PreRender step for the control and all its child controls.
	 * Only framework developers should use this method.
	 */
	protected function preRenderRecursive()
	{
		if($this->getVisible(false))
		{
			$this->onPreRender(null);
			if($this->getHasControls())
			{
				foreach($this->_rf[self::RF_CONTROLS] as $control)
					if($control instanceof TControl)
						$control->preRenderRecursive();
			}
		}
		$this->_stage=self::CS_PRERENDERED;
	}

	/**
	 * Performs the Unload step for the control and all its child controls.
	 * Only framework developers should use this method.
	 */
	protected function unloadRecursive()
	{
		if(!($this->_flags & self::IS_ID_SET))
			$this->_id='';
		if($this->getHasControls())
		{
			foreach($this->_rf[self::RF_CONTROLS] as $control)
				if($control instanceof TControl)
					$control->unloadRecursive();
		}
		$this->onUnload(null);
	}

	/**
	 * This method is invoked when the control enters 'Init' stage.
	 * The method raises 'Init' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onInit($param)
	{
		$this->raiseEvent('Init',$this,$param);
	}

	/**
	 * This method is invoked when the control enters 'Load' stage.
	 * The method raises 'Load' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onLoad($param)
	{
		$this->raiseEvent('Load',$this,$param);
	}

	/**
	 * Raises 'DataBinding' event.
	 * This method is invoked when {@link dataBind} is invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onDataBinding($param)
	{
		$this->raiseEvent('DataBinding',$this,$param);
	}


	/**
	 * This method is invoked when the control enters 'Unload' stage.
	 * The method raises 'Unload' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onUnload($param)
	{
		$this->raiseEvent('Unload',$this,$param);
	}

	/**
	 * This method is invoked when the control enters 'PreRender' stage.
	 * The method raises 'PreRender' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter event parameter to be passed to the event handlers
	 */
	protected function onPreRender($param)
	{
		$this->raiseEvent('PreRender',$this,$param);
	}

	/**
	 * Invokes the parent's onBubbleEvent method.
	 * A control who wants to bubble an event must call this method in its onEvent method.
	 * @param TControl sender of the event
	 * @param TEventParameter event parameter
	 * @see onBubbleEvent
	 */
	protected function raiseBubbleEvent($sender,$param)
	{
		$control=$this;
		while($control=$control->_parent)
		{
			if($control->onBubbleEvent($sender,$param))
				break;
		}
	}

	/**
	 * This method responds to a bubbled event.
	 * This method should be overriden to provide customized response to a bubbled event.
	 * Check the type of event parameter to determine what event is bubbled currently.
	 * @param TControl sender of the event
	 * @param TEventParameter event parameters
	 * @return boolean true if the event bubbling is handled and no more bubbling.
	 * @see raiseBubbleEvent
	 */
	protected function onBubbleEvent($sender,$param)
	{
		return false;
	}

	/**
	 * Renders the control.
	 * Only when the control is visible will the control be rendered.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function renderControl($writer)
	{
		if($this->getVisible(false))
			$this->render($writer);
	}

	/**
	 * Renders the control.
	 * This method is invoked by {@link renderControl} when the control is visible.
	 * You can override this method to provide customized rendering of the control.
	 * By default, the control simply renders all its child contents.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function render($writer)
	{
		$this->renderChildren($writer);
	}

	/**
	 * Renders the children of the control.
	 * This method iterates through all child controls and static text strings
	 * and renders them in order.
	 * @param THtmlWriter the writer used for the rendering purpose
	 */
	protected function renderChildren($writer)
	{
		if($this->getHasControls())
		{
			foreach($this->_rf[self::RF_CONTROLS] as $control)
			{
				if($control instanceof TControl)
					$control->renderControl($writer);
				else if(is_string($control))
					$writer->write($control);
			}
		}
	}

	/**
	 * This method is invoked when control state is to be saved.
	 * You can override this method to do last step state saving.
	 * Parent implementation must be invoked.
	 * @param TEventParameter event parameter
	 */
	protected function onSaveState($param)
	{
		$this->raiseEvent('SaveState',$this,$param);
	}

	/**
	 * This method is invoked right after the control has loaded its state.
	 * You can override this method to initialize data from the control state.
	 * Parent implementation must be invoked.
	 * @param TEventParameter
	 */
	protected function onLoadState($param)
	{
		$this->raiseEvent('LoadState',$this,$param);
	}

	/**
	 * Loads state (viewstate and controlstate) into a control and its children.
	 * @param TMap the collection of the state
	 * @param boolean whether the viewstate should be loaded
	 */
	final protected function loadStateRecursive(&$state,$needViewState=true)
	{
		// A null state means the stateful properties all take default values.
		// So if the state is enabled, we have to assign the null value.
		$needViewState=($needViewState && !($this->_flags & self::IS_DISABLE_VIEWSTATE));
		if(is_array($state))
		{
			if(isset($state[1]))
			{
				$this->_rf[self::RF_CONTROLSTATE]=&$state[1];
				unset($state[1]);
			}
			else
				unset($this->_rf[self::RF_CONTROLSTATE]);
			if($needViewState)
			{
				if(isset($state[0]))
					$this->_viewState=&$state[0];
				else
					$this->_viewState=array();
			}
			unset($state[0]);
			if($this->getHasControls())
			{
				foreach($this->_rf[self::RF_CONTROLS] as $control)
				{
					if($control instanceof TControl)
					{
						if(isset($state[$control->_id]))
						{
							$s=&$state[$control->_id];
							unset($state[$control->_id]);
						}
						else
							$s=null;
						$control->loadStateRecursive($s,$needViewState);
					}
				}
			}
			if(!empty($state))
				$this->_rf[self::RF_CHILD_STATE]=&$state;
		}
		else
		{
			unset($this->_rf[self::RF_CONTROLSTATE]);
			if($needViewState)
				$this->_viewState=array();
			if($this->getHasControls())
			{
				foreach($this->_rf[self::RF_CONTROLS] as $control)
				{
					$s=null;
					if($control instanceof TControl)
						$control->loadStateRecursive($s,$needViewState);
				}
			}
		}
		$this->onLoadState(null);
		$this->_stage=self::CS_STATE_LOADED;
	}

	/**
	 * Saves the all control state (viewstate and controlstate) as a collection.
	 * @param boolean whether the viewstate should be saved
	 * @return TMap the collection of the control state (including its children's state).
	 */
	final protected function &saveStateRecursive($needViewState=true)
	{
		$this->onSaveState(null);
		$needViewState=($needViewState && !($this->_flags & self::IS_DISABLE_VIEWSTATE));
		$state=array();
		if($this->getHasControls())
		{
			foreach($this->_rf[self::RF_CONTROLS] as $control)
			{
				if($control instanceof TControl)
				{
					$cs=&$control->saveStateRecursive($needViewState);
					if(!empty($cs))
						$state[$control->_id]=&$cs;
				}
			}
		}
		if($needViewState && !empty($this->_viewState))
			$state[0]=&$this->_viewState;
		if(isset($this->_rf[self::RF_CONTROLSTATE]) && !empty($this->_rf[self::RF_CONTROLSTATE]))
			$state[1]=&$this->_rf[self::RF_CONTROLSTATE];
		return $state;
	}

	/**
	 * Applies a stylesheet skin to a control.
	 * @param TPage the page containing the control
	 * @throws TInvalidOperationException if the stylesheet skin is applied already
	 */
	public function applyStyleSheetSkin($page)
	{
		if($page && !($this->_flags & self::IS_STYLESHEET_APPLIED))
		{
			$page->applyControlStyleSheet($this);
			$this->_flags |= self::IS_STYLESHEET_APPLIED;
		}
		else if($this->_flags & self::IS_STYLESHEET_APPLIED)
			throw new TInvalidOperationException('control_stylesheet_applied',get_class($this));
	}

	/**
	 * Clears the cached UniqueID.
	 * If $recursive=true, all children's cached UniqueID will be cleared as well.
	 * @param boolean whether the clearing is recursive.
	 */
	private function clearCachedUniqueID($recursive)
	{
		$this->_uid='';
		if($recursive && isset($this->_rf[self::RF_CONTROLS]))
		{
			foreach($this->_rf[self::RF_CONTROLS] as $control)
				if($control instanceof TControl)
					$control->clearCachedUniqueID($recursive);
		}
	}

	/**
	 * Generates an automatic ID for the control.
	 */
	private function generateAutomaticID()
	{
		$this->_flags &= ~self::IS_ID_SET;
		if(!isset($this->_namingContainer->_rf[self::RF_NAMED_CONTROLS_ID]))
			$this->_namingContainer->_rf[self::RF_NAMED_CONTROLS_ID]=0;
		$id=$this->_namingContainer->_rf[self::RF_NAMED_CONTROLS_ID]++;
		$this->_id=self::AUTOMATIC_ID_PREFIX . $id;
		$this->_namingContainer->clearNameTable();
	}

	/**
	 * Clears the list of the controls whose IDs are managed by the specified naming container.
	 */
	private function clearNameTable()
	{
		unset($this->_rf[self::RF_NAMED_CONTROLS]);
	}

	/**
	 * Updates the list of the controls whose IDs are managed by the specified naming container.
	 * @param TControl the naming container
	 * @param TControlList list of controls
	 * @throws TInvalidDataValueException if a control's ID is not unique within its naming container.
	 */
	private function fillNameTable($container,$controls)
	{
		foreach($controls as $control)
		{
			if($control instanceof TControl)
			{
				if($control->_id!=='')
				{
					if(isset($container->_rf[self::RF_NAMED_CONTROLS][$control->_id]))
						throw new TInvalidDataValueException('control_id_nonunique',get_class($control),$control->_id);
					else
						$container->_rf[self::RF_NAMED_CONTROLS][$control->_id]=$control;
				}
				if(!($control instanceof INamingContainer) && $control->getHasControls())
					$this->fillNameTable($container,$control->_rf[self::RF_CONTROLS]);
			}
		}
	}
}


/**
 * TControlList class
 *
 * TControlList implements a collection that enables
 * controls to maintain a list of their child controls.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TControlList extends TList
{
	/**
	 * the control that owns this collection.
	 * @var TControl
	 */
	private $_o;

	/**
	 * Constructor.
	 * @param TControl the control that owns this collection.
	 */
	public function __construct(TControl $owner)
	{
		parent::__construct();
		$this->_o=$owner;
	}

	/**
	 * @return TControl the control that owns this collection.
	 */
	protected function getOwner()
	{
		return $this->_o;
	}

	/**
	 * Overrides the parent implementation with customized processing of the newly added item.
	 * @param mixed the newly added item
	 */
	protected function addedItem($item)
	{
		if($item instanceof TControl)
			$this->_o->addedControl($item);
	}

	/**
	 * Overrides the parent implementation with customized processing of the removed item.
	 * @param mixed the removed item
	 */
	protected function removedItem($item)
	{
		if($item instanceof TControl)
			$this->_o->removedControl($item);
	}

	/**
	 * Only string or instance of TControl can be added into collection.
	 * @param mixed the item to be added
	 */
	protected function canAddItem($item)
	{
		return is_string($item) || ($item instanceof TControl);
	}

	/**
	 * Overrides the parent implementation by invoking {@link TControl::clearNamingContainer}
	 */
	public function clear()
	{
		parent::clear();
		if($this->_o instanceof INamingContainer)
			$this->_o->clearNamingContainer();
	}
}

/**
 * INamingContainer interface.
 * INamingContainer marks a control as a naming container.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
interface INamingContainer
{
}

/**
 * IPostBackEventHandler interface
 *
 * If a control wants to respond to postback event, it must implement this interface.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
interface IPostBackEventHandler
{
	/**
	 * Raises postback event.
	 * The implementation of this function should raise appropriate event(s) (e.g. OnClick, OnCommand)
	 * indicating the component is responsible for the postback event.
	 * @param string the parameter associated with the postback event
	 */
	public function raisePostBackEvent($param);
}


/**
 * IPostBackDataHandler interface
 *
 * If a control wants to load post data, it must implement this interface.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
interface IPostBackDataHandler
{
	/**
	 * Loads user input data.
	 * The implementation of this function can use $values[$key] to get the user input
	 * data that are meant for the particular control.
	 * @param string the key that can be used to retrieve data from the input data collection
	 * @param array the input data collection
	 * @return boolean whether the data of the control has been changed
	 */
	public function loadPostData($key,$values);
	/**
	 * Raises postdata changed event.
	 * The implementation of this function should raise appropriate event(s) (e.g. OnTextChanged)
	 * indicating the control data is changed.
	 */
	public function raisePostDataChangedEvent();
}


/**
 * IValidator interface
 *
 * If a control wants to validate user input, it must implement this interface.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
interface IValidator
{
	/**
	 * Validates certain data.
	 * The implementation of this function should validate certain data
	 * (e.g. data entered into TTextBox control).
	 * @return boolean whether the data passes the validation
	 */
	public function validate();
	/**
	 * @return boolean whether the previous {@link validate()} is successful.
	 */
	public function getIsValid();
	/**
	 * @param boolean whether the validator validates successfully
	 */
	public function setIsValid($value);
	/**
	 * @return string error message during last validate
	 */
	public function getErrorMessage();
	/**
	 * @param string error message for the validation
	 */
	public function setErrorMessage($value);
}


/**
 * IValidatable interface
 *
 * If a control wants to be validated by a validator, it must implement this interface.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
interface IValidatable
{
	/**
	 * @return mixed the value of the property to be validated.
	 */
	public function getValidationPropertyValue();
}

/**
 * TCommandEventParameter class
 *
 * TCommandEventParameter encapsulates the parameter data for <b>OnCommand</b>
 * event of button controls. You can access the name of the command via
 * <b>Name</b> property, and the parameter carried with the command via
 * <b>Parameter</b> property.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System.Web.UI
 * @since 3.0
 */
class TCommandEventParameter extends TEventParameter
{
	private $_name;
	private $_param;

	/**
	 * Constructor.
	 * @param string name of the command
	 * @param string parameter of the command
	 */
	public function __construct($name='',$parameter='')
	{
		$this->_name=$name;
		$this->_param=$parameter;
	}

	/**
	 * @return string name of the command
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * @param string name of the command
	 */
	public function setName($value)
	{
		$this->_name=$value;
	}

	/**
	 * @return string parameter of the command
	 */
	public function getParameter()
	{
		return $this->_param;
	}

	/**
	 * @param string parameter of the command
	 */
	public function setParameter($value)
	{
		$this->_param=$value;
	}
}

?>