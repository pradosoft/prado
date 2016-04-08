<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/COPYRIGHT
 * @package System
 */

/**
 * TComponent class
 *
 * TComponent is the base class for all PRADO components.
 * TComponent implements the protocol of defining, using properties, behaviors,
 * and events.
 *
 * A property is defined by a getter method, and/or a setter method.
 * Properties can be accessed in the way like accessing normal object members.
 * Reading or writing a property will cause the invocation of the corresponding
 * getter or setter method, e.g.,
 * <code>
 * $a=$this->Text;     // equivalent to $a=$this->getText();
 * $this->Text='abc';  // equivalent to $this->setText('abc');
 * </code>
 * The signatures of getter and setter methods are as follows,
 * <code>
 * // getter, defines a readable property 'Text'
 * function getText() { ... }
 * // setter, defines a writable property 'Text', with $value being the value to be set to the property
 * function setText($value) { ... }
 * </code>
 * Property names are case-insensitive. It is recommended that they are written
 * in the format of concatenated words, with the first letter of each word
 * capitalized (e.g. DisplayMode, ItemStyle).
 *
 * Javascript Get and Set
 *
 * Since Prado 3.2 a new class of javascript-friendly properties have been introduced
 * to better deal with potential security problems like cross-site scripting issues.
 * All the data that gets sent clientside inside a javascript block is now encoded by default.
 * Sometimes there's the need to bypass this encoding and be able to send raw javascript code.
 * This new class of javascript-friendly properties are identified by their name
 * starting with 'js' (case insensitive):
 * <code>
 * // getter, defines a readable property 'Text'
 * function getJsText() { ... }
 * // setter, defines a writable property 'Text', with $value being the value to be set to the property
 * function setJsText(TJavaScriptLiteral $value) { ... }
 * </code>
 * Js-friendly properties can be accessed using both their Js-less name and their Js-enabled name:
 * <code>
 * // set some simple text as property value
 * $component->Text = 'text';
 * // set some javascript code as property value
 * $component->JsText = 'raw javascript';
 * </code>
 * In the first case, the property value will automatically gets encoded when sent
 * clientside inside a javascript block.
 * In the second case, the property will be 'marked' as being a safe javascript
 * statement and will not be encoded when rendered inside a javascript block.
 * This special handling makes use of the {@link TJavaScriptLiteral} class.
 *
 * Events
 *
 * An event is defined by the presence of a method whose name starts with 'on'.
 * The event name is the method name and is thus case-insensitive.
 * An event can be attached with one or several methods (called event handlers).
 * An event can be raised by calling {@link raiseEvent} method, upon which
 * the attached event handlers will be invoked automatically in the order they
 * are attached to the event. Event handlers must have the following signature,
 * <code>
 * function eventHandlerFuncName($sender,$param) { ... }
 * </code>
 * where $sender refers to the object who is responsible for the raising of the event,
 * and $param refers to a structure that may contain event-specific information.
 * To raise an event (assuming named as 'Click') of a component, use
 * <code>
 * $component->raiseEvent('OnClick');
 * $component->raiseEvent('OnClick', $this, $param);
 * </code>
 * To attach an event handler to an event, use one of the following ways,
 * <code>
 * $component->OnClick=$callback;  // or $component->OnClick->add($callback);
 * $component->attachEventHandler('OnClick',$callback);
 * </code>
 * The first two ways make use of the fact that $component->OnClick refers to
 * the event handler list {@link TPriorityList} for the 'OnClick' event.
 * The variable $callback contains the definition of the event handler that can
 * be either a string referring to a global function name, or an array whose
 * first element refers to an object and second element a method name/path that
 * is reachable by the object, e.g.
 * - 'buttonClicked' : buttonClicked($sender,$param);
 * - array($object,'buttonClicked') : $object->buttonClicked($sender,$param);
 * - array($object,'MainContent.SubmitButton.buttonClicked') :
 *   $object->MainContent->SubmitButton->buttonClicked($sender,$param);
 *
 * With the addition of behaviors, a more expansive event model is needed.  There
 * are two new event types (global and dynamic events) as well as a more comprehensive
 * behavior model that includes class wide behaviors.
 *
 * A global event is defined by all events whose name starts with 'fx'.
 * The event name is potentially a method name and is thus case-insensitive. All 'fx' events
 * are valid as the whole 'fx' event/method space is global in nature. Any object may patch into
 * any global event by defining that event as a method. Global events have priorities
 * just like 'on' events; so as to be able to order the event execution. Due to the
 * nature of all events which start with 'fx' being valid, in effect, every object
 * has every 'fx' global event. It is simply an issue of tapping into the desired
 * global event.
 *
 * A global event that starts with 'fx' can be called even if the object does not
 * implement the method of the global event.  A call to a non-existing 'fx' method
 * will, at minimal, function and return null.  If a method argument list has a first
 * parameter, it will be returned instead of null.  This allows filtering and chaining.
 * 'fx' methods do not automatically install and uninstall. To install and uninstall an
 * object's global event listeners, call the object's {@link listen} and
 * {@link unlisten} methods, respectively.  An object may auto-install its global event
 * during {@link __construct} by overriding {@link getAutoGlobalListen} and returning true.
 *
 * As of PHP version 5.3, nulled objects without code references will still continue to persist
 * in the global event queue because {@link __destruct} is not automatically called.  In the common
 * __destruct method, if an object is listening to global events, then {@link unlisten} is called.
 * {@link unlisten} is required to be manually called before an object is
 * left without references if it is currently listening to any global events. This includes
 * class wide behaviors.
 *
 * An object that contains a method that starts with 'fx' will have those functions
 * automatically receive those events of the same name after {@link listen} is called on the object.
 *
 * An object may listen to a global event without defining an 'fx' method of the same name by
 * adding an object method to the global event list.  For example
 * <code>
 * $component->fxGlobalCheck=$callback;  // or $component->OnClick->add($callback);
 * $component->attachEventHandler('fxGlobalCheck',array($object, 'someMethod'));
 * </code>
 *
 * Events between Objects and their behaviors, Dynamic Events
 *
 * An intra-object/behavior event is defined by methods that start with 'dy'.  Just as with
 * 'fx' global events, every object has every dynamic event.  Any call to a method that
 * starts with 'dy' will be handled, regardless of whether it is implemented.  These
 * events are for communicating with attached behaviors.
 *
 * Dynamic events can be used in a variety of ways.  They can be used to tell behaviors
 * when a non-behavior method is called.  Dynamic events could be used as data filters.
 * They could also be used to specify when a piece of code is to be run, eg. should the
 * loop process be performed on a particular piece of data.  In this way, some control
 * is handed to the behaviors over the process and/or data.
 *
 * If there are no handlers for an 'fx' or 'dy' event, it will return the first
 * parameter of the argument list.  If there are no arguments, these events
 * will return null.  If there are handlers an 'fx' method will be called directly
 * within the object.  Global 'fx' events are triggered by calling {@link raiseEvent}.
 * For dynamic events where there are behaviors that respond to the dynamic events, a
 * {@link TCallChain} is developed.  A call chain allows the behavior dynamic event
 * implementations to call further implementing behaviors within a chain.
 *
 * If an object implements {@link IDynamicMethods}, all global and object dynamic
 * events will be sent to {@link __dycall}.  In the case of global events, all
 * global events will trigger this method.  In the case of behaviors, all undefined
 * dynamic events  which are called will be passed through to this method.
 *
 *
 * Behaviors
 *
 * There are two types of behaviors.  There are individual object behaviors and
 * there are class wide behaviors.  Class behaviors depend upon object behaviors.
 *
 * When a new class implements {@link IBehavior} or {@link IClassBehavior} or
 * extends {@link TBehavior} or {@link TClassBehavior}, it may be added to an
 * object by calling the object's {@link attachBehavior}.  The behaviors associated
 * name can then be used to {@link enableBehavior} or {@link disableBehavior}
 * the specific behavior.
 *
 * All behaviors may be turned on and off via {@link enableBehaviors} and
 * {@link disableBehaviors}, respectively.  To check if behaviors are on or off
 * a call to {@link getBehaviorsEnabled} will provide the variable.
 *
 * Attaching and detaching whole sets of behaviors is done using
 * {@link attachBehaviors} and {@link detachBehaviors}.  {@link clearBehaviors}
 * removes all of an object's behaviors.
 *
 * {@link asa} returns a behavior of a specific name.  {@link isa} is the
 * behavior inclusive function that acts as the PHP operator {@link instanceof}.
 * A behavior could provide the functionality of a specific class thus causing
 * the host object to act similarly to a completely different class.  A behavior
 * would then implement {@link IInstanceCheck} to provide the identity of the
 * different class.
 *
 * Class behaviors are similar to object behaviors except that the class behavior
 * is the implementation for all instances of the class.  A class behavior
 * will have the object upon which is being called be prepended to the parameter
 * list.  This way the object is known across the class behavior implementation.
 *
 * Class behaviors are attached using {@link attachClassBehavior} and detached
 * using {@link detachClassBehavior}.  Class behaviors are important in that
 * they will be applied to all new instances of a particular class.  In this way
 * class behaviors become default behaviors to a new instances of a class in
 * {@link __construct}.  Detaching a class behavior will remove the behavior
 * from the default set of behaviors created for an object when the object
 * is instanced.
 *
 * Class behaviors are also added to all existing instances via the global 'fx'
 * event mechanism.  When a new class behavior is added, the event
 * {@link fxAttachClassBehavior} is raised and all existing instances that are
 * listening to this global event (primarily after {@link listen} is called)
 * will have this new behavior attached.  A similar process is used when
 * detaching class behaviors.  Any objects listening to the global 'fx' event
 * {@link fxDetachClassBehavior} will have a class behavior removed.
 *
 * Dynamic Intra-Object Events
 *
 * Dynamic events start with 'dy'.  This mechanism is used to allow objects
 * to communicate with their behaviors directly.  The entire 'dy' event space
 * is valid.  All attached, enabled behaviors that implement a dynamic event
 * are called when the host object calls the dynamic event.  If there is no
 * implementation or behaviors, this returns null when no parameters are
 * supplied and will return the first parameter when there is at least one
 * parameter in the dynamic event.
 * <code>
 *	 null == $this->dyBehaviorEvent();
 *	 5 == $this->dyBehaviorEvent(5); //when no behaviors implement this dynamic event
 * </code>
 *
 * Dynamic events can be chained together within behaviors to allow for data
 * filtering. Dynamic events are implemented within behaviors by defining the
 * event as a method.
 * <code>
 * class TObjectBehavior extends TBehavior {
 *     public function dyBehaviorEvent($param1, $callchain) {
 *			//Do something, eg:  $param1 += 13;
 *			return $callchain->dyBehaviorEvent($param1);
 *     }
 * }
 * </code>
 * This implementation of a behavior and dynamic event will flow through to the
 * next behavior implementing the dynamic event.  The first parameter is always
 * return when it is supplied.  Otherwise a dynamic event returns null.
 *
 * In the case of a class behavior, the object is also prepended to the dynamic
 * event.
 * <code>
 * class TObjectClassBehavior extends TClassBehavior {
 *     public function dyBehaviorEvent($hostobject, $param1, $callchain) {
 *			//Do something, eg:  $param1 += $hostobject->getNumber();
 *			return $callchain->dyBehaviorEvent($param1);
 *     }
 * }
 * </code>
 * When calling a dynamic event, only the parameters are passed.  The host object
 * and the call chain are built into the framework.
 *
 * Global Event and Dynamic event catching
 *
 * Given that all global 'fx' events and dynamic 'dy' events are valid and
 * operational, there is a mechanism for catching events called that are not
 * implemented (similar to the built-in PHP method {@link __call}).  When
 * a dynamic or global event is called but a behavior does not implement it,
 * yet desires to know when an undefined dynamic event is run, the behavior
 * implements the interface {@link IDynamicMethods} and method {@link __dycall}.
 *
 * In the case of dynamic events, {@link __dycall} is supplied with the method
 * name and its parameters.  When a global event is raised, via {@link raiseEvent},
 * the method is the event name and the parameters are supplied.
 *
 * When implemented, this catch-all mechanism is called for event global event event
 * when implemented outside of a behavior.  Within a behavior, it will also be called
 * when the object to which the behavior is attached calls any unimplemented dynamic
 * event.  This is the fall-back mechanism for informing a class and/or behavior
 * of when an global and/or undefined dynamic event is executed.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Brad Anderson <javalizard@mac.com>
 * @package System
 * @since 3.0
 */
