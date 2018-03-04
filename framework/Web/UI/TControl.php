<?php
/**
 * TControl, TControlCollection, TEventParameter and INamingContainer class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

use Exception;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Prado;
use Prado\TPropertyValue;
use Prado\Web\UI\ActiveControls\IActiveControl;
use Prado\Collections\TAttributeCollection;

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
 * @package Prado\Web\UI
 * @since 3.0
 */
class TControl extends \Prado\TApplicationComponent implements IRenderable, IBindable
{
	/**
	 * format of control ID
	 */
	const ID_FORMAT = '/^[a-zA-Z_]\\w*$/';
	/**
	 * separator char between IDs in a UniqueID
	 */
	const ID_SEPARATOR = '$';
	/**
	 * separator char between IDs in a ClientID
	 */
	const CLIENT_ID_SEPARATOR = '_';
	/**
	 * prefix to an ID automatically generated
	 */
	const AUTOMATIC_ID_PREFIX = 'ctl';

	/**
	 * the stage of lifecycles that the control is currently at
	 */
	const CS_CONSTRUCTED = 0;
	const CS_CHILD_INITIALIZED = 1;
	const CS_INITIALIZED = 2;
	const CS_STATE_LOADED = 3;
	const CS_LOADED = 4;
	const CS_PRERENDERED = 5;

	/**
	 * State bits.
	 */
	const IS_ID_SET = 0x01;
	const IS_DISABLE_VIEWSTATE = 0x02;
	const IS_SKIN_APPLIED = 0x04;
	const IS_STYLESHEET_APPLIED = 0x08;
	const IS_DISABLE_THEMING = 0x10;
	const IS_CHILD_CREATED = 0x20;
	const IS_CREATING_CHILD = 0x40;

	/**
	 * Indexes for the rare fields.
	 * In order to save memory, rare fields will only be created if they are needed.
	 */
	const RF_CONTROLS = 0;			// child controls
	const RF_CHILD_STATE = 1;			// child state field
	const RF_NAMED_CONTROLS = 2;		// list of controls whose namingcontainer is this control
	const RF_NAMED_CONTROLS_ID = 3;	// counter for automatic id
	const RF_SKIN_ID = 4;				// skin ID
	const RF_DATA_BINDINGS = 5;		// data bindings
	const RF_EVENTS = 6;				// event handlers
	const RF_CONTROLSTATE = 7;		// controlstate
	const RF_NAMED_OBJECTS = 8;		// controls declared with ID on template
	const RF_ADAPTER = 9;				// adapter
	const RF_AUTO_BINDINGS = 10;		// auto data bindings

