<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 *
 * Global Events, intra-object events, Class behaviors, expanded behaviors
 * @author Brad Anderson <javalizard@mac.com>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

use Prado\Collections\TPriorityMap;
use Prado\Collections\TWeakCallableCollection;
use Prado\Exceptions\TApplicationException;
use Prado\Exceptions\TInvalidDataTypeException;
use Prado\Exceptions\TInvalidDataValueException;
use Prado\Exceptions\TInvalidOperationException;
use Prado\Util\IBaseBehavior;
use Prado\Util\IBehavior;
use Prado\Util\TCallChain;
use Prado\Util\IClassBehavior;
use Prado\Util\IDynamicMethods;
use Prado\Util\TClassBehaviorEventParameter;
use Prado\Web\Javascripts\TJavaScriptLiteral;
use Prado\Web\Javascripts\TJavaScriptString;

/**
 * TComponent class
 *
 * TComponent is the base class for all PRADO components.
 * TComponent implements the protocol of defining, using properties, behaviors,
 * events, dynamic events, and global events.
 *
 * Properties
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
 *
 * Javascript Get and Set Properties
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
 *
 * Object Events
 *
 * An event is defined by the presence of a method whose name starts with 'on'.
 * The event name is the method name and is thus case-insensitive.
 * An event can be attached with one or several methods (called event handlers).
 * An event can be raised by calling {@link raiseEvent} method, upon which
 * the attached event handlers will be invoked automatically in the order they
 * are attached to the event. Event handlers must have the following signature,
 * <code>
 * function eventHandlerFuncName($sender, $param) { ... }
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
 * $component->OnClick = $callback;
 * $component->OnClick->add($callback);
 * $component->attachEventHandler('OnClick', $callback);
 * </code>
 * The first two ways make use of the fact that $component->OnClick refers to
 * the event handler list {@link TWeakCallableCollection} for the 'OnClick' event.
 * The variable $callback contains the definition of the event handler that can
 * be either:
 *
 * a string referring to a global function name
 * <code>
 * $component->OnClick = 'buttonClicked';
 * // will cause the following function to be called
 * buttonClicked($sender, $param);
 * </code>
 *
 * All types of PHP Callables are supported, such as:
 *  - Simple Callback function string, eg. 'my_callback_function'
 *  - Static class method call, eg. ['MyClass', 'myCallbackMethod'] and 'MyClass::myCallbackMethod'
 *  - Object method call, eg. [$object, 'myCallbackMethod']
 *  - Objects implementing __invoke
 *  - Closure / anonymous functions
 *
 * PRADO can accept method names in PRADO namespace as well.
 * <code>
 * $component->OnClick = [$object, 'buttonClicked'];
 * // will cause the following function to be called
 * $object->buttonClicked($sender, param);
 *
 * // the method can also be expressed using the PRADO namespace format
 * $component->OnClick = [$object, 'MainContent.SubmitButton.buttonClicked'];
 * // will cause the following function to be called
 * $object->MainContent->SubmitButton->buttonClicked($sender, $param);
 *
 * // Closure as an event handler
 * $component->OnClick = function ($sender, $param) { ... };
 * </code
 *
 *
 * Global and Dynamic Events
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
 * class wide behaviors.  This is corrected in PHP 7.4.0 with WeakReferences and {@link
 * TWeakCallableCollection}
 *
 * An object that contains a method that starts with 'fx' will have those functions
 * automatically receive those events of the same name after {@link listen} is called on the object.
 *
 * An object may listen to a global event without defining an 'fx' method of the same name by
 * adding an object method to the global event list.  For example
 * <code>
 * $component->fxGlobalCheck=$callback;
 * $component->fxGlobalCheck->add($callback);
 * $component->attachEventHandler('fxGlobalCheck', [$object, 'someMethod']);
 * </code>
 *
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
 * PRADO TComponent Behaviors is a method to extend a single component or a class
 * of components with new properties, methods, features, and fine control over the
 * owner object.  Behaviors can be attached to single objects or whole classes
 * (or interfaces, parents, and first level traits).
 *
 * There are two types of behaviors.  There are individual {@link IBehavior} and
 * there are class wide {IClassBehavior}.  IBehavior has one owner and IClassBehavior
 * can attach to multiple owners at the same time.  IClassBehavior is designed to be
 * stateless, like for specific filtering or addition of data.
 *
 * When a new class implements {@link IClassBehavior} or {@link IBehavior}, or extends
 * the PRADO implementations {@link TClassBehavior} and {@link TBehavior}, it may be
 * attached to a TComponent by calling the object's {@link attachBehavior}. The
 * behaviors associated name can then be used to {@link enableBehavior} or {@link
 * disableBehavior} the specific behavior.
 *
 * All behaviors may be turned on and off via {@link enableBehaviors} and
 * {@link disableBehaviors}, respectively.  To check if behaviors are on or off
 * a call to {@link getBehaviorsEnabled} will provide the variable.  By default,
 * a behavior's event handlers will be removed from events when disabled.
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
 * IClassBehavior are similar to IBehavior except that the class behavior
 * attaches to multiple owners, like all the instances of a class.  A class behavior
 * will have the object upon which is being called be prepended to the parameter
 * list.  This way the object is known across the class behavior implementation.
 *
 * Class behaviors are attached using {@link attachClassBehavior} and detached
 * using {@link detachClassBehavior}.  Class behaviors are important in that
 * they will be applied to all new instances of a particular class and all listening
 * components as well.  Classes, Class Parents, Interfaces, and first level Traits
 * can be attached by class.
 * Class behaviors are default behaviors to new instances of a class in and are
 * received in {@link __construct}.  Detaching a class behavior will remove the
 * behavior from the default set of behaviors created for an object when the object
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
 * Anonymous Behaviors are supported where the behavior does not have a name or
 * the behavior has a numeric for a name.  These cannot be accessed by name because
 * their names may be different in each request, for different owners, and possibly,
 * though extremely rarely, even the same object between serialization-sleep and
 * unserialization-wakeup.
 *
 * When serializing a component with behaviors, behaviors are saved and restored.
 * Named IClassBehavior class behaviors are updated with the current instance
 * of the named class behavior rather than replicate it from the wake up. {@link
 * __wakeup} will add any new named class behaviors to the unserializing component.
 *
 * IClassBehaviors can only use one given name for all behaviors except when applied
 * anonymously (with no name or a numeric name).
 *
 *
 * Dynamic Intra-Object Behavior Events
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
 *			//Do something, eg:  $param1 += $this->getOwner()->getNumber();
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
 *
 * Global Event and Dynamic Event Catching
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
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 3.0
 * @method void dyClone()
 * @method void dyWakeUp()
 * @method void dyListen(array $globalEvents)
 * @method void dyUnlisten(array $globalEvents)
 * @method string dyPreRaiseEvent(string $name, mixed $sender, \Prado\TEventParameter $param, null|numeric $responsetype, null|function $postfunction)
 * @method dyIntraRaiseEventTestHandler(callable $handler, mixed $sender, \Prado\TEventParameter $param, string $name)
 * @method bool dyIntraRaiseEventPostHandler(string $name, mixed $sender, \Prado\TEventParameter $param, callable $handler, $response)
 * @method array dyPostRaiseEvent(array $responses, string $name, mixed $sender, \Prado\TEventParameter $param, null|numeric $responsetype, null|function $postfunction)
 * @method string dyEvaluateExpressionFilter(string $statements)
 * @method string dyEvaluateStatementsFilter(string $statements)
 * @method dyCreatedOnTemplate(\Prado\TComponent $parent)
 * @method void dyAddParsedObject(\Prado\TComponent|string $object)
 * @method void dyAttachBehavior(string $name, IBaseBehavior $behavior)
 * @method void dyDetachBehavior(string $name, IBaseBehavior $behavior)
 * @method void dyEnableBehavior(string $name, IBaseBehavior $behavior)
 * @method void dyDisableBehavior(string $name, IBaseBehavior $behavior)
 * @method void dyEnableBehaviors()
 * @method void dyDisableBehaviors()
 */
class TComponent
{
	/**
	 * @var array event handler lists
	 */
	protected $_e = [];

	/**
	 * @var bool if listening is enabled.  Automatically turned on or off in
	 * constructor according to {@link getAutoGlobalListen}.  Default false, off
	 */
	protected $_listeningenabled = false;