class TComponent
{
	/**
	 * @var array event handler lists
	 */
	private $_e=array();

	/**
	 * @var boolean if listening is enabled.  Automatically turned on or off in
	 * constructor according to {@link getAutoGlobalListen}.  Default false, off
	 */
	private $_listeningenabled=false;

	/**
	 * @var array static registered global event handler lists
	 */
	private static $_ue=array();

	/**
	 * @var boolean if object behaviors are on or off.  default true, on
	 */
	private $_behaviorsenabled=true;

	/**
	 * @var TPriorityMap list of object behaviors
	 */
	private $_m=null;

	/**
	 * @var array static global class behaviors, these behaviors are added upon instantiation of a class
	 */
	private static $_um=array();


	/**
	 * @const string the name of the global {@link raiseEvent} listener
	 */
	const GLOBAL_RAISE_EVENT_LISTENER='fxGlobalListener';


	/**
	 * The common __construct
	 * If desired by the new object, this will auto install and listen to global event functions
	 * as defined by the object via 'fx' methods. This also attaches any predefined behaviors.
	 * This function installs all class behaviors in a class hierarchy from the deepest subclass
	 * through each parent to the top most class, TComponent.
	 */
	public function __construct() {
		if($this->getAutoGlobalListen())
			$this->listen();

		$classes=array_reverse($this->getClassHierarchy(true));
		foreach($classes as $class) {
			if(isset(self::$_um[$class]))
				$this->attachBehaviors(self::$_um[$class]);
		}
	}


	/**
	 * Tells TComponent whether or not to automatically listen to global events.
	 * Defaults to false because PHP variable cleanup is affected if this is true.
	 * When unsetting a variable that is listening to global events, {@link unlisten}
	 * must explicitly be called when cleaning variables allocation or else the global
	 * event registry will contain references to the old object. This is true for PHP 5.4
	 *
	 * Override this method by a subclass to change the setting.  When set to true, this
	 * will enable {@link __construct} to call {@link listen}.
	 *
	 * @return boolean whether or not to auto listen to global events during {@link __construct}, default false
	 */
	public function getAutoGlobalListen() {
		return false;
	}


	/**
	 * The common __destruct
	 * This unlistens from the global event routines if listening
	 *
	 * PHP 5.3 does not __destruct objects when they are nulled and thus unlisten must be
	 * called must be explicitly called.
	 */
	public function __destruct() {
		if($this->_listeningenabled)
			$this->unlisten();
	}


	/**
	 * This utility function is a private array filter method.  The array values
	 * that start with 'fx' are filtered in.
	 */
	private function filter_prado_fx($name) {
		return strncasecmp($name,'fx',2)===0;
	}


	/**
	 * This returns an array of the class name and the names of all its parents.  The base object first,
	 * {@link TComponent}, and the deepest subclass is last.
	 * @param boolean optional should the names be all lowercase true/false
	 * @return array array of strings being the class hierarchy of $this.
	 */
	public function getClassHierarchy($lowercase = false)
	{
		$class=get_class($this);
		$classes=array($class);
		while($class=get_parent_class($class)){array_unshift($classes,$class);}
		if($lowercase)
			return array_map('strtolower',$classes);
		return $classes;
	}


	/**
	 * This adds an object's fx event handlers into the global broadcaster to listen into any
	 * broadcast global events called through {@link raiseEvent}
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyListen($globalEvents[, $chain]) {
	 * 		$this->listen(); //eg
	 * }
	 * </code>
	 * to be executed when listen is called.  All attached behaviors are notified through dyListen.
	 *
	 * @return numeric the number of global events that were registered to the global event registry
	 */
	public function listen() {
		if($this->_listeningenabled)
			return;

		$fx=array_filter(get_class_methods($this),array($this,'filter_prado_fx'));

		foreach($fx as $func)
			$this->attachEventHandler($func,array($this,$func));

		if(is_a($this,'IDynamicMethods')) {
			$this->attachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER,array($this,'__dycall'));
			array_push($fx,TComponent::GLOBAL_RAISE_EVENT_LISTENER);
		}

		$this->_listeningenabled=true;

		$this->dyListen($fx);