	/**
	 * @var string control ID
	 */
	private $_id = '';
	/**
	 * @var string control unique ID
	 */
	private $_uid;
	/**
	 * @var TControl parent of the control
	 */
	private $_parent;
	/**
	 * @var TPage page that the control resides in
	 */
	private $_page;
	/**
	 * @var TControl naming container of the control
	 */
	private $_namingContainer;
	/**
	 * @var TTemplateControl control whose template contains the control
	 */
	private $_tplControl;
	/**
	 * @var array viewstate data
	 */
	private $_viewState = [];
	/**
	 * @var array temporary state (usually set in template)
	 */
	private $_tempState = [];
	/**
	 * @var boolean whether we should keep state in viewstate
	 */
	private $_trackViewState = true;
	/**
	 * @var integer the current stage of the control lifecycles
	 */
	private $_stage = 0;
	/**
	 * @var integer representation of the state bits
	 */
	private $_flags = 0;
	/**
	 * @var array a collection of rare control data
	 */
	private $_rf = [];

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
		if (isset($this->_rf[self::RF_NAMED_OBJECTS][$name])) {
			return $this->_rf[self::RF_NAMED_OBJECTS][$name];
		} else {
			return parent::__get($name);
		}
	}

	/**
	 * Checks for the existance of a property value by name or a control by ID.
	 * This overrides the parent implementation by allowing checking for the
	 * existance of a control via its ID using the following syntax,
	 * <code>
	 * $menuBarExists = isset($this->menuBar);
	 * </code>
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using isset() to detect if a component property is set or not.
	 * Note, the control must be configured in the template
	 * with explicit ID. If the name matches both a property and a control ID,
	 * the control ID will take the precedence.
	 *
	 * @param string the property name or control ID
	 * @return bool wether the control or property exists
	 * @see __get
	 */
	public function __isset($name)
	{
		if (isset($this->_rf[self::RF_NAMED_OBJECTS][$name])) {
			return true;
		} else {
			return parent::__isset($name);
		}
	}

	/**
	 * @return boolean whether there is an adapter for this control
	 */
	public function getHasAdapter()
	{
		return isset($this->_rf[self::RF_ADAPTER]);
	}

	/**
	 * @return TControlAdapter control adapter. Null if not exists.
	 */
	public function getAdapter()
	{
		return isset($this->_rf[self::RF_ADAPTER]) ? $this->_rf[self::RF_ADAPTER] : null;
	}

	/**
	 * @param TControlAdapter control adapter
	 */
	public function setAdapter(TControlAdapter $adapter)
	{
		$this->_rf[self::RF_ADAPTER] = $adapter;
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
		if (!$this->_namingContainer && $this->_parent) {
			if ($this->_parent instanceof INamingContainer) {
				$this->_namingContainer = $this->_parent;
			} else {
				$this->_namingContainer = $this->_parent->getNamingContainer();
			}
		}
		return $this->_namingContainer;
	}

	/**
	 * @return TPage the page that contains this control
	 */
	public function getPage()
	{
		if (!$this->_page) {
			if ($this->_parent) {
				$this->_page = $this->_parent->getPage();
			} elseif ($this->_tplControl) {
				$this->_page = $this->_tplControl->getPage();
			}
		}
		return $this->_page;
	}

	/**
	 * Sets the page for a control.
	 * Only framework developers should use this method.
	 * @param TPage $page the page that contains this control
	 */
	public function setPage($page)
	{
		$this->_page = $page;
	}

	/**
	 * Sets the control whose template contains this control.
	 * Only framework developers should use this method.
	 * @param TTemplateControl $control the control whose template contains this control
	 */
	public function setTemplateControl($control)
	{
		$this->_tplControl = $control;
	}

	/**
	 * @return TTemplateControl the control whose template contains this control
	 */
	public function getTemplateControl()
	{
		if (!$this->_tplControl && $this->_parent) {
			$this->_tplControl = $this->_parent->getTemplateControl();
		}
		return $this->_tplControl;
	}

	/**
	 * @return TTemplateControl the control whose template is loaded from
	 * some external storage, such as file, db, and whose template ultimately
	 * contains this control.
	 */
	public function getSourceTemplateControl()
	{
		$control = $this;
		while (($control instanceof TControl) && ($control = $control->getTemplateControl()) !== null) {
			if (($control instanceof TTemplateControl) && $control->getIsSourceTemplateControl()) {
				return $control;
			}
		}
		return $this->getPage();
	}

	/**
	 * Gets the lifecycle step the control is currently at.
	 * This method should only be used by control developers.
	 * @return integer the lifecycle step the control is currently at.
	 * The value can be CS_CONSTRUCTED, CS_CHILD_INITIALIZED, CS_INITIALIZED,
	 * CS_STATE_LOADED, CS_LOADED, CS_PRERENDERED.
	 */
	protected function getControlStage()
	{
		return $this->_stage;
	}

	/**
	 * Sets the lifecycle step the control is currently at.
	 * This method should only be used by control developers.
	 * @param integer the lifecycle step the control is currently at.
	 * Valid values include CS_CONSTRUCTED, CS_CHILD_INITIALIZED, CS_INITIALIZED,
	 * CS_STATE_LOADED, CS_LOADED, CS_PRERENDERED.
	 */
	protected function setControlStage($value)
	{
		$this->_stage = $value;
	}

	/**
	 * Returns the id of the control.
	 * Control ID can be either manually set or automatically generated.
	 * If $hideAutoID is true, automatically generated ID will be returned as an empty string.
	 * @param boolean whether to hide automatically generated ID
	 * @return string the ID of the control
	 */
	public function getID($hideAutoID = true)
	{
		if ($hideAutoID) {
			return ($this->_flags & self::IS_ID_SET) ? $this->_id : '';
		} else {
			return $this->_id;
		}
	}

	/**
	 * @param string $id the new control ID. The value must consist of word characters [a-zA-Z0-9_] only
	 * @throws TInvalidDataValueException if ID is in a bad format
	 */
	public function setID($id)
	{
		if (!preg_match(self::ID_FORMAT, $id)) {
			throw new TInvalidDataValueException('control_id_invalid', get_class($this), $id);
		}
		$this->_id = $id;
		$this->_flags |= self::IS_ID_SET;
		$this->clearCachedUniqueID($this instanceof INamingContainer);
		if ($this->_namingContainer) {
			$this->_namingContainer->clearNameTable();
		}
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
		if ($this->_uid === '' || $this->_uid === null) {	// need to build the UniqueID
			$this->_uid = '';  // set to not-null, so that clearCachedUniqueID() may take action
			if ($namingContainer = $this->getNamingContainer()) {
				if ($this->getPage() === $namingContainer) {
					return ($this->_uid = $this->_id);
				} elseif (($prefix = $namingContainer->getUniqueID()) === '') {
					return $this->_id;
				} else {
					return ($this->_uid = $prefix . self::ID_SEPARATOR . $this->_id);
				}
			} else {	// no naming container
				return $this->_id;
			}
		} else {
			return $this->_uid;
		}
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
		return strtr($this->getUniqueID(), self::ID_SEPARATOR, self::CLIENT_ID_SEPARATOR);
	}

	/**
	 * Converts a unique ID to a client ID.
	 * @param string $uniqueID the unique ID of a control
	 * @return string the client ID of the control
	 */
	public static function convertUniqueIdToClientId($uniqueID)
	{
		return strtr($uniqueID, self::ID_SEPARATOR, self::CLIENT_ID_SEPARATOR);
	}

	/**
	 * @return string the skin ID of this control, '' if not set
	 */
	public function getSkinID()
	{
		return isset($this->_rf[self::RF_SKIN_ID]) ? $this->_rf[self::RF_SKIN_ID] : '';
	}

	/**
	 * @param string $value the skin ID of this control
	 * @throws TInvalidOperationException if the SkinID is set in a stage later than PreInit, or if the skin is applied already.
	 */
	public function setSkinID($value)
	{
		if (($this->_flags & self::IS_SKIN_APPLIED) || $this->_stage >= self::CS_CHILD_INITIALIZED) {
			throw new TInvalidOperationException('control_skinid_unchangeable', get_class($this));
		} else {
			$this->_rf[self::RF_SKIN_ID] = $value;
		}
	}

	/**
	 * @param string the skin ID of this control
	 * @throws TInvalidOperationException if the SkinID is set in a stage later than PreInit, or if the skin is applied already.
	 */
	public function getIsSkinApplied()
	{
		return ($this->_flags & self::IS_SKIN_APPLIED);
	}

	/**
	 * @return boolean whether theming is enabled for this control.
	 * The theming is enabled if the control and all its parents have it enabled.
	 */
	public function getEnableTheming()
	{
		if ($this->_flags & self::IS_DISABLE_THEMING) {
			return false;
		} else {
			return $this->_parent ? $this->_parent->getEnableTheming() : true;
		}
	}

	/**
	 * @param boolean $value whether to enable theming
	 * @throws TInvalidOperationException if this method is invoked after OnPreInit
	 */
	public function setEnableTheming($value)
	{
		if ($this->_stage >= self::CS_CHILD_INITIALIZED) {
			throw new TInvalidOperationException('control_enabletheming_unchangeable', get_class($this), $this->getUniqueID());
		} elseif (TPropertyValue::ensureBoolean($value)) {
			$this->_flags &= ~self::IS_DISABLE_THEMING;
		} else {
			$this->_flags |= self::IS_DISABLE_THEMING;
		}
	}

	/**
	 * Returns custom data associated with this control.
	 * A control may be associated with some custom data for various purposes.
	 * For example, a button may be associated with a string to identify itself
	 * in a generic OnClick event handler.
	 * @return mixed custom data associated with this control. Defaults to null.
	 */
	public function getCustomData()
	{
		return $this->getViewState('CustomData', null);
	}

	/**
	 * Associates custom data with this control.
	 * Note, the custom data must be serializable and unserializable.
	 * @param mixed $value custom data
	 */
	public function setCustomData($value)
	{
		$this->setViewState('CustomData', $value, null);
	}

	/**
	 * @return boolean whether the control has child controls
	 */
	public function getHasControls()
	{
		return isset($this->_rf[self::RF_CONTROLS]) && $this->_rf[self::RF_CONTROLS]->getCount() > 0;
	}

	/**
	 * @return TControlCollection the child control collection
	 */
	public function getControls()
	{
		if (!isset($this->_rf[self::RF_CONTROLS])) {
			$this->_rf[self::RF_CONTROLS] = $this->createControlCollection();
		}
		return $this->_rf[self::RF_CONTROLS];
	}

	/**
	 * Creates a control collection object that is to be used to hold child controls
	 * @return TControlCollection control collection
	 * @see getControls
	 */
	protected function createControlCollection()
	{
		return $this->getAllowChildControls() ? new TControlCollection($this) : new TEmptyControlCollection($this);
	}

	/**
	 * Checks if a control is visible.
	 * If parent check is required, then a control is visible only if the control
	 * and all its ancestors are visible.
	 * @param boolean whether the parents should also be checked if visible
	 * @return boolean whether the control is visible (default=true).
	 */
	public function getVisible($checkParents = true)
	{
		if ($checkParents) {
			for ($control = $this;$control;$control = $control->_parent) {
				if (!$control->getVisible(false)) {
					return false;
				}
			}
			return true;
		} else {
			return $this->getViewState('Visible', true);
		}
	}

	/**
	 * @param boolean $value whether the control is visible
	 */
	public function setVisible($value)
	{
		$this->setViewState('Visible', TPropertyValue::ensureBoolean($value), true);
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
	public function getEnabled($checkParents = false)
	{
		if ($checkParents) {
			for ($control = $this;$control;$control = $control->_parent) {
				if (!$control->getViewState('Enabled', true)) {
					return false;
				}
			}
			return true;
		} else {
			return $this->getViewState('Enabled', true);
		}
	}

	/**
	 * @param boolean $value whether the control is to be enabled.
	 */
	public function setEnabled($value)
	{
		$this->setViewState('Enabled', TPropertyValue::ensureBoolean($value), true);
	}

	/**
	 * @return boolean whether the control has custom attributes
	 */
	public function getHasAttributes()
	{
		if ($attributes = $this->getViewState('Attributes', null)) {
			return $attributes->getCount() > 0;
		} else {
			return false;
		}
	}

	/**
	 * Returns the list of custom attributes.
	 * Custom attributes are name-value pairs that may be rendered
	 * as HTML tags' attributes.
	 * @return TAttributeCollection the list of custom attributes
	 */
	public function getAttributes()
	{
		if ($attributes = $this->getViewState('Attributes', null)) {
			return $attributes;
		} else {
			$attributes = new TAttributeCollection;
			$this->setViewState('Attributes', $attributes, null);
			return $attributes;
		}
	}

	/**
	 * @return boolean whether the named attribute exists
	 */
	public function hasAttribute($name)
	{
		if ($attributes = $this->getViewState('Attributes', null)) {
			return $attributes->contains($name);
		} else {
			return false;
		}
	}

	/**
	 * @return string attribute value, null if attribute does not exist
	 */
	public function getAttribute($name)
	{
		if ($attributes = $this->getViewState('Attributes', null)) {
			return $attributes->itemAt($name);
		} else {
			return null;
		}
	}

	/**
	 * Sets a custom control attribute.
	 * @param string attribute name
	 * @param string value of the attribute
	 */
	public function setAttribute($name, $value)
	{
		$this->getAttributes()->add($name, $value);
	}

	/**
	 * Removes the named attribute.
	 * @param string $name the name of the attribute to be removed.
	 * @return string attribute value removed, null if attribute does not exist.
	 */
	public function removeAttribute($name)
	{
		if ($attributes = $this->getViewState('Attributes', null)) {
			return $attributes->remove($name);
		} else {
			return null;
		}
	}

	/**
	 * @return boolean whether viewstate is enabled
	 */
	public function getEnableViewState($checkParents = false)
	{
		if ($checkParents) {
			for ($control = $this;$control !== null;$control = $control->getParent()) {
				if ($control->_flags & self::IS_DISABLE_VIEWSTATE) {
					return false;
				}
			}
			return true;
		} else {
			return !($this->_flags & self::IS_DISABLE_VIEWSTATE);
		}
	}

	/**
	 * @param boolean $value set whether to enable viewstate
	 */
	public function setEnableViewState($value)
	{
		if (TPropertyValue::ensureBoolean($value)) {
			$this->_flags &= ~self::IS_DISABLE_VIEWSTATE;
		} else {
			$this->_flags |= self::IS_DISABLE_VIEWSTATE;
		}
	}

	/**
	 * Returns a controlstate value.
	 *
	 * This function is mainly used in defining getter functions for control properties
	 * that must be kept in controlstate.
	 * @param string $key the name of the controlstate value to be returned
	 * @param mixed $defaultValue the default value. If $key is not found in controlstate, $defaultValue will be returned
	 * @return mixed the controlstate value corresponding to $key
	 */
	protected function getControlState($key, $defaultValue = null)
	{
		return isset($this->_rf[self::RF_CONTROLSTATE][$key]) ? $this->_rf[self::RF_CONTROLSTATE][$key] : $defaultValue;
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
	protected function setControlState($key, $value, $defaultValue = null)
	{
		if ($value === $defaultValue) {
			unset($this->_rf[self::RF_CONTROLSTATE][$key]);
		} else {
			$this->_rf[self::RF_CONTROLSTATE][$key] = $value;
		}
	}

	/**
	 * Clears a controlstate value.
	 * @param string $key the name of the controlstate value to be cleared
	 */
	protected function clearControlState($key)
	{
		unset($this->_rf[self::RF_CONTROLSTATE][$key]);
	}

	/**
	 * Sets a value indicating whether we should keep data in viewstate.
	 * When it is false, data saved via setViewState() will not be persisted.
	 * By default, it is true, meaning data will be persisted across postbacks.
	 * @param boolean $enabled whether data should be persisted
	 */
	public function trackViewState($enabled)
	{
		$this->_trackViewState = TPropertyValue::ensureBoolean($enabled);
	}

	/**
	 * Returns a viewstate value.
	 *
	 * This function is very useful in defining getter functions for component properties
	 * that must be kept in viewstate.
	 * @param string $key the name of the viewstate value to be returned
	 * @param mixed $defaultValue the default value. If $key is not found in viewstate, $defaultValue will be returned
	 * @return mixed the viewstate value corresponding to $key
	 */
	public function getViewState($key, $defaultValue = null)
	{
		if (isset($this->_viewState[$key])) {
			return $this->_viewState[$key] !== null ? $this->_viewState[$key] : $defaultValue;
		} elseif (isset($this->_tempState[$key])) {
			if (is_object($this->_tempState[$key]) && $this->_trackViewState) {
				$this->_viewState[$key] = $this->_tempState[$key];
			}
			return $this->_tempState[$key];
		} else {
			return $defaultValue;
		}
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
	public function setViewState($key, $value, $defaultValue = null)
	{
		if ($this->_trackViewState) {
			unset($this->_tempState[$key]);
			$this->_viewState[$key] = $value;
		} else {
			unset($this->_viewState[$key]);
			if ($value === $defaultValue) {
				unset($this->_tempState[$key]);
			} else {
				$this->_tempState[$key] = $value;
			}
		}
	}

	/**
	 * Clears a viewstate value.
	 * @param string $key the name of the viewstate value to be cleared
	 */
	public function clearViewState($key)
	{
		unset($this->_viewState[$key]);
		unset($this->_tempState[$key]);
	}

	/**
	 * Sets up the binding between a property (or property path) and an expression.
	 * The context of the expression is the template control (or the control itself if it is a page).
	 * @param string the property name, or property path
	 * @param string the expression
	 */
	public function bindProperty($name, $expression)
	{
		$this->_rf[self::RF_DATA_BINDINGS][$name] = $expression;
	}

	/**
	 * Breaks the binding between a property (or property path) and an expression.
	 * @param string $name the property name (or property path)
	 */
	public function unbindProperty($name)
	{
		unset($this->_rf[self::RF_DATA_BINDINGS][$name]);
	}

	/**
	 * Sets up the binding between a property (or property path) and an expression.
	 * Unlike regular databinding, the expression bound by this method
	 * is automatically evaluated during {@link prerenderRecursive()}.
	 * The context of the expression is the template control (or the control itself if it is a page).
	 * @param string the property name, or property path
	 * @param string the expression
	 */
	public function autoBindProperty($name, $expression)
	{
		$this->_rf[self::RF_AUTO_BINDINGS][$name] = $expression;
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
		Prado::trace("Data bind properties", 'Prado\Web\UI\TControl');
		if (isset($this->_rf[self::RF_DATA_BINDINGS])) {
			if (($context = $this->getTemplateControl()) === null) {
				$context = $this;
			}
			foreach ($this->_rf[self::RF_DATA_BINDINGS] as $property => $expression) {
				$this->setSubProperty($property, $context->evaluateExpression($expression));
			}
		}
	}

	/**
	 * Auto databinding properties of the control.
	 */
	protected function autoDataBindProperties()
	{
		if (isset($this->_rf[self::RF_AUTO_BINDINGS])) {
			if (($context = $this->getTemplateControl()) === null) {
				$context = $this;
			}
			foreach ($this->_rf[self::RF_AUTO_BINDINGS] as $property => $expression) {
				$this->setSubProperty($property, $context->evaluateExpression($expression));
			}
		}
	}

	/**
	 * Databinding child controls.
	 */
	protected function dataBindChildren()
	{
		Prado::trace("dataBindChildren()", 'Prado\Web\UI\TControl');
		if (isset($this->_rf[self::RF_CONTROLS])) {
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if ($control instanceof IBindable) {
					$control->dataBind();
				}
			}
		}
	}

	/**
	 * @return boolean whether child controls have been created
	 */
	final protected function getChildControlsCreated()
	{
		return ($this->_flags & self::IS_CHILD_CREATED) !== 0;
	}

	/**
	 * Sets a value indicating whether child controls are created.
	 * If false, any existing child controls will be cleared up.
	 * @param boolean $value whether child controls are created
	 */
	final protected function setChildControlsCreated($value)
	{
		if ($value) {
			$this->_flags |= self::IS_CHILD_CREATED;
		} else {
			if ($this->getHasControls() && ($this->_flags & self::IS_CHILD_CREATED)) {
				$this->getControls()->clear();
			}
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
		if (!($this->_flags & self::IS_CHILD_CREATED) && !($this->_flags & self::IS_CREATING_CHILD)) {
			try {
				$this->_flags |= self::IS_CREATING_CHILD;
				if (isset($this->_rf[self::RF_ADAPTER])) {
					$this->_rf[self::RF_ADAPTER]->createChildControls();
				} else {
					$this->createChildControls();
				}
				$this->_flags &= ~self::IS_CREATING_CHILD;
				$this->_flags |= self::IS_CHILD_CREATED;
			} catch (Exception $e) {
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
	public function createChildControls()
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
		$id = strtr($id, '.', self::ID_SEPARATOR);
		$container = ($this instanceof INamingContainer) ? $this : $this->getNamingContainer();
		if (!$container || !$container->getHasControls()) {
			return null;
		}
		if (!isset($container->_rf[self::RF_NAMED_CONTROLS])) {
			$container->_rf[self::RF_NAMED_CONTROLS] = [];
			$container->fillNameTable($container, $container->_rf[self::RF_CONTROLS]);
		}
		if (($pos = strpos($id, self::ID_SEPARATOR)) === false) {
			return isset($container->_rf[self::RF_NAMED_CONTROLS][$id]) ? $container->_rf[self::RF_NAMED_CONTROLS][$id] : null;
		} else {
			$cid = substr($id, 0, $pos);
			$sid = substr($id, $pos + 1);
			if (isset($container->_rf[self::RF_NAMED_CONTROLS][$cid])) {
				return $container->_rf[self::RF_NAMED_CONTROLS][$cid]->findControl($sid);
			} else {
				return null;
			}
		}
	}

	/**
	 * Finds all child and grand-child controls that are of the specified type.
	 * @param string $type the class name
	 * @param boolean $strict whether the type comparison is strict or not. If false, controls of the parent classes of the specified class will also be returned.
	 * @return array list of controls found
	 */
	public function findControlsByType($type, $strict = true)
	{
		$controls = [];
		if ($this->getHasControls()) {
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if (is_object($control) && (get_class($control) === $type || (!$strict && ($control instanceof $type)))) {
					$controls[] = $control;
				}
				if (($control instanceof TControl) && $control->getHasControls()) {
					$controls = array_merge($controls, $control->findControlsByType($type, $strict));
				}
			}
		}
		return $controls;
	}

	/**
	 * Finds all child and grand-child controls with the specified ID.
	 * Note, this method is different from {@link findControl} in that
	 * it searches through all controls that have this control as the ancestor
	 * while {@link findcontrol} only searches through controls that have this
	 * control as the direct naming container.
	 * @param string $id the ID being looked for
	 * @return array list of controls found
	 */
	public function findControlsByID($id)
	{
		$controls = [];
		if ($this->getHasControls()) {
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if ($control instanceof TControl) {
					if ($control->_id === $id) {
						$controls[] = $control;
					}
					$controls = array_merge($controls, $control->findControlsByID($id));
				}
			}
		}
		return $controls;
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
	public function registerObject($name, $object)
	{
		if (isset($this->_rf[self::RF_NAMED_OBJECTS][$name])) {
			throw new TInvalidOperationException('control_object_reregistered', $name);
		}
		$this->_rf[self::RF_NAMED_OBJECTS][$name] = $object;
	}

	/**
	 * Unregisters an object by name.
	 * @param string $name name of the object
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
	 * @return boolean true if the child control has been initialized.
	 */
	public function getHasChildInitialized()
	{
		return $this->getControlStage() >= self::CS_CHILD_INITIALIZED;
	}

	/**
	 * @return boolean true if the onInit event has raised.
	 */
	public function getHasInitialized()
	{
		return $this->getControlStage() >= self::CS_INITIALIZED;
	}

	/**
	 * @return boolean true if the control has loaded post data.
	 */
	public function getHasLoadedPostData()
	{
		return $this->getControlStage() >= self::CS_STATE_LOADED;
	}

	/**
	 * @return boolean true if the onLoad event has raised.
	 */
	public function getHasLoaded()
	{
		return $this->getControlStage() >= self::CS_LOADED;
	}

	/**
	 * @return boolean true if onPreRender event has raised.
	 */
	public function getHasPreRendered()
	{
		return $this->getControlStage() >= self::CS_PRERENDERED;
	}

	/**
	 * Returns the named registered object.
	 * A component with explicit ID on a template will be registered to
	 * the template owner. This method allows you to obtain this component
	 * with the ID.
	 * @return mixed the named registered object. Null if object is not found.
	 */
	public function getRegisteredObject($name)
	{
		return isset($this->_rf[self::RF_NAMED_OBJECTS][$name]) ? $this->_rf[self::RF_NAMED_OBJECTS][$name] : null;
	}

	/**
	 * @return boolean whether body contents are allowed for this control. Defaults to true.
	 */
	public function getAllowChildControls()
	{
		return true;
	}

	/**
	 * Adds the object instantiated on a template to the child control collection.
	 * This method overrides the parent implementation.
	 * Only framework developers and control developers should use this method.
	 * @param string|TComponent $object text string or component parsed and instantiated in template
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
		$control = $this;
		while ($control !== $ancestor && $control->_parent) {
			$control = $control->_parent;
		}
		return $control === $ancestor;
	}

	/**
	 * Adds a control into the child collection of the control.
	 * Control lifecycles will be caught up during the addition.
	 * Only framework developers should use this method.
	 * @param TControl $control the new child control
	 */
	public function addedControl($control)
	{
		if ($control->_parent) {
			$control->_parent->getControls()->remove($control);
		}
		$control->_parent = $this;
		$control->_page = $this->getPage();
		$namingContainer = ($this instanceof INamingContainer) ? $this : $this->_namingContainer;
		if ($namingContainer) {
			$control->_namingContainer = $namingContainer;
			if ($control->_id === '') {
				$control->generateAutomaticID();
			} else {
				$namingContainer->clearNameTable();
			}
			$control->clearCachedUniqueID($control instanceof INamingContainer);
		}

		if ($this->_stage >= self::CS_CHILD_INITIALIZED) {
			$control->initRecursive($namingContainer);
			if ($this->_stage >= self::CS_STATE_LOADED) {
				if (isset($this->_rf[self::RF_CHILD_STATE][$control->_id])) {
					$state = $this->_rf[self::RF_CHILD_STATE][$control->_id];
					unset($this->_rf[self::RF_CHILD_STATE][$control->_id]);
				} else {
					$state = null;
				}
				$control->loadStateRecursive($state, !($this->_flags & self::IS_DISABLE_VIEWSTATE));
				if ($this->_stage >= self::CS_LOADED) {
					$control->loadRecursive();
					if ($this->_stage >= self::CS_PRERENDERED) {
						$control->preRenderRecursive();
					}
				}
			}
		}
	}

	/**
	 * Removes a control from the child collection of the control.
	 * Only framework developers should use this method.
	 * @param TControl $control the child control removed
	 */
	public function removedControl($control)
	{
		if ($this->_namingContainer) {
			$this->_namingContainer->clearNameTable();
		}
		$control->unloadRecursive();
		$control->_parent = null;
		$control->_page = null;
		$control->_namingContainer = null;
		$control->_tplControl = null;
		//$control->_stage=self::CS_CONSTRUCTED;
		if (!($control->_flags & self::IS_ID_SET)) {
			$control->_id = '';
		} else {
			unset($this->_rf[self::RF_NAMED_OBJECTS][$control->_id]);
		}
		$control->clearCachedUniqueID(true);
	}

	/**
	 * Performs the Init step for the control and all its child controls.
	 * Only framework developers should use this method.
	 * @param TControl $namingContainer the naming container control
	 */
	protected function initRecursive($namingContainer = null)
	{
		$this->ensureChildControls();
		if ($this->getHasControls()) {
			if ($this instanceof INamingContainer) {
				$namingContainer = $this;
			}
			$page = $this->getPage();
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if ($control instanceof TControl) {
					$control->_namingContainer = $namingContainer;
					$control->_page = $page;
					if ($control->_id === '' && $namingContainer) {
						$control->generateAutomaticID();
					}
					$control->initRecursive($namingContainer);
				}
			}
		}
		if ($this->_stage < self::CS_INITIALIZED) {
			$this->_stage = self::CS_CHILD_INITIALIZED;
			if (($page = $this->getPage()) && $this->getEnableTheming() && !($this->_flags & self::IS_SKIN_APPLIED)) {
				$page->applyControlSkin($this);
				$this->_flags |= self::IS_SKIN_APPLIED;
			}
			if (isset($this->_rf[self::RF_ADAPTER])) {
				$this->_rf[self::RF_ADAPTER]->onInit(null);
			} else {
				$this->onInit(null);
			}
			$this->_stage = self::CS_INITIALIZED;
		}
	}

	/**
	 * Performs the Load step for the control and all its child controls.
	 * Only framework developers should use this method.
	 */
	protected function loadRecursive()
	{
		if ($this->_stage < self::CS_LOADED) {
			if (isset($this->_rf[self::RF_ADAPTER])) {
				$this->_rf[self::RF_ADAPTER]->onLoad(null);
			} else {
				$this->onLoad(null);
			}
		}
		if ($this->getHasControls()) {
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if ($control instanceof TControl) {
					$control->loadRecursive();
				}
			}
		}
		if ($this->_stage < self::CS_LOADED) {
			$this->_stage = self::CS_LOADED;
		}
	}

	/**
	 * Performs the PreRender step for the control and all its child controls.
	 * Only framework developers should use this method.
	 */
	protected function preRenderRecursive()
	{
		$this->autoDataBindProperties();

		if ($this->getVisible(false)) {
			if (isset($this->_rf[self::RF_ADAPTER])) {
				$this->_rf[self::RF_ADAPTER]->onPreRender(null);
			} else {
				$this->onPreRender(null);
			}
			if ($this->getHasControls()) {
				foreach ($this->_rf[self::RF_CONTROLS] as $control) {
					if ($control instanceof TControl) {
						$control->preRenderRecursive();
					} elseif ($control instanceof TCompositeLiteral) {
						$control->evaluateDynamicContent();
					}
				}
			}
		}
		$this->_stage = self::CS_PRERENDERED;
	}

	/**
	 * Performs the Unload step for the control and all its child controls.
	 * Only framework developers should use this method.
	 */
	protected function unloadRecursive()
	{
		if (!($this->_flags & self::IS_ID_SET)) {
			$this->_id = '';
		}
		if ($this->getHasControls()) {
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if ($control instanceof TControl) {
					$control->unloadRecursive();
				}
			}
		}
		if (isset($this->_rf[self::RF_ADAPTER])) {
			$this->_rf[self::RF_ADAPTER]->onUnload(null);
		} else {
			$this->onUnload(null);
		}
	}

	/**
	 * This method is invoked when the control enters 'OnInit' stage.
	 * The method raises 'OnInit' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onInit($param)
	{
		$this->raiseEvent('OnInit', $this, $param);
	}

	/**
	 * This method is invoked when the control enters 'OnLoad' stage.
	 * The method raises 'OnLoad' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onLoad($param)
	{
		$this->raiseEvent('OnLoad', $this, $param);
	}

	/**
	 * Raises 'OnDataBinding' event.
	 * This method is invoked when {@link dataBind} is invoked.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onDataBinding($param)
	{
		Prado::trace("onDataBinding()", 'Prado\Web\UI\TControl');
		$this->raiseEvent('OnDataBinding', $this, $param);
	}


	/**
	 * This method is invoked when the control enters 'OnUnload' stage.
	 * The method raises 'OnUnload' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onUnload($param)
	{
		$this->raiseEvent('OnUnload', $this, $param);
	}

	/**
	 * This method is invoked when the control enters 'OnPreRender' stage.
	 * The method raises 'OnPreRender' event.
	 * If you override this method, be sure to call the parent implementation
	 * so that the event handlers can be invoked.
	 * @param TEventParameter $param event parameter to be passed to the event handlers
	 */
	public function onPreRender($param)
	{
		$this->raiseEvent('OnPreRender', $this, $param);
	}

	/**
	 * Invokes the parent's bubbleEvent method.
	 * A control who wants to bubble an event must call this method in its onEvent method.
	 * @param TControl sender of the event
	 * @param TEventParameter event parameter
	 * @see bubbleEvent
	 */
	protected function raiseBubbleEvent($sender, $param)
	{
		$control = $this;
		while ($control = $control->_parent) {
			if ($control->bubbleEvent($sender, $param)) {
				break;
			}
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
	public function bubbleEvent($sender, $param)
	{
		return false;
	}

	/**
	 * Broadcasts an event.
	 * The event will be sent to all controls on the current page hierarchy.
	 * If a control defines the event, the event will be raised for the control.
	 * If a control implements {@link IBroadcastEventReceiver}, its
	 * {@link IBroadcastEventReceiver::broadcastEventReceived broadcastEventReceived()} method will
	 * be invoked which gives the control a chance to respond to the event.
	 * For example, when broadcasting event 'OnClick', all controls having 'OnClick'
	 * event will have this event raised, and all controls implementing
	 * {@link IBroadcastEventReceiver} will also have its
	 * {@link IBroadcastEventReceiver::broadcastEventReceived broadcastEventReceived()}
	 * invoked.
	 * @param string name of the broadcast event
	 * @param TControl sender of this event
	 * @param TEventParameter event parameter
	 */
	public function broadcastEvent($name, $sender, $param)
	{
		$rootControl = (($page = $this->getPage()) === null) ? $this : $page;
		$rootControl->broadcastEventInternal($name, $sender, new TBroadcastEventParameter($name, $param));
	}

	/**
	 * Recursively broadcasts an event.
	 * This method should only be used by framework developers.
	 * @param string name of the broadcast event
	 * @param TControl sender of the event
	 * @param TBroadcastEventParameter event parameter
	 */
	private function broadcastEventInternal($name, $sender, $param)
	{
		if ($this->hasEvent($name)) {
			$this->raiseEvent($name, $sender, $param->getParameter());
		}
		if ($this instanceof IBroadcastEventReceiver) {
			$this->broadcastEventReceived($sender, $param);
		}
		if ($this->getHasControls()) {
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if ($control instanceof TControl) {
					$control->broadcastEventInternal($name, $sender, $param);
				}
			}
		}
	}

	/**
	 * Traverse the whole control hierarchy rooted at this control.
	 * Callback function may be invoked for each control being visited.
	 * A pre-callback is invoked before traversing child controls;
	 * A post-callback is invoked after traversing child controls.
	 * Callback functions can be global functions or class methods.
	 * They must be of the following signature:
	 * <code>
	 * function callback_func($control,$param) {...}
	 * </code>
	 * where $control refers to the control being visited and $param
	 * is the parameter that is passed originally when calling this traverse function.
	 *
	 * @param mixed parameter to be passed to callbacks for each control
	 * @param callback callback invoked before traversing child controls. If null, it is ignored.
	 * @param callback callback invoked after traversing child controls. If null, it is ignored.
	 */
	protected function traverseChildControls($param, $preCallback = null, $postCallback = null)
	{
		if ($preCallback !== null) {
			call_user_func($preCallback, $this, $param);
		}
		if ($this->getHasControls()) {
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if ($control instanceof TControl) {
					$control->traverseChildControls($param, $preCallback, $postCallback);
				}
			}
		}
		if ($postCallback !== null) {
			call_user_func($postCallback, $this, $param);
		}
	}

	/**
	 * Renders the control.
	 * Only when the control is visible will the control be rendered.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderControl($writer)
	{
		if ($this instanceof IActiveControl || $this->getVisible(false)) {
			if (isset($this->_rf[self::RF_ADAPTER])) {
				$this->_rf[self::RF_ADAPTER]->render($writer);
			} else {
				$this->render($writer);
			}
		}
	}

	/**
	 * Renders the control.
	 * This method is invoked by {@link renderControl} when the control is visible.
	 * You can override this method to provide customized rendering of the control.
	 * By default, the control simply renders all its child contents.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function render($writer)
	{
		$this->renderChildren($writer);
	}

	/**
	 * Renders the children of the control.
	 * This method iterates through all child controls and static text strings
	 * and renders them in order.
	 * @param THtmlWriter $writer the writer used for the rendering purpose
	 */
	public function renderChildren($writer)
	{
		if ($this->getHasControls()) {
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if (is_string($control)) {
					$writer->write($control);
				} elseif ($control instanceof TControl) {
					$control->renderControl($writer);
				} elseif ($control instanceof IRenderable) {
					$control->render($writer);
				}
			}
		}
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
	 * Loads state (viewstate and controlstate) into a control and its children.
	 * This method should only be used by framework developers.
	 * @param array the collection of the state
	 * @param boolean whether the viewstate should be loaded
	 */
	protected function loadStateRecursive(&$state, $needViewState = true)
	{
		if (is_array($state)) {
			// A null state means the stateful properties all take default values.
			// So if the state is enabled, we have to assign the null value.
			$needViewState = ($needViewState && !($this->_flags & self::IS_DISABLE_VIEWSTATE));
			if (isset($state[1])) {
				$this->_rf[self::RF_CONTROLSTATE] = &$state[1];
				unset($state[1]);
			} else {
				unset($this->_rf[self::RF_CONTROLSTATE]);
			}
			if ($needViewState) {
				if (isset($state[0])) {
					$this->_viewState = &$state[0];
				} else {
					$this->_viewState = [];
				}
			}
			unset($state[0]);
			if ($this->getHasControls()) {
				foreach ($this->_rf[self::RF_CONTROLS] as $control) {
					if ($control instanceof TControl) {
						if (isset($state[$control->_id])) {
							$control->loadStateRecursive($state[$control->_id], $needViewState);
							unset($state[$control->_id]);
						}
					}
				}
			}
			if (!empty($state)) {
				$this->_rf[self::RF_CHILD_STATE] = &$state;
			}
		}
		$this->_stage = self::CS_STATE_LOADED;
		if (isset($this->_rf[self::RF_ADAPTER])) {
			$this->_rf[self::RF_ADAPTER]->loadState();
		} else {
			$this->loadState();
		}
	}

	/**
	 * Saves all control state (viewstate and controlstate) as a collection.
	 * This method should only be used by framework developers.
	 * @param boolean whether the viewstate should be saved
	 * @return array the collection of the control state (including its children's state).
	 */
	protected function &saveStateRecursive($needViewState = true)
	{
		if (isset($this->_rf[self::RF_ADAPTER])) {
			$this->_rf[self::RF_ADAPTER]->saveState();
		} else {
			$this->saveState();
		}
		$needViewState = ($needViewState && !($this->_flags & self::IS_DISABLE_VIEWSTATE));
		$state = [];
		if ($this->getHasControls()) {
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if ($control instanceof TControl) {
					if (count($tmp = &$control->saveStateRecursive($needViewState))) {
						$state[$control->_id] = $tmp;
					}
				}
			}
		}
		if ($needViewState && !empty($this->_viewState)) {
			$state[0] = &$this->_viewState;
		}
		if (isset($this->_rf[self::RF_CONTROLSTATE])) {
			$state[1] = &$this->_rf[self::RF_CONTROLSTATE];
		}
		return $state;
	}

	/**
	 * Applies a stylesheet skin to a control.
	 * @param TPage $page the page containing the control
	 * @throws TInvalidOperationException if the stylesheet skin is applied already
	 */
	public function applyStyleSheetSkin($page)
	{
		if ($page && !($this->_flags & self::IS_STYLESHEET_APPLIED)) {
			$page->applyControlStyleSheet($this);
			$this->_flags |= self::IS_STYLESHEET_APPLIED;
		} elseif ($this->_flags & self::IS_STYLESHEET_APPLIED) {
			throw new TInvalidOperationException('control_stylesheet_applied', get_class($this));
		}
	}

	/**
	 * Clears the cached UniqueID.
	 * If $recursive=true, all children's cached UniqueID will be cleared as well.
	 * @param boolean $recursive whether the clearing is recursive.
	 */
	private function clearCachedUniqueID($recursive)
	{
		if ($recursive && $this->_uid !== null && isset($this->_rf[self::RF_CONTROLS])) {
			foreach ($this->_rf[self::RF_CONTROLS] as $control) {
				if ($control instanceof TControl) {
					$control->clearCachedUniqueID($recursive);
				}
			}
		}
		$this->_uid = null;
	}

	/**
	 * Generates an automatic ID for the control.
	 */
	private function generateAutomaticID()
	{
		$this->_flags &= ~self::IS_ID_SET;
		if (!isset($this->_namingContainer->_rf[self::RF_NAMED_CONTROLS_ID])) {
			$this->_namingContainer->_rf[self::RF_NAMED_CONTROLS_ID] = 0;
		}
		$id = $this->_namingContainer->_rf[self::RF_NAMED_CONTROLS_ID]++;
		$this->_id = self::AUTOMATIC_ID_PREFIX . $id;
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
	 * @param TControlCollection list of controls
	 * @throws TInvalidDataValueException if a control's ID is not unique within its naming container.
	 */
	private function fillNameTable($container, $controls)
	{
		foreach ($controls as $control) {
			if ($control instanceof TControl) {
				if ($control->_id !== '') {
					if (isset($container->_rf[self::RF_NAMED_CONTROLS][$control->_id])) {
						throw new TInvalidDataValueException('control_id_nonunique', get_class($control), $control->_id);
					} else {
						$container->_rf[self::RF_NAMED_CONTROLS][$control->_id] = $control;
					}
				}
				if (!($control instanceof INamingContainer) && $control->getHasControls()) {
					$this->fillNameTable($container, $control->_rf[self::RF_CONTROLS]);
				}
			}
		}
	}
}