	/**
	 * @var array static registered global event handler lists
	 */
	private static $_ue = [];

	/**
	 * @var bool if object behaviors are on or off.  default true, on
	 */
	protected $_behaviorsenabled = true;

	/**
	 * @var TPriorityMap list of object behaviors
	 */
	protected $_m;

	/**
	 * @var array static global class behaviors, these behaviors are added upon instantiation of a class
	 */
	private static $_um = [];


	/**
	 * @const string the name of the global {@link raiseEvent} listener
	 */
	public const GLOBAL_RAISE_EVENT_LISTENER = 'fxGlobalListener';


	/**
	 * The common __construct.
	 * If desired by the new object, this will auto install and listen to global event functions
	 * as defined by the object via 'fx' methods. This also attaches any predefined behaviors.
	 * This function installs all class behaviors in a class hierarchy from the deepest subclass
	 * through each parent to the top most class, TComponent.
	 */
	public function __construct()
	{
		if ($this->getAutoGlobalListen()) {
			$this->listen();
		}

		$classes = $this->getClassHierarchy(true);
		array_pop($classes);
		foreach ($classes as $class) {
			if (isset(self::$_um[$class])) {
				$this->attachBehaviors(self::$_um[$class], true);
			}
		}
	}

	/**
	 * The common __clone magic method from PHP's "clone".
	 * This reattaches the behaviors to the cloned object.
	 * IBehavior objects are cloned, IClassBehaviors are not.
	 * Clone object events are scrubbed of the old object behaviors' events.
	 * To finalize the behaviors, dyClone is raised.
	 * @since 4.2.3
	 */
	public function __clone()
	{
		foreach ($this->_e as $event => $handlers) {
			$this->_e[$event] = clone $handlers;
		}
		$behaviorArray = array_values(($this->_m !== null) ? $this->_m->toArray() : []);
		if (count($behaviorArray) && count($this->_e)) {
			$behaviorArray = array_combine(array_map('spl_object_id', $behaviorArray), $behaviorArray);
			foreach ($this->_e as $event => $handlers) {
				foreach ($handlers->toArray() as $handler) {
					$a = is_array($handler);
					if ($a && array_key_exists(spl_object_id($handler[0]), $behaviorArray) || !$a && array_key_exists(spl_object_id($handler), $behaviorArray)) {
						$handlers->remove($handler);
					}
				}
			}
		}
		if ($this->_m !== null) {
			$behaviors = $this->_m;
			$this->_m = new TPriorityMap();
			foreach ($behaviors->getPriorities() as $priority) {
				foreach ($behaviors->itemsAtPriority($priority) as $name => $behavior) {
					if ($behavior instanceof IBehavior) {
						$behavior = clone $behavior;
					}
					$this->attachBehavior($name, $behavior, $priority);
				}
			}
		}
		$this->callBehaviorsMethod('dyClone', $return);
	}

	/**
	 * The common __wakeup magic method from PHP's "unserialize".
	 * This reattaches the behaviors to the reconstructed object.
	 * Any global class behaviors are used rather than their unserialized copy.
	 * Any global behaviors not found in the object will be added.
	 * To finalize the behaviors, dyWakeUp is raised.
	 * If a TModule needs to add events to an object during unserialization,
	 * the module can use a small IClassBehavior [implementing dyWakeUp]
	 * (adding the event[s]) attached to the class with {@link
	 * attachClassBehavior} prior to unserialization.
	 * @since 4.2.3
	 */
	public function __wakeup()
	{
		$classes = $this->getClassHierarchy(true);
		array_pop($classes);
		$classBehaviors = [];
		if ($this->_m !== null) {
			$behaviors = $this->_m;
			$this->_m = new TPriorityMap();
			foreach ($behaviors->getPriorities() as $priority) {
				foreach ($behaviors->itemsAtPriority($priority) as $name => $behavior) {
					if ($behavior instanceof IClassBehavior && !is_numeric($name)) {
						//Replace class behaviors with their current instances, if they exist.
						foreach ($classes as $class) {
							if (isset(self::$_um[$class]) && array_key_exists($name, self::$_um[$class])) {
								$behavior = self::$_um[$class][$name]->getBehavior();
								break;
							}
						}
					}
					$classBehaviors[$name] = $name;
					$this->attachBehavior($name, $behavior, $priority);
				}
			}
		}
		foreach ($classes as $class) {
			if (isset(self::$_um[$class])) {
				foreach (self::$_um[$class] as $name => $behavior) {
					if(is_numeric($name)) {
						continue;
					}
					if (!array_key_exists($name, $classBehaviors)) {
						$this->attachBehaviors([$name => $behavior], true);
					}
				}
			}
		}
		$this->callBehaviorsMethod('dyWakeUp', $return);
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
	 * @return bool whether or not to auto listen to global events during {@link __construct}, default false
	 */
	public function getAutoGlobalListen()
	{
		return false;
	}


	/**
	 * The common __destruct
	 * When listening, this unlistens from the global event routines.  It also detaches
	 * all the behaviors so they can clean up, eg remove handlers.
	 *
	 * Prior to PHP 7.4, when listening, unlisten must be manually called for objects
	 * to destruct because circular references will prevent the __destruct process.
	 */
	public function __destruct()
	{
		$this->clearBehaviors();
		if ($this->_listeningenabled) {
			$this->unlisten();
		}
	}


	/**
	 * This utility function is a private array filter method.  The array values
	 * that start with 'fx' are filtered in.
	 * @param mixed $name
	 */
	private function filter_prado_fx($name)
	{
		return strncasecmp($name, 'fx', 2) === 0;
	}


	/**
	 * This returns an array of the class name and the names of all its parents.  The base object last,
	 * {@link TComponent}, and the deepest subclass is first.
	 * @param bool $lowercase optional should the names be all lowercase true/false
	 * @return string[] array of strings being the class hierarchy of $this.
	 */
	public function getClassHierarchy($lowercase = false)
	{
		static $_classhierarchy = [];
		$class = $this::class;
		if (isset($_classhierarchy[$class]) && isset($_classhierarchy[$class][$lowercase ? 1 : 0])) {
			return $_classhierarchy[$class][$lowercase ? 1 : 0];
		}
		$classes = [array_values(class_implements($class))];
		do {
			$classes[] = array_values(class_uses($class));
			$classes[] = [$class];
		} while ($class = get_parent_class($class));
		$classes = array_merge(...$classes);
		if ($lowercase) {
			$classes = array_map('strtolower', $classes);
		}
		$_classhierarchy[$class] ??= [];
		$_classhierarchy[$class][$lowercase ? 1 : 0] = $classes;

		return $classes;
	}

	/**
	 * This caches the 'fx' events for classes.
	 * @param object $class
	 * @return string[] fx events from a specific class
	 */
	protected function getClassFxEvents($class)
	{
		static $_classfx = [];
		$className = $class::class;
		if (isset($_classfx[$className])) {
			return $_classfx[$className];
		}
		$fx = array_filter(get_class_methods($class), [$this, 'filter_prado_fx']);
		$_classfx[$className] = $fx;
		return $fx;
	}

	/**
	 * This adds an object's fx event handlers into the global broadcaster to listen into any
	 * broadcast global events called through {@link raiseEvent}
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyListen($globalEvents[, ?TCallChain $chain = null]) {
	 * 		$this->listen($globalEvents); //eg
	 *      if ($chain)
	 *          $chain->dyUnlisten($globalEvents);
	 * }
	 * </code>
	 * to be executed when listen is called.  All attached behaviors are notified through dyListen.
	 *
	 * @return numeric the number of global events that were registered to the global event registry
	 */
	public function listen()
	{
		if ($this->_listeningenabled) {
			return;
		}

		$fx = $this->getClassFxEvents($this);

		foreach ($fx as $func) {
			$this->getEventHandlers($func)->add([$this, $func]);
		}

		if (is_a($this, IDynamicMethods::class)) {
			$this->attachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$this, '__dycall']);
			array_push($fx, TComponent::GLOBAL_RAISE_EVENT_LISTENER);
		}

		$this->_listeningenabled = true;

		$this->callBehaviorsMethod('dyListen', $return, $fx);