		return count($fx);
	}

	/**
	 * this removes an object's fx events from the global broadcaster
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyUnlisten($globalEvents[, $chain]) {
	 * 		$this->behaviorUnlisten(); //eg
	 * }
	 * </code>
	 * to be executed when listen is called.  All attached behaviors are notified through dyUnlisten.
	 *
	 * @return numeric the number of global events that were unregistered from the global event registry
	 */
	public function unlisten() {
		if(!$this->_listeningenabled)
			return;

		$fx=array_filter(get_class_methods($this),array($this,'filter_prado_fx'));

		foreach($fx as $func)
			$this->detachEventHandler($func,array($this,$func));

		if(is_a($this,'IDynamicMethods')) {
			$this->detachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER,array($this,'__dycall'));
			array_push($fx,TComponent::GLOBAL_RAISE_EVENT_LISTENER);
		}

		$this->_listeningenabled=false;

		$this->dyUnlisten($fx);

		return count($fx);
	}

	/**
	 * Gets the state of listening to global events
	 * @return boolean is Listening to global broadcast enabled
	 */
	public function getListeningToGlobalEvents()
	{
		return $this->_listeningenabled;
	}


	/**
	 * Calls a method.
	 * Do not call this method directly. This is a PHP magic method that we override
	 * to allow behaviors, dynamic events (intra-object/behavior events),
	 * undefined dynamic and global events, and
	 * to allow using the following syntax to call a property setter or getter.
	 * <code>
	 * $this->getPropertyName($value); // if there's a $this->getjsPropertyName() method
	 * $this->setPropertyName($value); // if there's a $this->setjsPropertyName() method
	 * </code>
	 *
	 * Additional object behaviors override class behaviors.
	 * dynamic and global events do not fail even if they aren't implemented.
	 * Any intra-object/behavior dynamic events that are not implemented by the behavior
	 * return the first function paramater or null when no parameters are specified.
	 *
	 * @param string method name that doesn't exist and is being called on the object
	 * @param mixed method parameters
	 * @throws TInvalidOperationException If the property is not defined or read-only or
	 * 		method is undefined
	 * @return mixed result of the method call, or false if 'fx' or 'dy' function but
	 *		is not found in the class, otherwise it runs
	 */
	public function __call($method, $args)
	{
		$getset=substr($method,0,3);
		if(($getset=='get')||($getset=='set'))
		{
			$propname=substr($method,3);
			$jsmethod=$getset.'js'.$propname;
			if(method_exists($this,$jsmethod))
			{
				if(count($args)>0)
					if($args[0]&&!($args[0] instanceof TJavaScriptString))
						$args[0]=new TJavaScriptString($args[0]);
				return call_user_func_array(array($this,$jsmethod),$args);
			}

			if (($getset=='set')&&method_exists($this,'getjs'.$propname))
				throw new TInvalidOperationException('component_property_readonly',get_class($this),$method);
		}

		if($this->_m!==null&&$this->_behaviorsenabled)
		{
			if(strncasecmp($method,'dy',2)===0)
			{
				$callchain=new TCallChain($method);
				foreach($this->_m->toArray() as $behavior)
				{
					if((!($behavior instanceof IBehavior)||$behavior->getEnabled())&&(method_exists($behavior,$method)||($behavior instanceof IDynamicMethods)))
					{
						$behavior_args=$args;
						if($behavior instanceof IClassBehavior)
							array_unshift($behavior_args,$this);
						$callchain->addCall(array($behavior,$method),$behavior_args);
					}

				}
				if($callchain->getCount()>0)
					return call_user_func_array(array($callchain,'call'),$args);
			}
			else
			{
				foreach($this->_m->toArray() as $behavior)
				{
					if((!($behavior instanceof IBehavior)||$behavior->getEnabled())&&method_exists($behavior,$method))
					{
						if($behavior instanceof IClassBehavior)
							array_unshift($args,$this);
						return call_user_func_array(array($behavior,$method),$args);
					}
				}
			}
		}

		if(strncasecmp($method,'dy',2)===0||strncasecmp($method,'fx',2)===0)
		{
			if($this instanceof IDynamicMethods)
				return $this->__dycall($method,$args);
			return isset($args[0])?$args[0]:null;
		}

		// don't thrown an exception for __magicMethods() or any other weird methods natively implemented by php
		if (!method_exists($this, $method)) {
			throw new TApplicationException('component_method_undefined',get_class($this),$method);
		}
	}


	/**
	 * Returns a property value or an event handler list by property or event name.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to read a property:
	 * <code>
	 * $value=$component->PropertyName;
	 * $value=$component->jsPropertyName; // return JavaScript literal
	 * </code>
	 * and to obtain the event handler list for an event,
	 * <code>
	 * $eventHandlerList=$component->EventName;
	 * </code>
	 * This will also return the global event handler list when specifing an 'fx'
	 * event,
	 * <code>
	 * $globalEventHandlerList=$component->fxEventName;
	 * </code>
	 * When behaviors are enabled, this will return the behavior of a specific
	 * name, a property of a behavior, or an object 'on' event defined by the behavior.
	 * @param string the property name or the event name
	 * @return mixed the property value or the event handler list as {@link TPriorityList}
	 * @throws TInvalidOperationException if the property/event is not defined.
	 */
	public function __get($name)
	{
		if(method_exists($this,$getter='get'.$name))
		{
			// getting a property
			return $this->$getter();
		}
		else if(method_exists($this,$jsgetter='getjs'.$name))
		{
			// getting a javascript property
			return (string)$this->$jsgetter();
		}
		else if(strncasecmp($name,'on',2)===0 && method_exists($this,$name))
		{
			// getting an event (handler list)
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new TPriorityList;
			return $this->_e[$name];
		}
		else if(strncasecmp($name,'fx',2)===0)
		{
			// getting a global event (handler list)
			$name=strtolower($name);
			if(!isset(self::$_ue[$name]))
				self::$_ue[$name]=new TPriorityList;
			return self::$_ue[$name];
		}
		else if($this->_behaviorsenabled)
		{
			// getting a behavior property/event (handler list)
			if(isset($this->_m[$name]))
				return $this->_m[$name];
			else if($this->_m!==null)
			{
				foreach($this->_m->toArray() as $behavior)
				{
					if((!($behavior instanceof IBehavior)||$behavior->getEnabled())&&
						(property_exists($behavior,$name)||$behavior->canGetProperty($name)||$behavior->hasEvent($name)))
						return $behavior->$name;
				}
			}
		}
		throw new TInvalidOperationException('component_property_undefined',get_class($this),$name);
	}

	/**
	 * Sets value of a component property.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to set a property or attach an event handler.
	 * <code>
	 * $this->PropertyName=$value;
	 * $this->jsPropertyName=$value; // $value will be treated as a JavaScript literal
	 * $this->EventName=$handler;
	 * $this->fxEventName=$handler; //global event listener
	 * </code>
	 * When behaviors are enabled, this will also set a behaviors properties and events.
	 * @param string the property name or event name
	 * @param mixed the property value or event handler
	 * @throws TInvalidOperationException If the property is not defined or read-only.
	 */
	public function __set($name,$value)
	{
		if(method_exists($this,$setter='set'.$name))
		{
			if(strncasecmp($name,'js',2)===0&&$value&&!($value instanceof TJavaScriptLiteral))
				$value = new TJavaScriptLiteral($value);
			return $this->$setter($value);
		}
		else if(method_exists($this,$jssetter='setjs'.$name))
		{
			if($value&&!($value instanceof TJavaScriptString))
				$value=new TJavaScriptString($value);
			return $this->$jssetter($value);
		}
		else if((strncasecmp($name,'on',2)===0&&method_exists($this,$name))||strncasecmp($name,'fx',2)===0)
		{
			return $this->attachEventHandler($name,$value);
		}
		else if($this->_m!==null&&$this->_m->getCount()>0&&$this->_behaviorsenabled)
		{
			$sets=0;
			foreach($this->_m->toArray() as $behavior)
			{
				if((!($behavior instanceof IBehavior)||$behavior->getEnabled())&&
					(property_exists($behavior,$name)||$behavior->canSetProperty($name)||$behavior->hasEvent($name))) {
					$behavior->$name=$value;
					$sets++;
				}
			}
			if($sets)return $value;

		}

		if(method_exists($this,'get'.$name)||method_exists($this,'getjs'.$name))
		{
			throw new TInvalidOperationException('component_property_readonly',get_class($this),$name);
		}
		else
		{
			throw new TInvalidOperationException('component_property_undefined',get_class($this),$name);
		}
	}

	/**
	 * Checks if a property value is null, there are no events in the object
	 * event list or global event list registered under the name, and, if
	 * behaviors are enabled,
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using isset() to detect if a component property is set or not.
	 * This also works for global events.  When behaviors are enabled, it
	 * will check for a behavior of the specified name, and also check
	 * the behavior for events and properties.
	 * @param string the property name or the event name
	 * @since 3.2.3
	 */
	public function __isset($name)
	{
		if(method_exists($this,$getter='get'.$name))
			return $this->$getter()!==null;
		else if(method_exists($this,$jsgetter='getjs'.$name))
			return $this->$jsgetter()!==null;
		else if(strncasecmp($name,'on',2)===0&&method_exists($this,$name))
		{
			$name=strtolower($name);
			return isset($this->_e[$name])&&$this->_e[$name]->getCount();
		}
		else if(strncasecmp($name,'fx',2)===0)
		{
			$name=strtolower($name);
			return isset(self::$_ue[$name])&&self::$_ue[$name]->getCount();
		}
		else if($this->_m!==null&&$this->_m->getCount()>0&&$this->_behaviorsenabled)
		{
			if(isset($this->_m[$name]))
				return true;
			foreach($this->_m->toArray() as $behavior)
			{
				if((!($behavior instanceof IBehavior)||$behavior->getEnabled()))
					return isset($behavior->$name);
			}

		}
		else
			return false;
	}

	/**
	 * Sets a component property to be null.  Clears the object or global
	 * events. When enabled, loops through all behaviors and unsets the
	 * property or event.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using unset() to set a component property to be null.
	 * @param string the property name or the event name
	 * @throws TInvalidOperationException if the property is read only.
	 * @since 3.2.3
	 */
	public function __unset($name)
	{
		if(method_exists($this,$setter='set'.$name))
			$this->$setter(null);
		else if(method_exists($this,$jssetter='setjs'.$name))
			$this->$jssetter(null);
		else if(strncasecmp($name,'on',2)===0&&method_exists($this,$name))
			$this->_e[strtolower($name)]->clear();
		else if(strncasecmp($name,'fx',2)===0)
			$this->getEventHandlers($name)->remove(array($this, $name));
		else if($this->_m!==null&&$this->_m->getCount()>0&&$this->_behaviorsenabled)
		{
			if(isset($this->_m[$name]))
				$this->detachBehavior($name);
			else {
				$unset=0;
				foreach($this->_m->toArray() as $behavior)
				{
					if((!($behavior instanceof IBehavior)||$behavior->getEnabled())) {
						unset($behavior->$name);
						$unset++;
					}
				}
				if(!$unset&&method_exists($this,'get'.$name))
					throw new TInvalidOperationException('component_property_readonly',get_class($this),$name);
			}
		} else if(method_exists($this,'get'.$name))
			throw new TInvalidOperationException('component_property_readonly',get_class($this),$name);
	}

	/**
	 * Determines whether a property is defined.
	 * A property is defined if there is a getter or setter method
	 * defined in the class. Note, property names are case-insensitive.
	 * @param string the property name
	 * @return boolean whether the property is defined
	 */
	public function hasProperty($name)
	{
		return $this->canGetProperty($name)||$this->canSetProperty($name);
	}

	/**
	 * Determines whether a property can be read.
	 * A property can be read if the class has a getter method
	 * for the property name. Note, property name is case-insensitive.
	 * This also checks for getjs.  When enabled, it loops through all
	 * active behaviors for the get property when undefined by the object.
	 * @param string the property name
	 * @return boolean whether the property can be read
	 */
	public function canGetProperty($name)
	{
		if(method_exists($this,'get'.$name)||method_exists($this,'getjs'.$name))
			return true;
		else if($this->_m!==null&&$this->_behaviorsenabled)
		{
			foreach($this->_m->toArray() as $behavior)
			{
				if((!($behavior instanceof IBehavior)||$behavior->getEnabled())&&$behavior->canGetProperty($name))
					return true;
			}
		}
		return false;
	}

	/**
	 * Determines whether a property can be set.
	 * A property can be written if the class has a setter method
	 * for the property name. Note, property name is case-insensitive.
	 * This also checks for setjs.  When enabled, it loops through all
	 * active behaviors for the set property when undefined by the object.
	 * @param string the property name
	 * @return boolean whether the property can be written
	 */
	public function canSetProperty($name)
	{
		if(method_exists($this,'set'.$name)||method_exists($this,'setjs'.$name))
			return true;
		else if($this->_m!==null&&$this->_behaviorsenabled)
		{
			foreach($this->_m->toArray() as $behavior)
			{
				if((!($behavior instanceof IBehavior)||$behavior->getEnabled())&&$behavior->canSetProperty($name))
					return true;
			}
		}
		return false;
	}

	/**
	 * Evaluates a property path.
	 * A property path is a sequence of property names concatenated by '.' character.
	 * For example, 'Parent.Page' refers to the 'Page' property of the component's
	 * 'Parent' property value (which should be a component also).
	 * When a property is not defined by an object, this also loops through all
	 * active behaviors of the object.
	 * @param string property path
	 * @return mixed the property path value
	 */
	public function getSubProperty($path)
	{
		$object=$this;
		foreach(explode('.',$path) as $property)
			$object=$object->$property;
		return $object;
	}

	/**
	 * Sets a value to a property path.
	 * A property path is a sequence of property names concatenated by '.' character.
	 * For example, 'Parent.Page' refers to the 'Page' property of the component's
	 * 'Parent' property value (which should be a component also).
	 * When a property is not defined by an object, this also loops through all
	 * active behaviors of the object.
	 * @param string property path
	 * @param mixed the property path value
	 */
	public function setSubProperty($path,$value)
	{
		$object=$this;
		if(($pos=strrpos($path,'.'))===false)
			$property=$path;
		else
		{
			$object=$this->getSubProperty(substr($path,0,$pos));
			$property=substr($path,$pos+1);
		}
		$object->$property=$value;
	}

	/**
	 * Determines whether an event is defined.
	 * An event is defined if the class has a method whose name is the event name
	 * prefixed with 'on', 'fx', or 'dy'.
	 * Every object responds to every 'fx' and 'dy' event as they are in a universally
	 * accepted event space.  'on' event must be declared by the object.
	 * When enabled, this will loop through all active behaviors for 'on' events
	 * defined by the behavior.
	 * Note, event name is case-insensitive.
	 * @param string the event name
	 * @return boolean
	 */
	public function hasEvent($name)
	{
		if((strncasecmp($name,'on',2)===0&&method_exists($this,$name))||strncasecmp($name,'fx',2)===0||strncasecmp($name,'dy',2)===0)
			return true;

		else if($this->_m!==null&&$this->_behaviorsenabled)
		{
			foreach($this->_m->toArray() as $behavior)
			{
				if((!($behavior instanceof IBehavior)||$behavior->getEnabled())&&$behavior->hasEvent($name))
					return true;
			}
		}
		return false;
	}

	/**
	 * Checks if an event has any handlers.  This function also checks through all
	 * the behaviors for 'on' events when behaviors are enabled.
	 * 'dy' dynamic events are not handled by this function.
	 * @param string the event name
	 * @return boolean whether an event has been attached one or several handlers
	 */
	public function hasEventHandler($name)
	{
		$name=strtolower($name);
		if(strncasecmp($name,'fx',2)===0)
			return isset(self::$_ue[$name])&&self::$_ue[$name]->getCount()>0;
		else
		{
			if(isset($this->_e[$name])&&$this->_e[$name]->getCount()>0)
				return true;
			else if($this->_m!==null&&$this->_behaviorsenabled) {
				foreach($this->_m->toArray() as $behavior)
				{
					if((!($behavior instanceof IBehavior)||$behavior->getEnabled())&&$behavior->hasEventHandler($name))
						return true;
				}
			}
		}
		return false;
	}

	/**
	 * Returns the list of attached event handlers for an 'on' or 'fx' event.   This function also
	 * checks through all the behaviors for 'on' event lists when behaviors are enabled.
	 * @return TPriorityList list of attached event handlers for an event
	 * @throws TInvalidOperationException if the event is not defined
	 */
	public function getEventHandlers($name)
	{
		if(strncasecmp($name,'on',2)===0&&method_exists($this,$name))
		{
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new TPriorityList;
			return $this->_e[$name];
		}
		else if(strncasecmp($name,'fx',2)===0)
		{
			$name=strtolower($name);
			if(!isset(self::$_ue[$name]))
				self::$_ue[$name]=new TPriorityList;
			return self::$_ue[$name];
		}
		else if($this->_m!==null&&$this->_behaviorsenabled)
		{
			foreach($this->_m->toArray() as $behavior)
			{
				if((!($behavior instanceof IBehavior)||$behavior->getEnabled())&&$behavior->hasEvent($name))
					return $behavior->getEventHandlers($name);
			}
		}
		throw new TInvalidOperationException('component_event_undefined',get_class($this),$name);
	}

	/**
	 * Attaches an event handler to an event.
	 *
	 * The handler must be a valid PHP callback, i.e., a string referring to
	 * a global function name, or an array containing two elements with
	 * the first element being an object and the second element a method name
	 * of the object. In Prado, you can also use method path to refer to
	 * an event handler. For example, array($object,'Parent.buttonClicked')
	 * uses a method path that refers to the method $object->Parent->buttonClicked(...).
	 *
	 * The event handler must be of the following signature,
	 * <code>
	 * function handlerName($sender, $param) {}
	 * function handlerName($sender, $param, $name) {}
	 * </code>
	 * where $sender represents the object that raises the event,
	 * and $param is the event parameter. $name refers to the event name
	 * being handled.
	 *
	 * This is a convenient method to add an event handler.
	 * It is equivalent to {@link getEventHandlers}($name)->add($handler).
	 * For complete management of event handlers, use {@link getEventHandlers}
	 * to get the event handler list first, and then do various
	 * {@link TPriorityList} operations to append, insert or remove
	 * event handlers. You may also do these operations like
	 * getting and setting properties, e.g.,
	 * <code>
	 * $component->OnClick[]=array($object,'buttonClicked');
	 * $component->OnClick->insertAt(0,array($object,'buttonClicked'));
	 * </code>
	 * which are equivalent to the following
	 * <code>
	 * $component->getEventHandlers('OnClick')->add(array($object,'buttonClicked'));
	 * $component->getEventHandlers('OnClick')->insertAt(0,array($object,'buttonClicked'));
	 * </code>
	 *
	 * Due to the nature of {@link getEventHandlers}, any active behaviors defining
	 * new 'on' events, this method will pass through to the behavior transparently.
	 *
	 * @param string the event name
	 * @param callback the event handler
	 * @param numeric|null the priority of the handler, defaults to null which translates into the
	 * default priority of 10.0 within {@link TPriorityList}
	 * @throws TInvalidOperationException if the event does not exist
	 */
	public function attachEventHandler($name,$handler,$priority=null)
	{
		$this->getEventHandlers($name)->add($handler,$priority);
	}

	/**
	 * Detaches an existing event handler.
	 * This method is the opposite of {@link attachEventHandler}.  It will detach
	 * any 'on' events definedb by an objects active behaviors as well.
	 * @param string event name
	 * @param callback the event handler to be removed
	 * @param numeric|false|null the priority of the handler, defaults to false which translates
	 * to an item of any priority within {@link TPriorityList}; null means the default priority
	 * @return boolean if the removal is successful
	 */
	public function detachEventHandler($name,$handler,$priority=false)
	{
		if($this->hasEventHandler($name))
		{
			try
			{
				$this->getEventHandlers($name)->remove($handler,$priority);
				return true;
			}
			catch(Exception $e)
			{
			}
		}
		return false;
	}

	/**
	 * Raises an event.  This raises both inter-object 'on' events and global 'fx' events.
	 * This method represents the happening of an event and will
	 * invoke all attached event handlers for the event in {@link TPriorityList} order.
	 * This method does not handle intra-object/behavior dynamic 'dy' events.
	 *
	 * There are ways to handle event responses.  By defailt {@link EVENT_RESULT_FILTER},
	 * all event responses are stored in an array, filtered for null responses, and returned.
	 * If {@link EVENT_RESULT_ALL} is specified, all returned results will be stored along
	 * with the sender and param in an array
	 * <code>
	 * 		$result[] = array('sender'=>$sender,'param'=>$param,'response'=>$response);
	 * </code>
	 *
	 * If {@link EVENT_RESULT_FEED_FORWARD} is specified, then each handler result is then
	 * fed forward as the parameters for the next event.  This allows for events to filter data
	 * directly by affecting the event parameters
	 *
	 * If a callable function is set in the response type or the post function filter is specified then the
	 * result of each called event handler is post processed by the callable function.  Used in
	 * combination with {@link EVENT_RESULT_FEED_FORWARD}, any event (and its result) can be chained.
	 *
	 * When raising a global 'fx' event, registered handlers in the global event list for
	 * {@link GLOBAL_RAISE_EVENT_LISTENER} are always added into the set of event handlers.  In this way,
	 * these global events are always raised for every global 'fx' event.  The registered handlers for global
	 * raiseEvent events have priorities.  Any registered global raiseEvent event handlers with a priority less than zero
	 * are added before the main event handlers being raised and any registered global raiseEvent event handlers
	 * with a priority equal or greater than zero are added after the main event handlers being raised.  In this way
	 * all {@link GLOBAL_RAISE_EVENT_LISTENER} handlers are always called for every raised 'fx' event.
	 *
	 * Behaviors may implement the following functions:
	 * <code>
	 *	public function dyPreRaiseEvent($name,$sender,$param,$responsetype,$postfunction[, $chain]) {
	 *  	return $name; //eg, the event name may be filtered/changed
	 *  }
	 *	public function dyIntraRaiseEventTestHandler($handler,$sender,$param,$name[, $chain]) {
	 *  	return true; //should this particular handler be executed?  true/false
	 *  }
	 *  public function dyIntraRaiseEventPostHandler($name,$sender,$param,$handler,$response[, $chain]) {
	 *		//contains the per handler response
	 *  }
	 *  public function dyPostRaiseEvent($responses,$name,$sender,$param,$responsetype,$postfunction[, $chain]) {
	 *		return $responses;
	 *  }
	 * </code>
	 * to be executed when raiseEvent is called.  The 'intra' dynamic events are called per handler in
	 * the handler loop.
	 *
	 * dyPreRaiseEvent has the effect of being able to change the event being raised.  This intra
	 * object/behavior event returns the name of the desired event to be raised.  It will pass through
	 * if no dynamic event is specified, or if the original event name is returned.
	 * dyIntraRaiseEventTestHandler returns true or false as to whether a specific handler should be
	 * called for a specific raised event (and associated event arguments)
	 * dyIntraRaiseEventPostHandler does not return anything.  This allows behaviors to access the results
	 * of an event handler in the per handler loop.
	 * dyPostRaiseEvent returns the responses.  This allows for any post processing of the event
	 * results from the sum of all event handlers
	 *
	 * When handling a catch-all {@link __dycall}, the method name is the name of the event
	 * and the parameters are the sender, the param, and then the name of the event.
	 *
	 * @param string the event name
	 * @param mixed the event sender object
	 * @param TEventParameter the event parameter
	 * @param numeric how the results of the event are tabulated.  default: {@link EVENT_RESULT_FILTER}  The default filters out
	 *		null responses. optional
	 * @param function any per handler filtering of the response result needed is passed through
	 *		this if not null. default: null.  optional
	 * @return mixed the results of the event
	 * @throws TInvalidOperationException if the event is undefined
	 * @throws TInvalidDataValueException If an event handler is invalid
	 */
	public function raiseEvent($name,$sender,$param,$responsetype=null,$postfunction=null)
	{
		$p=$param;
		if(is_callable($responsetype))
		{
			$postfunction=$responsetype;
			$responsetype=null;
		}

		if($responsetype===null)
			$responsetype=TEventResults::EVENT_RESULT_FILTER;

		$name=strtolower($name);
		$responses=array();

		$name=$this->dyPreRaiseEvent($name,$sender,$param,$responsetype,$postfunction);

		if($this->hasEventHandler($name)||$this->hasEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER))
		{
			$handlers=$this->getEventHandlers($name);
			$handlerArray=$handlers->toArray();
			if(strncasecmp($name,'fx',2)===0&&$this->hasEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER))
			{
				$globalhandlers=$this->getEventHandlers(TComponent::GLOBAL_RAISE_EVENT_LISTENER);
				$handlerArray=array_merge($globalhandlers->toArrayBelowPriority(0),$handlerArray,$globalhandlers->toArrayAbovePriority(0));
			}
			$response=null;
			foreach($handlerArray as $handler)
			{
				if($this->dyIntraRaiseEventTestHandler($handler,$sender,$param,$name)===false)
					continue;

				if(is_string($handler))
				{
					if(($pos=strrpos($handler,'.'))!==false)
					{
						$object=$this->getSubProperty(substr($handler,0,$pos));
						$method=substr($handler,$pos+1);
						if(method_exists($object,$method)||strncasecmp($method,'dy',2)===0||strncasecmp($method,'fx',2)===0)
						{
							if($method=='__dycall')
								$response=$object->__dycall($name,array($sender,$param,$name));
							else
								$response=$object->$method($sender,$param,$name);
						}
						else
							throw new TInvalidDataValueException('component_eventhandler_invalid',get_class($this),$name,$handler);
					}
					else
						$response=call_user_func($handler,$sender,$param,$name);
				}
				else if(is_callable($handler,true))
				{
					list($object,$method)=$handler;
					if(is_string($object))
						$response=call_user_func($handler,$sender,$param,$name);
					else
					{
						if(($pos=strrpos($method,'.'))!==false)
						{
							$object=$this->getSubProperty(substr($method,0,$pos));
							$method=substr($method,$pos+1);
						}
						if(method_exists($object,$method)||strncasecmp($method,'dy',2)===0||strncasecmp($method,'fx',2)===0)
						{
							if($method=='__dycall')
								$response=$object->__dycall($name,array($sender,$param,$name));
							else
								$response=$object->$method($sender,$param,$name);
						}
						else
							throw new TInvalidDataValueException('component_eventhandler_invalid',get_class($this),$name,$handler[1]);
					}
				}
				else
					throw new TInvalidDataValueException('component_eventhandler_invalid',get_class($this),$name,gettype($handler));

				$this->dyIntraRaiseEventPostHandler($name,$sender,$param,$handler,$response);

				if($postfunction)
					$response=call_user_func_array($postfunction,array($sender,$param,$this,$response));

				if($responsetype&TEventResults::EVENT_RESULT_ALL)
					$responses[]=array('sender'=>$sender,'param'=>$param,'response'=>$response);
				else
					$responses[]=$response;

				if($response!==null&&($responsetype&TEventResults::EVENT_RESULT_FEED_FORWARD))
					$param=$response;

			}
		}
		else if(strncasecmp($name,'on',2)===0&&!$this->hasEvent($name))
			throw new TInvalidOperationException('component_event_undefined',get_class($this),$name);

		if($responsetype&TEventResults::EVENT_RESULT_FILTER)
			$responses=array_filter($responses);

		$responses=$this->dyPostRaiseEvent($responses,$name,$sender,$param,$responsetype,$postfunction);

		return $responses;
	}

	/**
	 * Evaluates a PHP expression in the context of this control.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyEvaluateExpressionFilter($expression, $chain) {
	 * 		return $chain->dyEvaluateExpressionFilter(str_replace('foo', 'bar', $expression)); //example
	 * }
	 * </code>
	 * to be executed when evaluateExpression is called.  All attached behaviors are notified through
	 * dyEvaluateExpressionFilter.  The chaining is important in this function due to the filtering
	 * pass-through effect.
	 *
	 * @param string PHP expression
	 * @return mixed the expression result
	 * @throws TInvalidOperationException if the expression is invalid
	 */
	public function evaluateExpression($expression)
	{
		$expression=$this->dyEvaluateExpressionFilter($expression);
		try
		{
			if(eval("\$result=$expression;")===false)
				throw new Exception('');
			return $result;
		}
		catch(Exception $e)
		{
			throw new TInvalidOperationException('component_expression_invalid',get_class($this),$expression,$e->getMessage());
		}
	}

	/**
	 * Evaluates a list of PHP statements.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyEvaluateStatementsFilter($statements, $chain) {
	 * 		return $chain->dyEvaluateStatementsFilter(str_replace('foo', 'bar', $statements)); //example
	 * }
	 * </code>
	 * to be executed when evaluateStatements is called.  All attached behaviors are notified through
	 * dyEvaluateStatementsFilter.  The chaining is important in this function due to the filtering
	 * pass-through effect.
	 *
	 * @param string PHP statements
	 * @return string content echoed or printed by the PHP statements
	 * @throws TInvalidOperationException if the statements are invalid
	 */
	public function evaluateStatements($statements)
	{
		$statements=$this->dyEvaluateStatementsFilter($statements);
		try
		{
			ob_start();
			if(eval($statements)===false)
				throw new Exception('');
			$content=ob_get_contents();
			ob_end_clean();
			return $content;
		}
		catch(Exception $e)
		{
			throw new TInvalidOperationException('component_statements_invalid',get_class($this),$statements,$e->getMessage());
		}
	}

	/**
	 * This method is invoked after the component is instantiated by a template.
	 * When this method is invoked, the component's properties have been initialized.
	 * The default implementation of this method will invoke
	 * the potential parent component's {@link addParsedObject}.
	 * This method can be overridden.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyCreatedOnTemplate($parent, $chain) {
	 * 		return $chain->dyCreatedOnTemplate($parent); //example
	 *  }
	 * </code>
	 * to be executed when createdOnTemplate is called.  All attached behaviors are notified through
	 * dyCreatedOnTemplate.
	 *
	 * @param TComponent potential parent of this control
	 * @see addParsedObject
	 */
	public function createdOnTemplate($parent)
	{
		$parent=$this->dyCreatedOnTemplate($parent);
		$parent->addParsedObject($this);
	}

	/**
	 * Processes an object that is created during parsing template.
	 * The object can be either a component or a static text string.
	 * This method can be overridden to customize the handling of newly created objects in template.
	 * Only framework developers and control developers should use this method.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyAddParsedObject($object[, $chain]) {
	 *  }
	 * </code>
	 * to be executed when addParsedObject is called.  All attached behaviors are notified through
	 * dyAddParsedObject.
	 *
	 * @param string|TComponent text string or component parsed and instantiated in template
	 * @see createdOnTemplate
	 */
	public function addParsedObject($object)
	{
		$this->dyAddParsedObject($object);
	}


	/**
	 *	This is the method registered for all instanced objects should a class behavior be added after
	 * the class is instanced.  Only when the class to which the behavior is being added is in this
	 * object's class hierarchy, via {@link getClassHierarchy}, is the behavior added to this instance.
	 * @param $sender the application
	 * @param $param TClassBehaviorEventParameter
	 * @since 3.2.3
	 */
	public function fxAttachClassBehavior($sender,$param) {
		if(in_array($param->getClass(),$this->getClassHierarchy(true)))
			return $this->attachBehavior($param->getName(),$param->getBehavior(),$param->getPriority());
	}


	/**
	 *	This is the method registered for all instanced objects should a class behavior be removed after
	 * the class is instanced.  Only when the class to which the behavior is being added is in this
	 * object's class hierarchy, via {@link getClassHierarchy}, is the behavior removed from this instance.
	 * @param $sender the application
	 * @param $param TClassBehaviorEventParameter
	 * @since 3.2.3
	 */
	public function fxDetachClassBehavior($sender,$param) {
		if(in_array($param->getClass(),$this->getClassHierarchy(true)))
			return $this->detachBehavior($param->getName(),$param->getPriority());
	}


	/**
	 *	This will add a class behavior to all classes instanced (that are listening) and future newly instanced objects.
	 * This registers the behavior for future instances and pushes the changes to all the instances that are listening as well.
	 * The universal class behaviors are stored in an inverted stack with the latest class behavior being at the first position in the array.
	 * This is done so class behaviors are added last first.
	 * @param string name the key of the class behavior
	 * @param object|string class behavior or name of the object behavior per instance
	 * @param string|class string of class or class on which to attach this behavior.  Defaults to null which will error
	 *	but more important, if this is on PHP 5.3 it will use Late Static Binding to derive the class
	 * it should extend.
	 * <code>
	 * TPanel::attachClassBehavior('javascripts', (new TJsPanelBehavior())->init($this));
	 * </code>
	 * @param numeric|null priority of behavior, default: null the default priority of the {@link TPriorityList}  Optional.
	 * @throws TInvalidOperationException if the class behavior is being added to a {@link TComponent}; due to recursion.
	 * @throws TInvalidOperationException if the class behavior is already defined
	 * @since 3.2.3
	 */
	public static function attachClassBehavior($name,$behavior,$class=null,$priority=null) {
		if(!$class&&function_exists('get_called_class'))
			$class=get_called_class();
		if(!$class)
			throw new TInvalidOperationException('component_no_class_provided_nor_late_binding');

		if(!is_string($name))
			$name=get_class($name);
		$class=strtolower($class);
		if($class==='tcomponent')
			throw new TInvalidOperationException('component_no_tcomponent_class_behaviors');
		if(empty(self::$_um[$class]))
			self::$_um[$class]=array();
		if(isset(self::$_um[$class][$name]))
			throw new TInvalidOperationException('component_class_behavior_defined',$class,$name);
		$param=new TClassBehaviorEventParameter($class,$name,$behavior,$priority);
		self::$_um[$class]=array($name=>$param)+self::$_um[$class];
		$behaviorObject=is_string($behavior)?new $behavior:$behavior;
		return $behaviorObject->raiseEvent('fxAttachClassBehavior',null,$param);
	}


	/**
	 *	This will remove a behavior from a class.  It unregisters it from future instances and
	 * pulls the changes from all the instances that are listening as well.
	 * PHP 5.3 uses Late Static Binding to derive the static class upon which this method is called.
	 * @param $name the key of the class behavior
	 * @param $class string class on which to attach this behavior.  Defaults to null.
	 * @param $priority numeric|null|false priority.  false is any priority, null is default
	 *		{@link TPriorityList} priority, and numeric is a specific priority.
	 * @throws Exception if the the class cannot be derived from Late Static Binding and is not
	 * not supplied as a parameter.
	 * @since 3.2.3
	 */
	public static function detachClassBehavior($name,$class=null,$priority=false) {
		if(!$class&&function_exists('get_called_class'))
			$class=get_called_class();
		if(!$class)
			throw new TInvalidOperationException('component_no_class_provided_nor_late_binding');

		$class=strtolower($class);
		if(!is_string($name))
			$name=get_class($name);
		if(empty(self::$_um[$class])||!isset(self::$_um[$class][$name]))
			return false;
		$param=self::$_um[$class][$name];
		$behavior=$param->getBehavior();
		unset(self::$_um[$class][$name]);
		$behaviorObject=is_string($behavior)?new $behavior:$behavior;
		return $behaviorObject->raiseEvent('fxDetachClassBehavior',null,$param);
	}

	/**
	 * Returns the named behavior object.
	 * The name 'asa' stands for 'as a'.
	 * @param string the behavior name
	 * @return IBehavior the behavior object, or null if the behavior does not exist
	 * @since 3.2.3
	 */
	public function asa($behaviorname)
	{
		return isset($this->_m[$behaviorname])?$this->_m[$behaviorname]:null;
	}

	/**
	 * Returns whether or not the object or any of the behaviors are of a particular class.
	 * The name 'isa' stands for 'is a'.  This first checks if $this is an instanceof the class.
	 * It then checks each Behavior.  If a behavior implements {@link IInstanceCheck},
	 * then the behavior can determine what it is an instanceof.  If this behavior function returns true,
	 * then this method returns true.  If the behavior instance checking function returns false,
	 * then no further checking is performed as it is assumed to be correct.
	 *
	 * If the behavior instance check function returns nothing or null or the behavior
	 * doesn't implement the {@link IInstanceCheck} interface, then the default instanceof occurs.
	 * The default isa behavior is to check if the behavior is an instanceof the class.
	 *
	 * The behavior {@link IInstanceCheck} is to allow a behavior to have the host object
	 * act as a completely different object.
	 *
	 * @param class or string
	 * @return boolean whether or not the object or a behavior is an instance of a particular class
	 * @since 3.2.3
	 */
	public function isa($class)
	{
		if($this instanceof $class)
			return true;
		if($this->_m!==null&&$this->_behaviorsenabled)
			foreach($this->_m->toArray() as $behavior){
				if(($behavior instanceof IBehavior)&&!$behavior->getEnabled())
					continue;

				$check = null;
				if(($behavior->isa('IInstanceCheck'))&&$check=$behavior->isinstanceof($class,$this))
					return true;
				if($check===null&&($behavior->isa($class)))
					return true;
			}
		return false;
	}

	/**
	 * Attaches a list of behaviors to the component.
	 * Each behavior is indexed by its name and should be an instance of
	 * {@link IBehavior}, a string specifying the behavior class, or a
	 * {@link TClassBehaviorEventParameter}.
	 * @param array list of behaviors to be attached to the component
	 * @since 3.2.3
	 */
	public function attachBehaviors($behaviors)
	{
		foreach($behaviors as $name=>$behavior)
			if($behavior instanceof TClassBehaviorEventParameter)
				$this->attachBehavior($behavior->getName(),$behavior->getBehavior(),$behavior->getPriority());
			else
				$this->attachBehavior($name,$behavior);
	}

	/**
	 * Detaches select behaviors from the component.
	 * Each behavior is indexed by its name and should be an instance of
	 * {@link IBehavior}, a string specifying the behavior class, or a
	 * {@link TClassBehaviorEventParameter}.
	 * @param array list of behaviors to be detached from the component
	 * @since 3.2.3
	 */
	public function detachBehaviors($behaviors)
	{
		if($this->_m!==null)
		{
			foreach($behaviors as $name=>$behavior)
				if($behavior instanceof TClassBehaviorEventParameter)
					$this->detachBehavior($behavior->getName(),$behavior->getPriority());
				else
					$this->detachBehavior(is_string($behavior)?$behavior:$name);
		}
	}

	/**
	 * Detaches all behaviors from the component.
	 * @since 3.2.3
	 */
	public function clearBehaviors()
	{
		if($this->_m!==null)
		{
			foreach($this->_m->toArray() as $name=>$behavior)
				$this->detachBehavior($name);
			$this->_m=null;
		}
	}

	/**
	 * Attaches a behavior to this component.
	 * This method will create the behavior object based on the given
	 * configuration. After that, the behavior object will be initialized
	 * by calling its {@link IBehavior::attach} method.
	 *
	 * Already attached behaviors may implement the function:
	 * <code>
	 *	public function dyAttachBehavior($name,$behavior[, $chain]) {
	 *  }
	 * </code>
	 * to be executed when attachBehavior is called.  All attached behaviors are notified through
	 * dyAttachBehavior.
	 *
	 * @param string the behavior's name. It should uniquely identify this behavior.
	 * @param mixed the behavior configuration. This is passed as the first
	 * parameter to {@link YiiBase::createComponent} to create the behavior object.
	 * @return IBehavior the behavior object
	 * @since 3.2.3
	 */
	public function attachBehavior($name,$behavior,$priority=null)
	{
		if(is_string($behavior))
			$behavior=Prado::createComponent($behavior);
		if(!($behavior instanceof IBaseBehavior))
			throw new TInvalidDataTypeException('component_not_a_behavior',get_class($behavior));
		if($behavior instanceof IBehavior)
			$behavior->setEnabled(true);
		if($this->_m===null)
			$this->_m=new TPriorityMap;
		$behavior->attach($this);
		$this->dyAttachBehavior($name,$behavior);
		$this->_m->add($name,$behavior,$priority);
		return $behavior;
	}

	/**
	 * Detaches a behavior from the component.
	 * The behavior's {@link IBehavior::detach} method will be invoked.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyDetachBehavior($name,$behavior[, $chain]) {
	 *  }
	 * </code>
	 * to be executed when detachBehavior is called.  All attached behaviors are notified through
	 * dyDetachBehavior.
	 *
	 * @param string the behavior's name. It uniquely identifies the behavior.
	 * @param numeric the behavior's priority. This defaults to false, aka any priority.
	 * @return IBehavior the detached behavior. Null if the behavior does not exist.
	 * @since 3.2.3
	 */
	public function detachBehavior($name,$priority=false)
	{
		if($this->_m!=null&&isset($this->_m[$name]))
		{
			$this->_m[$name]->detach($this);
			$behavior=$this->_m->itemAt($name);
			$this->_m->remove($name,$priority);
			$this->dyDetachBehavior($name,$behavior);
			return $behavior;
		}
	}

	/**
	 * Enables all behaviors attached to this component independent of the behaviors
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyEnableBehaviors($name,$behavior[, $chain]) {
	 *  }
	 * </code>
	 * to be executed when enableBehaviors is called.  All attached behaviors are notified through
	 * dyEnableBehaviors.
	 * @since 3.2.3
	 */
	public function enableBehaviors()
	{
		if(!$this->_behaviorsenabled)
		{
			$this->_behaviorsenabled=true;
			$this->dyEnableBehaviors();
		}
	}

	/**
	 * Disables all behaviors attached to this component independent of the behaviors
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyDisableBehaviors($name,$behavior[, $chain]) {
	 *  }
	 * </code>
	 * to be executed when disableBehaviors is called.  All attached behaviors are notified through
	 * dyDisableBehaviors.
	 * @since 3.2.3
	 */
	public function disableBehaviors()
	{
		if($this->_behaviorsenabled)
		{
			$this->dyDisableBehaviors();
			$this->_behaviorsenabled=false;
		}
	}


	/**
	 * Returns if all the behaviors are turned on or off for the object.
	 * @return boolean whether or not all behaviors are enabled (true) or not (false)
	 * @since 3.2.3
	 */
	public function getBehaviorsEnabled()
	{
		return $this->_behaviorsenabled;
	}

	/**
	 * Enables an attached object behavior.  This cannot enable or disable whole class behaviors.
	 * A behavior is only effective when it is enabled.
	 * A behavior is enabled when first attached.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyEnableBehavior($name,$behavior[, $chain]) {
	 *  }
	 * </code>
	 * to be executed when enableBehavior is called.  All attached behaviors are notified through
	 * dyEnableBehavior.
	 *
	 * @param string the behavior's name. It uniquely identifies the behavior.
	 * @since 3.2.3
	 */
	public function enableBehavior($name)
	{
		if($this->_m!=null&&isset($this->_m[$name])){
			if($this->_m[$name] instanceof IBehavior) {
				$this->_m[$name]->setEnabled(true);
				$this->dyEnableBehavior($name,$this->_m[$name]);
				return true;
			}
			return false;
		}
		return null;
	}

	/**
	 * Disables an attached behavior.  This cannot enable or disable whole class behaviors.
	 * A behavior is only effective when it is enabled.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyDisableBehavior($name,$behavior[, $chain]) {
	 *  }
	 * </code>
	 * to be executed when disableBehavior is called.  All attached behaviors are notified through
	 * dyDisableBehavior.
	 *
	 * @param string the behavior's name. It uniquely identifies the behavior.
	 * @since 3.2.3
	 */
	public function disableBehavior($name)
	{
		if($this->_m!=null&&isset($this->_m[$name])){
			if($this->_m[$name] instanceof IBehavior) {
				$this->_m[$name]->setEnabled(false);
				$this->dyDisableBehavior($name,$this->_m[$name]);
				return true;
			}
			return false;
		}
		return null;
	}

	/**
	 * Returns an array with the names of all variables of that object that should be serialized.
	 * Do not call this method. This is a PHP magic method that will be called automatically
	 * prior to any serialization.
	 */
	public function __sleep()
	{
		$a = (array)$this;
		$a = array_keys($a);
		$exprops = array();
		$this->_getZappableSleepProps($exprops);
		return array_diff($a, $exprops);
	}

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		if($this->_listeningenabled===false)
			$exprops[] = "\0TComponent\0_listeningenabled";
		if($this->_behaviorsenabled===true)
			$exprops[] = "\0TComponent\0_behaviorsenabled";
		if ($this->_e===array())
			$exprops[] = "\0TComponent\0_e";
		if ($this->_m===null)
			$exprops[] = "\0TComponent\0_m";
	}
}


/**
 * IDynamicMethods interface.
 * IDynamicMethods marks an object to receive undefined global or dynamic events.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @version $Id$
 * @package System
 * @since 3.2.3
 */
interface IDynamicMethods
{
	public function __dycall($method,$args);
}



/**
 * TClassBehaviorEventParameter class.
 * TClassBehaviorEventParameter is the parameter sent with the class behavior changes.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @version $Id$
 * @package System
 * @since 3.2.3
 */
class TClassBehaviorEventParameter extends TEventParameter
{
	private $_class;
	private $_name;
	private $_behavior;
	private $_priority;

	/**
	 * Holds the parameters for the Class Behavior Events
	 *	@param string $class this is the class to get the behavior
	 *	@param string $name the name of the behavior
	 *	@param object $behavior this is the behavior to implement the class behavior
	 */
	public function __construct($class,$name,$behavior,$priority)
	{
		$this->_class=$class;
		$this->_name=$name;
		$this->_behavior=$behavior;
		$this->_priority=$priority;
	}

	/**
	 * This is the class to get the behavior
	 * @return string the class to get the behavior
	 */
	public function getClass()
	{
		return $this->_class;
	}

	/**
	 * name of the behavior
	 * @return string the name to get the behavior
	 */
	public function getName()
	{
		return $this->_name;
	}