		return count($fx);
	}

	/**
	 * this removes an object's fx events from the global broadcaster
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyUnlisten($globalEvents[, ?TCallChain $chain = null]) {
	 * 		$this->behaviorUnlisten(); //eg
	 *      if ($chain)
	 *          $chain->dyUnlisten($globalEvents);
	 * }
	 * </code>
	 * to be executed when listen is called.  All attached behaviors are notified through dyUnlisten.
	 *
	 * @return numeric the number of global events that were unregistered from the global event registry
	 */
	public function unlisten()
	{
		if (!$this->_listeningenabled) {
			return;
		}

		$fx = $this->getClassFxEvents($this);

		foreach ($fx as $func) {
			$this->detachEventHandler($func, [$this, $func]);
		}

		if (is_a($this, IDynamicMethods::class)) {
			$this->detachEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER, [$this, '__dycall']);
			array_push($fx, TComponent::GLOBAL_RAISE_EVENT_LISTENER);
		}

		$this->_listeningenabled = false;

		$this->callBehaviorsMethod('dyUnlisten', $return, $fx);

		return count($fx);
	}

	/**
	 * Gets the state of listening to global events
	 * @return bool is Listening to global broadcast enabled
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
	 * @param string $method method name that doesn't exist and is being called on the object
	 * @param mixed $args method parameters
	 * @throws TInvalidOperationException If the property is not defined or read-only or
	 * 		method is undefined
	 * @return mixed result of the method call, or false if 'fx' or 'dy' function but
	 *		is not found in the class, otherwise it runs
	 */
	public function __call($method, $args)
	{
		$getset = substr($method, 0, 3);
		if (($getset == 'get') || ($getset == 'set')) {
			$propname = substr($method, 3);
			$jsmethod = $getset . 'js' . $propname;
			if (Prado::method_visible($this, $jsmethod)) {
				if (count($args) > 0) {
					if ($args[0] && !($args[0] instanceof TJavaScriptString)) {
						$args[0] = new TJavaScriptString($args[0]);
					}
				}
				return $this->$jsmethod(...$args);
			}

			if (($getset == 'set') && Prado::method_visible($this, 'getjs' . $propname)) {
				throw new TInvalidOperationException('component_property_readonly', $this::class, $method);
			}
		}
		if ($this->callBehaviorsMethod($method, $return, ...$args)) {
			return $return;
		}

		// don't throw an exception for __magicMethods() or any other weird methods natively implemented by php
		if (!method_exists($this, $method)) {
			throw new TApplicationException('component_method_undefined', $this::class, $method);
		}
	}


	/**
	 * Returns a property value or an event handler list by property or event name.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to read a property:
	 * <code>
	 * $value = $component->PropertyName;
	 * $value = $component->jsPropertyName; // return JavaScript literal
	 * </code>
	 * and to obtain the event handler list for an event,
	 * <code>
	 * $eventHandlerList = $component->EventName;
	 * </code>
	 * This will also return the global event handler list when specifing an 'fx'
	 * event,
	 * <code>
	 * $globalEventHandlerList = $component->fxEventName;
	 * </code>
	 * When behaviors are enabled, this will return the behavior of a specific
	 * name, a property of a behavior, or an object 'on' event defined by the behavior.
	 * @param string $name the property name or the event name
	 * @throws TInvalidOperationException if the property/event is not defined.
	 * @return mixed the property value or the event handler list as {@link TWeakCallableCollection}
	 */
	public function __get($name)
	{
		if (Prado::method_visible($this, $getter = 'get' . $name)) {
			// getting a property
			return $this->$getter();
		} elseif (Prado::method_visible($this, $jsgetter = 'getjs' . $name)) {
			// getting a javascript property
			return (string) $this->$jsgetter();
		} elseif (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
			// getting an event (handler list)
			$name = strtolower($name);
			if (!isset($this->_e[$name])) {
				$this->_e[$name] = new TWeakCallableCollection();
			}
			return $this->_e[$name];
		} elseif (strncasecmp($name, 'fx', 2) === 0) {
			// getting a global event (handler list)
			$name = strtolower($name);
			if (!isset(self::$_ue[$name])) {
				self::$_ue[$name] = new TWeakCallableCollection();
			}
			return self::$_ue[$name];
		} elseif ($this->getBehaviorsEnabled()) {
			// getting a behavior property/event (handler list)
			$name = strtolower($name);
			if (isset($this->_m[$name])) {
				return $this->_m[$name];
			} elseif ($this->_m !== null) {
				foreach ($this->_m->toArray() as $behavior) {
					if ($behavior->getEnabled() && (property_exists($behavior, $name) || $behavior->canGetProperty($name) || $behavior->hasEvent($name))) {
						return $behavior->$name;
					}
				}
			}
		}
		throw new TInvalidOperationException('component_property_undefined', $this::class, $name);
	}

	/**
	 * Sets value of a component property.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to set a property or attach an event handler.
	 * <code>
	 *    $this->PropertyName = $value;
	 *    $this->jsPropertyName = $value; // $value will be treated as a JavaScript literal
	 *    $this->EventName = $handler;
	 *    $this->fxEventName = $handler; //global event listener
	 *    $this->EventName = function($sender, $param) {...};
	 * </code>
	 * When behaviors are enabled, this will also set a behaviors properties and events.
	 * @param string $name the property name or event name
	 * @param mixed $value the property value or event handler
	 * @throws TInvalidOperationException If the property is not defined or read-only.
	 */
	public function __set($name, $value)
	{
		if (Prado::method_visible($this, $setter = 'set' . $name)) {
			if (strncasecmp($name, 'js', 2) === 0 && $value && !($value instanceof TJavaScriptLiteral)) {
				$value = new TJavaScriptLiteral($value);
			}
			return $this->$setter($value);
		} elseif (Prado::method_visible($this, $jssetter = 'setjs' . $name)) {
			if ($value && !($value instanceof TJavaScriptString)) {
				$value = new TJavaScriptString($value);
			}
			return $this->$jssetter($value);
		} elseif ((strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) || strncasecmp($name, 'fx', 2) === 0) {
			return $this->attachEventHandler($name, $value);
		} elseif ($this->_m !== null && $this->_m->getCount() > 0 && $this->getBehaviorsEnabled()) {
			$sets = 0;
			foreach ($this->_m->toArray() as $behavior) {
				if ($behavior->getEnabled() && (property_exists($behavior, $name) || $behavior->canSetProperty($name) || $behavior->hasEvent($name))) {
					$behavior->$name = $value;
					$sets++;
				}
			}
			if ($sets) {
				return $value;
			}
		}

		if (Prado::method_visible($this, 'get' . $name) || Prado::method_visible($this, 'getjs' . $name)) {
			throw new TInvalidOperationException('component_property_readonly', $this::class, $name);
		} else {
			throw new TInvalidOperationException('component_property_undefined', $this::class, $name);
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
	 * @param string $name the property name or the event name
	 * @since 3.2.3
	 */
	public function __isset($name)
	{
		if (Prado::method_visible($this, $getter = 'get' . $name)) {
			return $this->$getter() !== null;
		} elseif (Prado::method_visible($this, $jsgetter = 'getjs' . $name)) {
			return $this->$jsgetter() !== null;
		} elseif (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
			$name = strtolower($name);
			return isset($this->_e[$name]) && $this->_e[$name]->getCount();
		} elseif (strncasecmp($name, 'fx', 2) === 0) {
			$name = strtolower($name);
			return isset(self::$_ue[$name]) && self::$_ue[$name]->getCount();
		} elseif ($this->_m !== null && $this->_m->getCount() > 0 && $this->getBehaviorsEnabled()) {
			$name = strtolower($name);
			if (isset($this->_m[$name])) {
				return true;
			}
			foreach ($this->_m->toArray() as $behavior) {
				if ($behavior->getEnabled() && (property_exists($behavior, $name) || $behavior->canGetProperty($name) || $behavior->hasEvent($name))) {
					return isset($behavior->$name);
				}
			}
		}
		return false;
	}

	/**
	 * Sets a component property to be null.  Clears the object or global
	 * events. When enabled, loops through all behaviors and unsets the
	 * property or event.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using unset() to set a component property to be null.
	 * @param string $name the property name or the event name
	 * @throws TInvalidOperationException if the property is read only.
	 * @since 3.2.3
	 */
	public function __unset($name)
	{
		if (Prado::method_visible($this, $setter = 'set' . $name)) {
			$this->$setter(null);
		} elseif (Prado::method_visible($this, $jssetter = 'setjs' . $name)) {
			$this->$jssetter(null);
		} elseif (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
			$this->_e[strtolower($name)]->clear();
		} elseif (strncasecmp($name, 'fx', 2) === 0) {
			$this->getEventHandlers($name)->remove([$this, $name]);
		} elseif ($this->_m !== null && $this->_m->getCount() > 0 && $this->getBehaviorsEnabled()) {
			$name = strtolower($name);
			if (isset($this->_m[$name])) {
				$this->detachBehavior($name);
			} else {
				$unset = 0;
				foreach ($this->_m->toArray() as $behavior) {
					if ($behavior->getEnabled()) {
						unset($behavior->$name);
						$unset++;
					}
				}
				if (!$unset && Prado::method_visible($this, 'get' . $name)) {
					throw new TInvalidOperationException('component_property_readonly', $this::class, $name);
				}
			}
		} elseif (Prado::method_visible($this, 'get' . $name)) {
			throw new TInvalidOperationException('component_property_readonly', $this::class, $name);
		}
	}

	/**
	 * Determines whether a property is defined.
	 * A property is defined if there is a getter or setter method
	 * defined in the class. Note, property names are case-insensitive.
	 * @param string $name the property name
	 * @return bool whether the property is defined
	 */
	public function hasProperty($name)
	{
		return $this->canGetProperty($name) || $this->canSetProperty($name);
	}

	/**
	 * Determines whether a property can be read.
	 * A property can be read if the class has a getter method
	 * for the property name. Note, property name is case-insensitive.
	 * This also checks for getjs.  When enabled, it loops through all
	 * active behaviors for the get property when undefined by the object.
	 * @param string $name the property name
	 * @return bool whether the property can be read
	 */
	public function canGetProperty($name)
	{
		if (Prado::method_visible($this, 'get' . $name) || Prado::method_visible($this, 'getjs' . $name)) {
			return true;
		} elseif ($this->_m !== null && $this->getBehaviorsEnabled()) {
			foreach ($this->_m->toArray() as $behavior) {
				if ($behavior->getEnabled() && $behavior->canGetProperty($name)) {
					return true;
				}
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
	 * @param string $name the property name
	 * @return bool whether the property can be written
	 */
	public function canSetProperty($name)
	{
		if (Prado::method_visible($this, 'set' . $name) || Prado::method_visible($this, 'setjs' . $name)) {
			return true;
		} elseif ($this->_m !== null && $this->getBehaviorsEnabled()) {
			foreach ($this->_m->toArray() as $behavior) {
				if ($behavior->getEnabled() && $behavior->canSetProperty($name)) {
					return true;
				}
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
	 * @param string $path property path
	 * @return mixed the property path value
	 */
	public function getSubProperty($path)
	{
		$object = $this;
		foreach (explode('.', $path) as $property) {
			$object = $object->$property;
		}
		return $object;
	}

	/**
	 * Sets a value to a property path.
	 * A property path is a sequence of property names concatenated by '.' character.
	 * For example, 'Parent.Page' refers to the 'Page' property of the component's
	 * 'Parent' property value (which should be a component also).
	 * When a property is not defined by an object, this also loops through all
	 * active behaviors of the object.
	 * @param string $path property path
	 * @param mixed $value the property path value
	 */
	public function setSubProperty($path, $value)
	{
		$object = $this;
		if (($pos = strrpos($path, '.')) === false) {
			$property = $path;
		} else {
			$object = $this->getSubProperty(substr($path, 0, $pos));
			$property = substr($path, $pos + 1);
		}
		$object->$property = $value;
	}

	/**
	 * Calls a method on a component's behaviors.  When the method is a
	 * dynamic event, it is raised with all the behaviors.  When a class implements
	 * a dynamic event (eg. for patching), the class can customize raising the
	 * dynamic event with the classes behaviors using this method.
	 * Dynamic [dy] and global [fx] events call {@link __dycall} when $this
	 * implements IDynamicMethods.  Finally, this catches all unexecuted
	 * Dynamic [dy] and global [fx] events and returns the first $args parameter;
	 * acting as a passthrough (filter) of the first $args parameter. In dy/fx methods,
	 * there can be no $args parameters, the first parameter used as a pass through
	 * filter, or act as a return variable with the first $args parameter being
	 * the default return value.
	 * @param string $method The method being called or dynamic/global event being raised.
	 * @param mixed &$return The return value.
	 * @param array $args The arguments to the method being called.
	 * @return bool Was the method handled.
	 * @since 4.2.3
	 */
	public function callBehaviorsMethod($method, &$return, ...$args): bool
	{
		if ($this->_m !== null && $this->getBehaviorsEnabled()) {
			if (strncasecmp($method, 'dy', 2) === 0) {
				if ($callchain = $this->getCallChain($method, ...$args)) {
					$return = $callchain->call(...$args);
					return true;
				}
			} else {
				foreach ($this->_m->toArray() as $behavior) {
					if ($behavior->getEnabled() && Prado::method_visible($behavior, $method)) {
						if ($behavior instanceof IClassBehavior) {
							array_unshift($args, $this);
						}
						$return = $behavior->$method(...$args);
						return true;
					}
				}
			}
		}
		if (strncasecmp($method, 'dy', 2) === 0 || strncasecmp($method, 'fx', 2) === 0) {
			if ($this instanceof IDynamicMethods) {
				$return = $this->__dycall($method, $args);
				return true;
			}
			$return = $args[0] ?? null;
			return true;
		}
		return false;
	}

	/**
	 * This gets the chain of methods implemented by attached and enabled behaviors.
	 * This method disregards the {behaviorsEnabled
	 * @param string $method The name of the behaviors method being chained.
	 * @param array $args The arguments to the behaviors method being chained.
	 * @return ?TCallChain The chain of methods implemented by behaviors or null when
	 *   there are no methods to call.
	 * @since 4.2.3
	 */
	protected function getCallChain($method, ...$args): ?TCallChain
	{
		$classArgs = $callchain = null;
		foreach ($this->_m->toArray() as $behavior) {
			if ($behavior->getEnabled() && (Prado::method_visible($behavior, $method) || ($behavior instanceof IDynamicMethods))) {
				if ($classArgs === null) {
					$classArgs = $args;
					array_unshift($classArgs, $this);
				}
				if (!$callchain) {
					$callchain = new TCallChain($method);
				}
				$callchain->addCall([$behavior, $method], ($behavior instanceof IClassBehavior) ? $classArgs : $args);
			}
		}
		return $callchain;
	}

	/**
	 * Determines whether a method is defined. When behaviors are enabled, this
	 * will loop through all enabled behaviors checking for the method as well.
	 * Nested behaviors within behaviors are not supported but the nested behavior can
	 * affect the primary behavior like any behavior affects their owner.
	 * Note, method name are case-insensitive.
	 * @param string $name the method name
	 * @return bool
	 * @since 4.2.2
	 */
	public function hasMethod($name)
	{
		if (Prado::method_visible($this, $name) || strncasecmp($name, 'fx', 2) === 0 || strncasecmp($name, 'dy', 2) === 0) {
			return true;
		} elseif ($this->_m !== null && $this->getBehaviorsEnabled()) {
			foreach ($this->_m->toArray() as $behavior) {
				//Prado::method_visible($behavior, $name) rather than $behavior->hasMethod($name) b/c only one layer is supported, @4.2.2
				if ($behavior->getEnabled() && Prado::method_visible($behavior, $name)) {
					return true;
				}
			}
		}
		return false;
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
	 * @param string $name the event name
	 * @return bool
	 */
	public function hasEvent($name)
	{
		if ((strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) || strncasecmp($name, 'fx', 2) === 0 || strncasecmp($name, 'dy', 2) === 0) {
			return true;
		} elseif ($this->_m !== null && $this->getBehaviorsEnabled()) {
			foreach ($this->_m->toArray() as $behavior) {
				if ($behavior->getEnabled() && $behavior->hasEvent($name)) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Checks if an event has any handlers.  This function also checks through all
	 * the behaviors for 'on' events when behaviors are enabled.
	 * 'dy' dynamic events are not handled by this function.
	 * @param string $name the event name
	 * @return bool whether an event has been attached one or several handlers
	 */
	public function hasEventHandler($name)
	{
		$name = strtolower($name);
		if (strncasecmp($name, 'fx', 2) === 0) {
			return isset(self::$_ue[$name]) && self::$_ue[$name]->getCount() > 0;
		} else {
			if (isset($this->_e[$name]) && $this->_e[$name]->getCount() > 0) {
				return true;
			} elseif ($this->_m !== null && $this->getBehaviorsEnabled()) {
				foreach ($this->_m->toArray() as $behavior) {
					if ($behavior->getEnabled() && $behavior->hasEventHandler($name)) {
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Returns the list of attached event handlers for an 'on' or 'fx' event.   This function also
	 * checks through all the behaviors for 'on' event lists when behaviors are enabled.
	 * @param mixed $name
	 * @throws TInvalidOperationException if the event is not defined
	 * @return TWeakCallableCollection list of attached event handlers for an event
	 */
	public function getEventHandlers($name)
	{
		if (strncasecmp($name, 'on', 2) === 0 && method_exists($this, $name)) {
			$name = strtolower($name);
			if (!isset($this->_e[$name])) {
				$this->_e[$name] = new TWeakCallableCollection();
			}
			return $this->_e[$name];
		} elseif (strncasecmp($name, 'fx', 2) === 0) {
			$name = strtolower($name);
			if (!isset(self::$_ue[$name])) {
				self::$_ue[$name] = new TWeakCallableCollection();
			}
			return self::$_ue[$name];
		} elseif ($this->_m !== null && $this->getBehaviorsEnabled()) {
			foreach ($this->_m->toArray() as $behavior) {
				if ($behavior->getEnabled() && $behavior->hasEvent($name)) {
					return $behavior->getEventHandlers($name);
				}
			}
		}
		throw new TInvalidOperationException('component_event_undefined', $this::class, $name);
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
	 * {@link TWeakCallableCollection} operations to append, insert or remove
	 * event handlers. You may also do these operations like
	 * getting and setting properties, e.g.,
	 * <code>
	 *    $component->OnClick[] = array($object,'buttonClicked');
	 *    $component->OnClick->insertAt(0,array($object,'buttonClicked'));
	 *    $component->OnClick[] = function ($sender, $param) { ... };
	 * </code>
	 * which are equivalent to the following
	 * <code>
	 *    $component->getEventHandlers('OnClick')->add(array($object,'buttonClicked'));
	 *    $component->getEventHandlers('OnClick')->insertAt(0,array($object,'buttonClicked'));
	 * </code>
	 *
	 * Due to the nature of {@link getEventHandlers}, any active behaviors defining
	 * new 'on' events, this method will pass through to the behavior transparently.
	 *
	 * @param string $name the event name
	 * @param callable $handler the event handler
	 * @param null|numeric $priority the priority of the handler, defaults to null which translates into the
	 * default priority of 10.0 within {@link TWeakCallableCollection}
	 * @throws TInvalidOperationException if the event does not exist
	 */
	public function attachEventHandler($name, $handler, $priority = null)
	{
		$this->getEventHandlers($name)->add($handler, $priority);
	}

	/**
	 * Detaches an existing event handler.
	 * This method is the opposite of {@link attachEventHandler}.  It will detach
	 * any 'on' events defined by an objects active behaviors as well.
	 * @param string $name event name
	 * @param callable $handler the event handler to be removed
	 * @param null|false|numeric $priority the priority of the handler, defaults to false which translates
	 * to an item of any priority within {@link TWeakCallableCollection}; null means the default priority
	 * @return bool if the removal is successful
	 */
	public function detachEventHandler($name, $handler, $priority = false)
	{
		if ($this->hasEventHandler($name)) {
			try {
				$this->getEventHandlers($name)->remove($handler, $priority);
				return true;
			} catch (\Exception $e) {
			}
		}
		return false;
	}

	/**
	 * Raises an event.  This raises both inter-object 'on' events and global 'fx' events.
	 * This method represents the happening of an event and will
	 * invoke all attached event handlers for the event in {@link TWeakCallableCollection} order.
	 * This method does not handle intra-object/behavior dynamic 'dy' events.
	 *
	 * There are ways to handle event responses.  By default {@link EVENT_RESULT_FILTER},
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
	 * Behaviors may implement the following functions with TBehaviors:
	 * <code>
	 *	public function dyPreRaiseEvent($name, $sender, $param, $responsetype, $postfunction[, TCallChain $chain) {
	 *      ....  //  Your logic
	 *  	return $chain->dyPreRaiseEvent($name, $sender, $param, $responsetype, $postfunction); //eg, the event name may be filtered/changed
	 *  }
	 *	public function dyIntraRaiseEventTestHandler($handler, $sender, $param, $name, TCallChain $chain) {
	 *      ....  //  Your logic
	 *  	return $chain->dyIntraRaiseEventTestHandler($handler, $sender, $param, $name); //should this particular handler be executed?  true/false
	 *  }
	 *  public function dyIntraRaiseEventPostHandler($name, $sender, $param, $handler, $response, TCallChain $chain) {
	 *      ....  //  Your logic
	 *		return $chain->dyIntraRaiseEventPostHandler($name, $sender, $param, $handler, $response); //contains the per handler response
	 *  }
	 *  public function dyPostRaiseEvent($responses, $name, $sender, $param,$ responsetype, $postfunction, TCallChain $chain) {
	 *      ....  //  Your logic
	 *		return $chain->dyPostRaiseEvent($responses, $name, $sender, $param,$ responsetype, $postfunction);
	 *  }
	 * </code>
	 * to be executed when raiseEvent is called.  The 'intra' dynamic events are called per handler in
	 * the handler loop.  TClassBehaviors prepend the object being raised.
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
	 * In the rare circumstance that the event handlers need to be raised in reverse order, then
	 * specifying {@see TEventResults::EVENT_REVERSE} can be used to reverse the order of the
	 * handlers.
	 *
	 * @param string $name the event name
	 * @param mixed $sender the event sender object
	 * @param \Prado\TEventParameter $param the event parameter
	 * @param null|numeric $responsetype how the results of the event are tabulated.  default: {@link EVENT_RESULT_FILTER}  The default filters out
	 *		null responses. optional
	 * @param null|callable $postfunction any per handler filtering of the response result needed is passed through
	 *		this if not null. default: null.  optional
	 * @throws TInvalidOperationException if the event is undefined
	 * @throws TInvalidDataValueException If an event handler is invalid
	 * @return mixed the results of the event
	 */
	public function raiseEvent($name, $sender, $param, $responsetype = null, $postfunction = null)
	{
		$p = $param;
		if (is_callable($responsetype)) {
			$postfunction = $responsetype;
			$responsetype = null;
		}

		if ($responsetype === null) {
			$responsetype = TEventResults::EVENT_RESULT_FILTER;
		}

		$name = strtolower($name);
		$responses = [];

		if($param instanceof IEventParameter) {
			$param->setEventName($name);
		}

		$this->callBehaviorsMethod('dyPreRaiseEvent', $name, $name, $sender, $param, $responsetype, $postfunction);

		if ($this->hasEventHandler($name) || $this->hasEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER)) {
			$handlers = $this->getEventHandlers($name);
			$handlerArray = $handlers->toArray();
			if (strncasecmp($name, 'fx', 2) === 0 && $this->hasEventHandler(TComponent::GLOBAL_RAISE_EVENT_LISTENER)) {
				$globalhandlers = $this->getEventHandlers(TComponent::GLOBAL_RAISE_EVENT_LISTENER);
				$handlerArray = array_merge($globalhandlers->toArrayBelowPriority(0), $handlerArray, $globalhandlers->toArrayAbovePriority(0));
			}
			$response = null;
			if ($responsetype & TEventResults::EVENT_REVERSE) {
				$handlerArray = array_reverse($handlerArray);
			}
			foreach ($handlerArray as $handler) {
				$this->callBehaviorsMethod('dyIntraRaiseEventTestHandler', $return, $handler, $sender, $param, $name);
				if ($return === false) {
					continue;
				}

				if (is_string($handler)) {
					if (($pos = strrpos($handler, '.')) !== false) {
						$object = $this->getSubProperty(substr($handler, 0, $pos));
						$method = substr($handler, $pos + 1);
						if (Prado::method_visible($object, $method) || strncasecmp($method, 'dy', 2) === 0 || strncasecmp($method, 'fx', 2) === 0) {
							if ($method == '__dycall') {
								$response = $object->__dycall($name, [$sender, $param]);
							} else {
								$response = $object->$method($sender, $param);
							}
						} else {
							throw new TInvalidDataValueException('component_eventhandler_invalid', $this::class, $name, $handler);
						}
					} else {
						$response = call_user_func($handler, $sender, $param);
					}
				} elseif (is_callable($handler, true)) {
					if (is_object($handler) || is_string($handler[0])) {
						$response = call_user_func($handler, $sender, $param);
					} else {
						[$object, $method] = $handler;
						if (($pos = strrpos($method, '.')) !== false) {
							$object = $object->getSubProperty(substr($method, 0, $pos));
							$method = substr($method, $pos + 1);
						}
						if (Prado::method_visible($object, $method) || strncasecmp($method, 'dy', 2) === 0 || strncasecmp($method, 'fx', 2) === 0) {
							if ($method == '__dycall') {
								$response = $object->__dycall($name, [$sender, $param]);
							} else {
								$response = $object->$method($sender, $param);
							}
						} else {
							throw new TInvalidDataValueException('component_eventhandler_invalid', $this::class, $name, $handler[1]);
						}
					}
				} else {
					throw new TInvalidDataValueException('component_eventhandler_invalid', $this::class, $name, gettype($handler));
				}

				$this->callBehaviorsMethod('dyIntraRaiseEventPostHandler', $return, $name, $sender, $param, $handler, $response);

				if ($postfunction) {
					$response = call_user_func_array($postfunction, [$sender, $param, $this, $response]);
				}

				if ($responsetype & TEventResults::EVENT_RESULT_ALL) {
					$responses[] = ['sender' => $sender, 'param' => $param, 'response' => $response];
				} else {
					$responses[] = $response;
				}

				if ($response !== null && ($responsetype & TEventResults::EVENT_RESULT_FEED_FORWARD)) {
					$param = $response;
				}
			}
		} elseif (strncasecmp($name, 'on', 2) === 0 && !$this->hasEvent($name)) {
			throw new TInvalidOperationException('component_event_undefined', $this::class, $name);
		}

		if ($responsetype & TEventResults::EVENT_RESULT_FILTER) {
			$responses = array_filter($responses);
		}

		$this->callBehaviorsMethod('dyPostRaiseEvent', $responses, $responses, $name, $sender, $param, $responsetype, $postfunction);

		return $responses;
	}

	/**
	 * Evaluates a PHP expression in the context of this control.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyEvaluateExpressionFilter($expression, TCallChain $chain) {
	 * 		return $chain->dyEvaluateExpressionFilter(str_replace('foo', 'bar', $expression)); //example
	 * }
	 * </code>
	 * to be executed when evaluateExpression is called.  All attached behaviors are notified through
	 * dyEvaluateExpressionFilter.  The chaining is important in this function due to the filtering
	 * pass-through effect.
	 *
	 * @param string $expression PHP expression
	 * @throws TInvalidOperationException if the expression is invalid
	 * @return mixed the expression result
	 */
	public function evaluateExpression($expression)
	{
		$this->callBehaviorsMethod('dyEvaluateExpressionFilter', $expression, $expression);
		try {
			return eval("return $expression;");
		} catch (\Exception $e) {
			throw new TInvalidOperationException('component_expression_invalid', $this::class, $expression, $e->getMessage());
		}
	}

	/**
	 * Evaluates a list of PHP statements.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyEvaluateStatementsFilter($statements, TCallChain $chain) {
	 * 		return $chain->dyEvaluateStatementsFilter(str_replace('foo', 'bar', $statements)); //example
	 * }
	 * </code>
	 * to be executed when evaluateStatements is called.  All attached behaviors are notified through
	 * dyEvaluateStatementsFilter.  The chaining is important in this function due to the filtering
	 * pass-through effect.
	 *
	 * @param string $statements PHP statements
	 * @throws TInvalidOperationException if the statements are invalid
	 * @return string content echoed or printed by the PHP statements
	 */
	public function evaluateStatements($statements)
	{
		$this->callBehaviorsMethod('dyEvaluateStatementsFilter', $statements, $statements);
		try {
			ob_start();
			if (eval($statements) === false) {
				throw new \Exception('');
			}
			$content = ob_get_contents();
			ob_end_clean();
			return $content;
		} catch (\Exception $e) {
			throw new TInvalidOperationException('component_statements_invalid', $this::class, $statements, $e->getMessage());
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
	 *	public function dyCreatedOnTemplate($parent, TCallChain $chain) {
	 * 		return $chain->dyCreatedOnTemplate($parent); //example
	 *  }
	 * </code>
	 * to be executed when createdOnTemplate is called.  All attached behaviors are notified through
	 * dyCreatedOnTemplate.
	 *
	 * @param \Prado\TComponent $parent potential parent of this control
	 * @see addParsedObject
	 */
	public function createdOnTemplate($parent)
	{
		$this->callBehaviorsMethod('dyCreatedOnTemplate', $parent, $parent);
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
	 *	public function dyAddParsedObject($object, TCallChain $chain) {
	 *      return $chain-> dyAddParsedObject($object);
	 *  }
	 * </code>
	 * to be executed when addParsedObject is called.  All attached behaviors are notified through
	 * dyAddParsedObject.
	 *
	 * @param \Prado\TComponent|string $object text string or component parsed and instantiated in template
	 * @see createdOnTemplate
	 */
	public function addParsedObject($object)
	{
		$this->callBehaviorsMethod('dyAddParsedObject', $return, $object);
	}

	/**
	 *This is the method registered for all instanced objects should a class behavior be added after
	 * the class is instanced.  Only when the class to which the behavior is being added is in this
	 * object's class hierarchy, via {@link getClassHierarchy}, is the behavior added to this instance.
	 * @param mixed $sender the application
	 * @param TClassBehaviorEventParameter $param
	 * @since 3.2.3
	 */
	public function fxAttachClassBehavior($sender, $param)
	{
		if ($this->isa($param->getClass())) {
			if (($behavior = $param->getBehavior()) instanceof IBehavior) {
				$behavior = clone $behavior;
			}
			return $this->attachBehavior($param->getName(), $behavior, $param->getPriority());
		}
	}

	/**
	 *	This is the method registered for all instanced objects should a class behavior be removed after
	 * the class is instanced.  Only when the class to which the behavior is being added is in this
	 * object's class hierarchy, via {@link getClassHierarchy}, is the behavior removed from this instance.
	 * @param mixed $sender the application
	 * @param TClassBehaviorEventParameter $param
	 * @since 3.2.3
	 */
	public function fxDetachClassBehavior($sender, $param)
	{
		if ($this->isa($param->getClass())) {
			return $this->detachBehavior($param->getName(), $param->getPriority());
		}
	}

	/**
	 * instanceBehavior is an internal method that takes a Behavior Object, a class name, or array of
	 * ['class' => 'MyBehavior', 'property1' => 'Value1'...] and creates a Behavior in return. eg.
	 * <code>
	 *		$b = $this->instanceBehavior('MyBehavior');
	 * 		$b = $this->instanceBehavior(['class' => 'MyBehavior', 'property1' => 'Value1']);
	 * 		$b = $this->instanceBehavior(new MyBehavior);
	 * </code>
	 * If the behavior is an array, the key IBaseBehavior::CONFIG_KEY is stripped and used to initialize
	 * the behavior.
	 *
	 * @param array|IBaseBehavior|string $behavior string, Behavior, or array of ['class' => 'MyBehavior', 'property1' => 'Value1' ...].
	 * @throws TInvalidDataTypeException if the behavior is not an {@link IBaseBehavior}
	 * @return IBaseBehavior&TComponent an instance of $behavior or $behavior itself
	 * @since 4.2.0
	 */
	protected static function instanceBehavior($behavior)
	{
		$config = null;
		$isArray = false;
		$init = false;
		if (is_string($behavior) || (($isArray = is_array($behavior)) && isset($behavior['class']))) {
			if ($isArray && array_key_exists(IBaseBehavior::CONFIG_KEY, $behavior)) {
				$config = $behavior[IBaseBehavior::CONFIG_KEY];
				unset($behavior[IBaseBehavior::CONFIG_KEY]);
			}
			$behavior = Prado::createComponent($behavior);
			$init = true;
		}
		if (!($behavior instanceof IBaseBehavior)) {
			throw new TInvalidDataTypeException('component_not_a_behavior', $behavior::class);
		}
		if ($init) {
			$behavior->init($config);
		}
		return $behavior;
	}


	/**
	 *	This will add a class behavior to all classes instanced (that are listening) and future newly instanced objects.
	 * This registers the behavior for future instances and pushes the changes to all the instances that are listening as well.
	 * The universal class behaviors are stored in an inverted stack with the latest class behavior being at the first position in the array.
	 * This is done so class behaviors are added last first.
	 * @param string $name name the key of the class behavior
	 * @param object|string $behavior class behavior or name of the object behavior per instance
	 * @param null|array|IBaseBehavior|string $class string of class or class on which to attach this behavior.  Defaults to null which will error
	 *	but more important, if this is on PHP 5.3 it will use Late Static Binding to derive the class
	 * it should extend.
	 * <code>
	 *   TPanel::attachClassBehavior('javascripts', new TJsPanelClassBehavior());
	 *   TApplication::attachClassBehavior('jpegize', \Prado\Util\Behaviors\TJPEGizeAssetBehavior::class, \Prado\Web\TFileAsset::class);
	 * </code>
	 * An array is used to initialize values of the behavior. eg. ['class' => '\\MyBehavior', 'property' => 'value'].
	 * @param null|numeric $priority priority of behavior, default: null the default
	 *  priority of the {@link TWeakCallableCollection}  Optional.
	 * @throws TInvalidOperationException if the class behavior is being added to a
	 *  {@link TComponent}; due to recursion.
	 * @throws TInvalidOperationException if the class behavior is already defined
	 * @return array|object the behavior if its an IClassBehavior and an array of all
	 * behaviors that have been attached from 'fxAttachClassBehavior' when the Class
	 * Behavior being attached is a per instance IBaseBehavior.
	 * @since 3.2.3
	 */
	public static function attachClassBehavior($name, $behavior, $class = null, $priority = null)
	{
		if (!$class) {
			$class = get_called_class();
		}
		if (!$class) {
			throw new TInvalidOperationException('component_no_class_provided_nor_late_binding');
		}

		$class = strtolower($class);
		if ($class === strtolower(TComponent::class)) {
			throw new TInvalidOperationException('component_no_tcomponent_class_behaviors');
		}
		if (empty(self::$_um[$class])) {
			self::$_um[$class] = [];
		}
		$name = strtolower($name !== null ? $name : '');
		if (!empty($name) && !is_numeric($name) && isset(self::$_um[$class][$name])) {
			throw new TInvalidOperationException('component_class_behavior_defined', $class, $name);
		}
		$behaviorObject = self::instanceBehavior($behavior);
		$behaviorObject->setName($name);
		$isClassBehavior = $behaviorObject instanceof \Prado\Util\IClassBehavior;
		$param = new TClassBehaviorEventParameter($class, $name, $isClassBehavior ? $behaviorObject : $behavior, $priority);
		if (empty($name) || is_numeric($name)) {
			self::$_um[$class][] = $param;
		} else {
			self::$_um[$class] = [$name => $param] + self::$_um[$class];
		}
		$results = $behaviorObject->raiseEvent('fxAttachClassBehavior', null, $param);
		return $isClassBehavior ? $behaviorObject : $results;
	}

	/**
	 *	This will remove a behavior from a class.  It unregisters it from future instances and
	 * pulls the changes from all the instances that are listening as well.
	 * PHP 5.3 uses Late Static Binding to derive the static class upon which this method is called.
	 * @param string $name the key of the class behavior
	 * @param string $class class on which to attach this behavior.  Defaults to null.
	 * @param null|false|numeric $priority priority: false is any priority, null is default
	 *		{@link TWeakCallableCollection} priority, and numeric is a specific priority.
	 * @throws TInvalidOperationException if the the class cannot be derived from Late Static Binding and is not
	 * not supplied as a parameter.
	 * @return null|array|object the behavior if its an IClassBehavior and an array of all behaviors
	 * that have been detached from 'fxDetachClassBehavior' when the Class Behavior being
	 * attached is a per instance IBehavior.  Null if no behavior of $name to detach.
	 * @since 3.2.3
	 */
	public static function detachClassBehavior($name, $class = null, $priority = false)
	{
		if (!$class) {
			$class = get_called_class();
		}
		if (!$class) {
			throw new TInvalidOperationException('component_no_class_provided_nor_late_binding');
		}

		$class = strtolower($class);
		$name = strtolower($name);
		if (empty(self::$_um[$class]) || !isset(self::$_um[$class][$name])) {
			return null;
		}
		$param = self::$_um[$class][$name];
		$behavior = $param->getBehavior();
		$behaviorObject = self::instanceBehavior($behavior);
		$behaviorObject->setName($name);
		$isClassBehavior = $behaviorObject instanceof IClassBehavior;
		unset(self::$_um[$class][$name]);
		if(empty(self::$_um[$class])) {
			unset(self::$_um[$class]);
		}
		$results = $behaviorObject->raiseEvent('fxDetachClassBehavior', null, $param);
		return $isClassBehavior ? $behaviorObject : $results;
	}

	/**
	 * Returns the named behavior object.  If the $behaviorname is not found, but is
	 * an existing class or interface, this will return the first instanceof.
	 * The name 'asa' stands for 'as a'.
	 * @param string $behaviorname the behavior name or the class name of the behavior.
	 * @return object the behavior object of name or class, or null if the behavior does not exist
	 * @since 3.2.3
	 */
	public function asa($behaviorname)
	{
		$behaviorname = strtolower($behaviorname);
		if (isset($this->_m[$behaviorname])) {
			return $this->_m[$behaviorname];
		}
		if ((class_exists($behaviorname, false) || interface_exists($behaviorname, false)) && $this->_m) {
			foreach($this->_m->toArray() as $behavior) {
				if ($behavior instanceof $behaviorname) {
					return $behavior;
				}
			}
		}
		return null;
	}

	/**
	 * Returns whether or not the object or any of the behaviors are of a particular class.
	 * The name 'isa' stands for 'is a'.  This first checks if $this is an instanceof the class.
	 * Then it checks if the $class is in the hierarchy, which includes first level traits.
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
	 * @param mixed|string $class class or string
	 * @return bool whether or not the object or a behavior is an instance of a particular class
	 * @since 3.2.3
	 */
	public function isa($class)
	{
		if ($this instanceof $class || in_array(strtolower(is_object($class) ? $class::class : $class), $this->getClassHierarchy(true))) {
			return true;
		}
		if ($this->_m !== null && $this->getBehaviorsEnabled()) {
			foreach ($this->_m->toArray() as $behavior) {
				if (!$behavior->getEnabled()) {
					continue;
				}

				$check = null;
				if (($behavior->isa(\Prado\Util\IInstanceCheck::class)) && $check = $behavior->isinstanceof($class, $this)) {
					return true;
				}
				if ($check === null && ($behavior->isa($class))) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Returns all the behaviors attached to the TComponent.  IBaseBehavior[s] may
	 * be attached but not {@link IBaseBehavior::getEnabled Enabled}.
	 * @param ?string $class Filters the result by class, default null for no filter.
	 * @return array The behaviors [optionally filtered] attached to the TComponent.
	 * @since 4.2.2
	 */
	public function getBehaviors(?string $class = null)
	{
		if ($class === null) {
			return isset($this->_m) ? $this->_m->toArray() : [];
		} elseif (class_exists($class, false) || interface_exists($class, false)) {
			return array_filter($this->_m->toArray(), fn ($b) => $b instanceof $class);
		}
		return [];
	}

	/**
	 * Attaches a list of behaviors to the component.
	 * Each behavior is indexed by its name and should be an instance of
	 * {@link IBaseBehavior}, a string specifying the behavior class, or a
	 * {@link TClassBehaviorEventParameter}.
	 * @param array $behaviors list of behaviors to be attached to the component
	 * @param bool $cloneIBehavior Should IBehavior be cloned before attaching.
	 *   Default is false.
	 * @since 3.2.3
	 */
	public function attachBehaviors($behaviors, bool $cloneIBehavior = false)
	{
		foreach ($behaviors as $name => $behavior) {
			if ($behavior instanceof TClassBehaviorEventParameter) {
				$paramBehavior = $behavior->getBehavior();
				if ($cloneIBehavior && ($paramBehavior instanceof IBehavior)) {
					$paramBehavior = clone $paramBehavior;
				}
				$this->attachBehavior($behavior->getName(), $paramBehavior, $behavior->getPriority());
			} else {
				if ($cloneIBehavior && ($behavior instanceof IBehavior)) {
					$behavior = clone $behavior;
				}
				$this->attachBehavior($name, $behavior);
			}
		}
	}

	/**
	 * Detaches select behaviors from the component.
	 * Each behavior is indexed by its name and should be an instance of
	 * {@link IBaseBehavior}, a string specifying the behavior class, or a
	 * {@link TClassBehaviorEventParameter}.
	 * @param array $behaviors list of behaviors to be detached from the component
	 * @since 3.2.3
	 */
	public function detachBehaviors($behaviors)
	{
		if ($this->_m !== null) {
			foreach ($behaviors as $name => $behavior) {
				if ($behavior instanceof TClassBehaviorEventParameter) {
					$this->detachBehavior($behavior->getName(), $behavior->getPriority());
				} else {
					$this->detachBehavior(is_string($behavior) ? $behavior : $name);
				}
			}
		}
	}

	/**
	 * Detaches all behaviors from the component.
	 * @since 3.2.3
	 */
	public function clearBehaviors()
	{
		if ($this->_m !== null) {
			foreach ($this->_m->getKeys() as $name) {
				$this->detachBehavior($name);
			}
			$this->_m = null;
		}
	}

	/**
	 * Attaches a behavior to this component.
	 * This method will create the behavior object based on the given
	 * configuration. After that, the behavior object will be initialized
	 * by calling its {@link IBaseBehavior::attach} method.
	 *
	 * Already attached behaviors may implement the function:
	 * <code>
	 *	public function dyAttachBehavior($name,$behavior[, ?TCallChain $chain = null]) {
	 *      if ($chain)
	 *          return $chain->dyDetachBehavior($name, $behavior);
	 *  }
	 * </code>
	 * to be executed when attachBehavior is called.  All attached behaviors are notified through
	 * dyAttachBehavior.
	 *
	 * @param null|numeric|string $name the behavior's name. It should uniquely identify this behavior.
	 * @param array|IBaseBehavior|string $behavior the behavior configuration. This is the name of the Behavior Class
	 * instanced by {@link PradoBase::createComponent}, or is a Behavior, or is an array of
	 * ['class'=>'TBehavior' property1='value 1' property2='value2'...] with the class and properties
	 * with values.
	 * @param null|numeric $priority
	 * @return IBaseBehavior the behavior object
	 * @since 3.2.3
	 */
	public function attachBehavior($name, $behavior, $priority = null)
	{
		$name = strtolower($name !== null ? $name : '');
		if ($this->_m && isset($this->_m[$name])) {
			$this->detachBehavior($name);
		}
		$behavior = self::instanceBehavior($behavior);
		if ($this->_m === null) {
			$this->_m = new TPriorityMap();
		}
		if (empty($name) || is_numeric($name)) {
			$name = $this->_m->getNextIntegerKey();
		}
		$this->_m->add($name, $behavior, $priority);
		$behavior->setName($name);
		$behavior->attach($this);
		$this->callBehaviorsMethod('dyAttachBehavior', $return, $name, $behavior);
		return $behavior;
	}

	/**
	 * Detaches a behavior from the component.
	 * The behavior's {@link IBaseBehavior::detach} method will be invoked.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyDetachBehavior($name, $behavior[, ?TCallChain $chain = null]) {
	 *      if ($chain)
	 *          return $chain->dyDetachBehavior($name, $behavior);
	 *  }
	 * </code>
	 * to be executed when detachBehavior is called.  All attached behaviors are notified through
	 * dyDetachBehavior.
	 *
	 * @param string $name the behavior's name. It uniquely identifies the behavior.
	 * @param false|numeric $priority the behavior's priority. This defaults to false, which is any priority.
	 * @return null|IBaseBehavior the detached behavior. Null if the behavior does not exist.
	 * @since 3.2.3
	 */
	public function detachBehavior($name, $priority = false)
	{
		$name = strtolower($name);
		if ($this->_m != null && ($behavior = $this->_m->itemAt($name, $priority))) {
			$this->callBehaviorsMethod('dyDetachBehavior', $return, $name, $behavior);
			$behavior->detach($this);
			$this->_m->remove($name, $priority);
			return $behavior;
		}
		return null;
	}

	/**
	 * Enables all behaviors attached to this component independent of the behaviors
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyEnableBehaviors([?TCallChain $chain = null]) {
	 *      if ($chain)
	 *          return $chain->dyEnableBehaviors();
	 *  }
	 * </code>
	 * to be executed when enableBehaviors is called.  All attached behaviors are notified through
	 * dyEnableBehaviors.
	 * @since 3.2.3
	 */
	public function enableBehaviors()
	{
		if (!$this->_behaviorsenabled) {
			$this->_behaviorsenabled = true;
			$this->callBehaviorsMethod('dyEnableBehaviors', $return);
		}
	}

	/**
	 * Disables all behaviors attached to this component independent of the behaviors
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyDisableBehaviors([?TCallChain $chain = null]) {
	 *      if ($chain)
	 *          return $chain->dyDisableBehaviors();
	 *  }
	 * </code>
	 * to be executed when disableBehaviors is called.  All attached behaviors are notified through
	 * dyDisableBehaviors.
	 * @since 3.2.3
	 */
	public function disableBehaviors()
	{
		if ($this->_behaviorsenabled) {
			$callchain = $this->getCallChain('dyDisableBehaviors');
			$this->_behaviorsenabled = false;
			if ($callchain) { // normal dynamic events won't work because behaviors are disabled.
				$callchain->call();
			}
		}
	}


	/**
	 * Returns if all the behaviors are turned on or off for the object.
	 * @return bool whether or not all behaviors are enabled (true) or not (false)
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
	 *	public function dyEnableBehavior($name, $behavior[, ?TCallChain $chain = null]) {
	 *      if ($chain)
	 *          return $chain->dyEnableBehavior($name, $behavior);
	 *  }
	 * </code>
	 * to be executed when enableBehavior is called.  All attached behaviors are notified through
	 * dyEnableBehavior.
	 *
	 * @param string $name the behavior's name. It uniquely identifies the behavior.
	 * @return bool Was the behavior found and enabled.
	 * @since 3.2.3
	 */
	public function enableBehavior($name): bool
	{
		$name = strtolower($name);
		if ($this->_m != null && isset($this->_m[$name])) {
			$behavior = $this->_m[$name];
			if ($behavior->getEnabled() === false) {
				$behavior->setEnabled(true);
				$this->callBehaviorsMethod('dyEnableBehavior', $return, $name, $behavior);
			}
			return true;
		}
		return false;
	}

	/**
	 * Disables an attached behavior.  This cannot enable or disable whole class behaviors.
	 * A behavior is only effective when it is enabled.
	 *
	 * Behaviors may implement the function:
	 * <code>
	 *	public function dyDisableBehavior($name, $behavior[, ?TCallChain $chain = null]) {
	 *      if ($chain)
	 *          return $chain->dyDisableBehavior($name, $behavior);
	 *  }
	 * </code>
	 * to be executed when disableBehavior is called.  All attached behaviors are notified through
	 * dyDisableBehavior.
	 *
	 * @param string $name the behavior's name. It uniquely identifies the behavior.
	 * @return bool Was the behavior found and disabled.
	 * @since 3.2.3
	 */
	public function disableBehavior($name): bool
	{
		$name = strtolower($name);
		if ($this->_m != null && isset($this->_m[$name])) {
			$behavior = $this->_m[$name];
			if ($behavior->getEnabled() === true) {
				$behavior->setEnabled(false);
				$this->callBehaviorsMethod('dyDisableBehavior', $return, $name, $behavior);
			}
			return true;
		}
		return false;
	}

	/**
	 * Returns an array with the names of all variables of that object that should be serialized.
	 * Do not call this method. This is a PHP magic method that will be called automatically
	 * prior to any serialization.
	 */
	public function __sleep()
	{
		$a = (array) $this;
		$a = array_keys($a);
		$exprops = [];
		$this->_getZappableSleepProps($exprops);
		return array_diff($a, $exprops);
	}

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 * @param array $exprops by reference
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		if ($this->_listeningenabled === false) {
			$exprops[] = "\0*\0_listeningenabled";
		}
		if ($this->_behaviorsenabled === true) {
			$exprops[] = "\0*\0_behaviorsenabled";
		}
		$exprops[] = "\0*\0_e";
		if ($this->_m === null) {
			$exprops[] = "\0*\0_m";
		}
	}
}