	/**
	 * This is the behavior which the class is to get
	 * @return object the behavior to implement
	 */
	public function getBehavior()
	{
		return $this->_behavior;
	}

	/**
	 * This is the priority which the behavior is to get
	 * @return numeric the priority of the behavior
	 */
	public function getPriority()
	{
		return $this->_priority;
	}
}


/**
 * TEnumerable class.
 * TEnumerable is the base class for all enumerable types.
 * To define an enumerable type, extend TEnumberable and define string constants.
 * Each constant represents an enumerable value.
 * The constant name must be the same as the constant value.
 * For example,
 * <code>
 * class TTextAlign extends TEnumerable
 * {
 *     const Left='Left';
 *     const Right='Right';
 * }
 * </code>
 * Then, one can use the enumerable values such as TTextAlign::Left and
 * TTextAlign::Right.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.0
 */
class TEnumerable implements Iterator
{
	private $_enums=array();

	public function __construct() {
		$reflection=new ReflectionClass($this);
		$this->_enums=$reflection->getConstants();
	}

	public function current() {
		return current($this->_enums);
	}

	public function key() {
		return key($this->_enums);
	}

	public function next() {
		return next($this->_enums);
	}

	public function rewind() {
		reset($this->_enums);
	}

	public function valid() {
		return $this->current()!==false;
	}
}

/**
 * TPropertyValue class
 *
 * TPropertyValue is a utility class that provides static methods
 * to convert component property values to specific types.
 *
 * TPropertyValue is commonly used in component setter methods to ensure
 * the new property value is of specific type.
 * For example, a boolean-typed property setter method would be as follows,
 * <code>
 * function setPropertyName($value) {
 *     $value=TPropertyValue::ensureBoolean($value);
 *     // $value is now of boolean type
 * }
 * </code>
 *
 * Properties can be of the following types with specific type conversion rules:
 * - string: a boolean value will be converted to 'true' or 'false'.
 * - boolean: string 'true' (case-insensitive) will be converted to true,
 *            string 'false' (case-insensitive) will be converted to false.
 * - integer
 * - float
 * - array: string starting with '(' and ending with ')' will be considered as
 *          as an array expression and will be evaluated. Otherwise, an array
 *          with the value to be ensured is returned.
 * - object
 * - enum: enumerable type, represented by an array of strings.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.0
 */
class TPropertyValue
{
	/**
	 * Converts a value to boolean type.
	 * Note, string 'true' (case-insensitive) will be converted to true,
	 * string 'false' (case-insensitive) will be converted to false.
	 * If a string represents a non-zero number, it will be treated as true.
	 * @param mixed the value to be converted.
	 * @return boolean
	 */
	public static function ensureBoolean($value)
	{
		if (is_string($value))
			return strcasecmp($value,'true')==0 || $value!=0;
		else
			return (boolean)$value;
	}

	/**
	 * Converts a value to string type.
	 * Note, a boolean value will be converted to 'true' if it is true
	 * and 'false' if it is false.
	 * @param mixed the value to be converted.
	 * @return string
	 */
	public static function ensureString($value)
	{
		if (TJavaScript::isJsLiteral($value))
			return $value;
		if (is_bool($value))
			return $value?'true':'false';
		else
			return (string)$value;
	}

	/**
	 * Converts a value to integer type.
	 * @param mixed the value to be converted.
	 * @return integer
	 */
	public static function ensureInteger($value)
	{
		return (integer)$value;
	}

	/**
	 * Converts a value to float type.
	 * @param mixed the value to be converted.
	 * @return float
	 */
	public static function ensureFloat($value)
	{
		return (float)$value;
	}

	/**
	 * Converts a value to array type. If the value is a string and it is
	 * in the form (a,b,c) then an array consisting of each of the elements
	 * will be returned. If the value is a string and it is not in this form
	 * then an array consisting of just the string will be returned. If the value
	 * is not a string then
	 * @param mixed the value to be converted.
	 * @return array
	 */
	public static function ensureArray($value)
	{
		if(is_string($value))
		{
			$value = trim($value);
			$len = strlen($value);
			if ($len >= 2 && $value[0] == '(' && $value[$len-1] == ')')
			{
				eval('$array=array'.$value.';');
				return $array;
			}
			else
				return $len>0?array($value):array();
		}
		else
			return (array)$value;
	}

	/**
	 * Converts a value to object type.
	 * @param mixed the value to be converted.
	 * @return object
	 */
	public static function ensureObject($value)
	{
		return (object)$value;
	}

	/**
	 * Converts a value to enum type.
	 *
	 * This method checks if the value is of the specified enumerable type.
	 * A value is a valid enumerable value if it is equal to the name of a constant
	 * in the specified enumerable type (class).
	 * For more details about enumerable, see {@link TEnumerable}.
	 *
	 * For backward compatibility, this method also supports sanity
	 * check of a string value to see if it is among the given list of strings.
	 * @param mixed the value to be converted.
	 * @param mixed class name of the enumerable type, or array of valid enumeration values. If this is not an array,
	 * the method considers its parameters are of variable length, and the second till the last parameters are enumeration values.
	 * @return string the valid enumeration value
	 * @throws TInvalidDataValueException if the original value is not in the string array.
	 */
	public static function ensureEnum($value,$enums)
	{
		static $types=array();
		if(func_num_args()===2 && is_string($enums))
		{
			if(!isset($types[$enums]))
				$types[$enums]=new ReflectionClass($enums);
			if($types[$enums]->hasConstant($value))
				return $value;
			else
				throw new TInvalidDataValueException(
					'propertyvalue_enumvalue_invalid',$value,
						implode(' | ',$types[$enums]->getConstants()));
		}
		else if(!is_array($enums))
		{
			$enums=func_get_args();
			array_shift($enums);
		}
		if(in_array($value,$enums,true))
			return $value;
		else
			throw new TInvalidDataValueException('propertyvalue_enumvalue_invalid',$value,implode(' | ',$enums));
	}

	/**
	 * Converts the value to 'null' if the given value is empty
	 * @param mixed value to be converted
	 * @return mixed input or NULL if input is empty
	 */
	public static function ensureNullIfEmpty($value)
	{
		return empty($value)?null:$value;
	}
}

/**
 * TEventParameter class.
 * TEventParameter is the base class for all event parameter classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.0
 */
class TEventParameter extends TComponent
{
}

class TEventResults extends TEnumerable {
	const EVENT_RESULT_FEED_FORWARD=1;
	const EVENT_RESULT_FILTER=2;
	const EVENT_RESULT_ALL=4;
}

/**
 * TComponentReflection class.
 *
 * TComponentReflection provides functionalities to inspect the public/protected
 * properties, events and methods defined in a class.
 *
 * The following code displays the properties and events defined in {@link TDataGrid},
 * <code>
 *   $reflection=new TComponentReflection('TDataGrid');
 *   Prado::varDump($reflection->getProperties());
 *   Prado::varDump($reflection->getEvents());
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package System
 * @since 3.0
 */
class TComponentReflection extends TComponent
{
	private $_className;
	private $_properties=array();
	private $_events=array();
	private $_methods=array();

	/**
	 * Constructor.
	 * @param object|string the component instance or the class name
	 * @throws TInvalidDataTypeException if the object is not a component
	 */
	public function __construct($component)
	{
		if(is_string($component) && class_exists($component,false))
			$this->_className=$component;
		else if(is_object($component))
			$this->_className=get_class($component);
		else
			throw new TInvalidDataTypeException('componentreflection_class_invalid');
		$this->reflect();
	}

	private function isPropertyMethod($method)
	{
		$methodName=$method->getName();
		return $method->getNumberOfRequiredParameters()===0
				&& strncasecmp($methodName,'get',3)===0
				&& isset($methodName[3]);
	}

	private function isEventMethod($method)
	{
		$methodName=$method->getName();
		return strncasecmp($methodName,'on',2)===0
				&& isset($methodName[2]);
	}

	private function reflect()
	{
		$class=new ReflectionClass($this->_className);
		$properties=array();
		$events=array();
		$methods=array();
		$isComponent=is_subclass_of($this->_className,'TComponent') || strcasecmp($this->_className,'TComponent')===0;
		foreach($class->getMethods() as $method)
		{
			if($method->isPublic() || $method->isProtected())
			{
				$methodName=$method->getName();
				if(!$method->isStatic() && $isComponent)
				{
					if($this->isPropertyMethod($method))
						$properties[substr($methodName,3)]=$method;
					else if($this->isEventMethod($method))
					{
						$methodName[0]='O';
						$events[$methodName]=$method;
					}
				}
				if(strncmp($methodName,'__',2)!==0)
					$methods[$methodName]=$method;
			}
		}
		$reserved=array();
		ksort($properties);
		foreach($properties as $name=>$method)
		{
			$this->_properties[$name]=array(
				'type'=>$this->determinePropertyType($method),
				'readonly'=>!$class->hasMethod('set'.$name),
				'protected'=>$method->isProtected(),
				'class'=>$method->getDeclaringClass()->getName(),
				'comments'=>$method->getDocComment()
			);
			$reserved['get'.strtolower($name)]=1;
			$reserved['set'.strtolower($name)]=1;
		}
		ksort($events);
		foreach($events as $name=>$method)
		{
			$this->_events[$name]=array(
				'class'=>$method->getDeclaringClass()->getName(),
				'protected'=>$method->isProtected(),
				'comments'=>$method->getDocComment()
			);
			$reserved[strtolower($name)]=1;
		}
		ksort($methods);
		foreach($methods as $name=>$method)
		{
			if(!isset($reserved[strtolower($name)]))
				$this->_methods[$name]=array(
					'class'=>$method->getDeclaringClass()->getName(),
					'protected'=>$method->isProtected(),
					'static'=>$method->isStatic(),
					'comments'=>$method->getDocComment()
				);
		}
	}

	/**
	 * Determines the property type.
	 * This method uses the doc comment to determine the property type.
	 * @param ReflectionMethod
	 * @return string the property type, '{unknown}' if type cannot be determined from comment
	 */
	protected function determinePropertyType($method)
	{
		$comment=$method->getDocComment();
		if(preg_match('/@return\\s+(.*?)\\s+/',$comment,$matches))
			return $matches[1];
		else
			return '{unknown}';
	}

	/**
	 * @return string class name of the component
	 */
	public function getClassName()
	{
		return $this->_className;
	}

	/**
	 * @return array list of component properties. Array keys are property names.
	 * Each array element is of the following structure:
	 * [type]=>property type,
	 * [readonly]=>whether the property is read-only,
	 * [protected]=>whether the method is protected or not
	 * [class]=>the class where the property is inherited from,
	 * [comments]=>comments	associated with the property.
	 */
	public function getProperties()
	{
		return $this->_properties;
	}

	/**
	 * @return array list of component events. Array keys are event names.
	 * Each array element is of the following structure:
	 * [protected]=>whether the event is protected or not
	 * [class]=>the class where the event is inherited from.
	 * [comments]=>comments associated with the event.
	 */
	public function getEvents()
	{
		return $this->_events;
	}

	/**
	 * @return array list of public/protected methods. Array keys are method names.
	 * Each array element is of the following structure:
	 * [protected]=>whether the method is protected or not
	 * [static]=>whether the method is static or not
	 * [class]=>the class where the property is inherited from,
	 * [comments]=>comments associated with the event.
	 */
	public function getMethods()
	{
		return $this->_methods;
	}
}

/**
 * IBaseBehavior interface is the base behavior class from which all other
 * behaviors types are derived
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @version $Id$
 * @package System
 * @since 3.2.3
 */
interface IBaseBehavior {
	/**
	 * Attaches the behavior object to the component.
	 * @param CComponent the component that this behavior is to be attached to.
	 */
	public function attach($component);
	/**
	 * Detaches the behavior object from the component.
	 * @param CComponent the component that this behavior is to be detached from.
	 */
	public function detach($component);
}

/**
 * IBehavior interfaces is implemented by instance behavior classes.
 *
 * A behavior is a way to enhance a component with additional methods and
 * events that are defined in the behavior class and not available in the
 * class.  Objects may signal behaviors through dynamic events.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @version $Id$
 * @package System
 * @since 3.2.3
 */
interface IBehavior extends IBaseBehavior
{
	/**
	 * @return boolean whether this behavior is enabled
	 */
	public function getEnabled();
	/**
	 * @param boolean whether this behavior is enabled
	 */
	public function setEnabled($value);
}


/**
 * IClassBehavior interface is implements behaviors across all instances of
 * a particular class
 *
 * Any calls to functions not present in the original object but to behaviors
 * derived from this class, will have inserted as the first argument parameter
 * the object containing the behavior.
 *
 * For example:
 * <code>
 * $objWithClassBehavior->MethodOfClassBehavior(1, 20);
 * </code>
 * will be acted within the class behavior like this:
 * <code>
 * public function MethodOfClassBehavior($object, $firstParam, $secondParam){
 *      // $object === $objWithClassBehavior, $firstParam === 1, $secondParam === 20
 * }
 * </code>
 *
 * This also holds for 'dy' events as well.  For dynamic events, method arguments would be:
 * <code>
 * public function dyMethodOfClassBehavior($object, $firstParam, $secondParam, $callchain){
 *      // $object === $objWithClassBehavior, $firstParam === 1, $secondParam === 20, $callchain instanceof {@link TCallChain}
 * }
 * </code>
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @version $Id$
 * @package System
 * @since 3.2.3
 */
interface IClassBehavior extends IBaseBehavior {
}


/**
 * IInstanceCheck This interface allows objects to determine their own
 * 'instanceof' results when {@link TComponent::isa} is called.  This is
 * important with behaviors because behaviors may want to look like
 * particular objects other than themselves.
 *
 * @author Brad Anderson <javalizard@mac.com>
 * @version $Id$
 * @package System
 * @since 3.2.3
 */
interface IInstanceCheck {
	/**
	 * The method checks $this or, if needed, the parameter $instance is of type
	 * class.  In the case of a Class Behavior, the instance to which the behavior
	 * is attached may be important to determine if $this is an instance
	 * of a particular class.
	 * @param class|string the component that this behavior is checking if it is an instanceof.
	 * @param object the object which the behavior is attached to.  default: null
	 * @return boolean|null if the this or the instance is of type class.  When null, no information could be derived and
	 * the default mechanisms take over.
	 */
	public function isinstanceof($class,$instance=null);
}

/**
 * TJavaScriptLiteral class that encloses string literals that are not
 * supposed to be escaped by {@link TJavaScript::encode() }
 *
 * Since Prado 3.2 all the data that gets sent clientside inside a javascript statement
 * is encoded by default to avoid any kind of injection.
 * Sometimes there's the need to bypass this encoding and send raw javascript code.
 * To ensure that a string doesn't get encoded by {@link TJavaScript::encode() },
 * construct a new TJavaScriptLiteral:
 * <code>
 * // a javascript test string
 * $js="alert('hello')";
 * // the string in $raw will not be encoded when sent clientside inside a javascript block
 * $raw=new TJavaScriptLiteral($js);
 * // shortened form
 * $raw=_js($js);
 * </code>
 *
 * @package System
 * @since 3.2.0
 */
class TJavaScriptLiteral
{
	protected $_s;

	public function __construct($s)
	{
		$this->_s = $s;
	}

	public function __toString()
	{
		return (string)$this->_s;
	}

	public function toJavaScriptLiteral()
	{
		return $this->__toString();
	}
}

/**
 * TJavaScriptString class is an internal class that marks strings that will be
 * forcibly encoded when rendered inside a javascript block
 *
 * @package System
 * @since 3.2.0
 */
class TJavaScriptString extends TJavaScriptLiteral
{
	public function toJavaScriptLiteral()
	{
		return TJavaScript::jsonEncode((string)$this->_s,JSON_HEX_QUOT | JSON_HEX_APOS | JSON_HEX_TAG);
	}
}

