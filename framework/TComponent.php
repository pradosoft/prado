<?php
/**
 * TComponent, TPropertyValue classes
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @version $Revision: $  $Date: $
 * @package System
 */

/**
 * TComponent class
 *
 * TComponent is the base class for all PRADO components.
 * TComponent implements the protocol of defining, using properties and events.
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
 * An event is defined by the presence of a method whose name is the event name prefixed with 'on'.
 * The event name is case-insensitive.
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
 * $component->raiseEvent('Click');
 * </code>
 * To attach an event handler to an event, use one of the following ways,
 * <code>
 * $component->Click=$callback;  // or $component->Click->add($callback);
 * $$component->attachEventHandler('Click',$callback);
 * </code>
 * The first two ways make use of the fact that $component->Click refers to
 * the event handler list {@link TList} for the 'Click' event.
 * The variable $callback contains the definition of the event handler that can
 * be either a string referring to a global function name, or an array whose
 * first element refers to an object and second element a method name/path that
 * is reachable by the object, e.g.
 * - 'buttonClicked' : buttonClicked($sender,$param);
 * - array($object,'buttonClicked') : $object->buttonClicked($sender,$param);
 * - array($object,'MainContent.SubmitButton.buttonClicked') :
 *   $object->MainContent->SubmitButton->buttonClicked($sender,$param);
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
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
	 * Constructor.
	 */
	public function __construct()
	{
	}

	/**
	 * Destructor.
	 */
	public function __destruct()
	{
	}

	/**
	 * Returns a property value or an event handler list by property or event name.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to read a property:
	 * <code>
	 * $value=$component->PropertyName;
	 * </code>
	 * and to obtain the event handler list for an event,
	 * <code>
	 * $eventHandlerList=$component->EventName;
	 * </code>
	 * @param string the property name or the event name
	 * @return mixed the property value or the event handler list
	 * @throws TInvalidOperationException if the property/event is not defined.
	 */
	public function __get($name)
	{
		$getter='get'.$name;
		if(method_exists($this,$getter))
		{
			// getting a property
			return $this->$getter();
		}
		else if(method_exists($this,'on'.$name))
		{
			// getting an event (handler list)
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new TList;
			return $this->_e[$name];
		}
		else
		{
			throw new TInvalidOperationException('component_property_undefined',get_class($this),$name);
		}
	}

	/**
	 * Sets value of a component property.
	 * Do not call this method. This is a PHP magic method that we override
	 * to allow using the following syntax to set a property or attach an event handler.
	 * <code>
	 * $this->PropertyName=$value;
	 * $this->EventName=$handler;
	 * </code>
	 * @param string the property name or event name
	 * @param mixed the property value or event handler
	 * @throws TInvalidOperationException If the property is not defined or read-only.
	 */
	public function __set($name,$value)
	{
		$setter='set'.$name;
		if(method_exists($this,$setter))
		{
			$this->$setter($value);
		}
		else if(method_exists($this,'on'.$name))
		{
			$this->attachEventHandler($name,$value);
		}
		else if(method_exists($this,'get'.$name))
		{
			throw new TInvalidOperationException('component_property_readonly',get_class($this),$name);
		}
		else
		{
			throw new TInvalidOperationException('component_property_undefined',get_class($this),$name);
		}
	}

	/**
	 * Determines whether a property is defined.
	 * A property is defined if there is a getter or setter method
	 * defined in the class. Note, property names are case-insensitive.
	 * @param string the property name
	 * @return boolean whether the property is defined
	 */
	final public function hasProperty($name)
	{
		return method_exists($this,'get'.$name) || method_exists($this,'set'.$name);
	}

	/**
	 * Determines whether a property can be read.
	 * A property can be read if the class has a getter method
	 * for the property name. Note, property name is case-insensitive.
	 * @param string the property name
	 * @return boolean whether the property can be read
	 */
	final public function canGetProperty($name)
	{
		return method_exists($this,'get'.$name);
	}

	/**
	 * Determines whether a property can be set.
	 * A property can be written if the class has a setter method
	 * for the property name. Note, property name is case-insensitive.
	 * @param string the property name
	 * @return boolean whether the property can be written
	 */
	final public function canSetProperty($name)
	{
		return method_exists($this,'set'.$name);
	}

	/**
	 * Evaluates a property path.
	 * A property path is a sequence of property names concatenated by '.' character.
	 * For example, 'Parent.Page' refers to the 'Page' property of the component's
	 * 'Parent' property value (which should be a component also).
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
	 * An event is defined if the class has a method whose name is the event name prefixed with 'on'.
	 * Note, event name is case-insensitive.
	 * @param string the event name
	 * @return boolean
	 */
	public function hasEvent($name)
	{
		return method_exists($this,'on'.$name);
	}

	/**
	 * @return boolean whether an event has been attached one or several handlers
	 */
	public function hasEventHandler($name)
	{
		$name=strtolower($name);
		return isset($this->_e[$name]) && $this->_e[$name]->getCount()>0;
	}

	/**
	 * Returns the list of attached event handlers for an event.
	 * @return TList list of attached event handlers for an event
	 * @throws TInvalidOperationException if the event is not defined
	 */
	public function getEventHandlers($name)
	{
		if(method_exists($this,'on'.$name))
		{
			$name=strtolower($name);
			if(!isset($this->_e[$name]))
				$this->_e[$name]=new TList;
			return $this->_e[$name];
		}
		else
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
	 * function handlerName($sender,$param) {}
	 * </code>
	 * where $sender represents the object that raises the event,
	 * and $param is the event parameter.
	 *
	 * This is a convenient method to add an event handler.
	 * It is equivalent to {@link getEventHandlers}($name)->add($handler).
	 * For complete management of event handlers, use {@link getEventHandlers}
	 * to get the event handler list first, and then do various
	 * {@link TList} operations to append, insert or remove
	 * event handlers. You may also do these operations like
	 * getting and setting properties, e.g.,
	 * <code>
	 * $component->Click[]=array($object,'buttonClicked');
	 * $component->Click->addAt(0,array($object,'buttonClicked'));
	 * </code>
	 * which are equivalent to the following
	 * <code>
	 * $component->getEventHandlers('Click')->add(array($object,'buttonClicked'));
	 * $component->getEventHandlers('Click')->addAt(0,array($object,'buttonClicked'));
	 * </code>
	 *
	 * @param string the event name
	 * @param callback the event handler
	 * @throws TInvalidOperationException if the event does not exist
	 */
	public function attachEventHandler($name,$handler)
	{
		$this->getEventHandlers($name)->add($handler);
	}

	/**
	 * Raises an event.
	 * This method represents the happening of an event and will
	 * invoke all attached event handlers for the event.
	 * @param string the event name
	 * @param mixed the event sender object
	 * @param TEventParameter the event parameter
	 * @throws TInvalidOperationException if the event is undefined
	 * @throws TInvalidDataValueException If an event handler is invalid
	 */
	public function raiseEvent($name,$sender,$param)
	{
		$name=strtolower($name);
		if(isset($this->_e[$name]))
		{
			foreach($this->_e[$name] as $handler)
			{
				if(is_string($handler))
				{
					call_user_func($handler,$sender,$param);
				}
				else if(is_callable($handler,true))
				{
					// an array: 0 - object, 1 - method name/path
					list($object,$method)=$handler;
					if(is_string($object))	// static method call
						call_user_func($handler,$sender,$param);
					else
					{
						if(($pos=strrpos($method,'.'))!==false)
						{
							$object=$this->getSubProperty(substr($method,0,$pos));
							$method=substr($method,$pos+1);
						}
						$object->$method($sender,$param);
					}
				}
				else
					throw new TInvalidDataValueException('component_eventhandler_invalid',get_class($this),$name);
			}
		}
		else if(!$this->hasEvent($name))
			throw new TInvalidOperationException('component_event_undefined',get_class($this),$name);
	}

	/**
	 * Evaluates a PHP expression in the context of this control.
	 * @return mixed the expression result
	 * @throws TInvalidOperationException if the expression is invalid
	 */
	public function evaluateExpression($expression)
	{
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
	 * @param string PHP statements
	 * @return string content echoed or printed by the PHP statements
	 * @throw TInvalidOperationException if the statements are invalid
	 */
	public function evaluateStatements($statements)
	{
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
 * @version $Revision: $  $Date: $
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
			$trimmed = trim($value);
			$len = strlen($value);
			if ($len >= 2 && $trimmed[0] == '(' && $trimmed[$len-1] == ')')
			{
				eval('$array=array'.$trimmed.';');
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
	 * Note, enumeration values are case-sensitive strings.
	 * @param mixed the value to be converted.
	 * @param array array of strings representing the enum type.
	 * @return string
	 * @throws TInvalidDataValueException if the original value is not in the string array.
	 */
	public static function ensureEnum($value,$enum)
	{
		if(($index=array_search($value,$enum))!==false)
			return $enum[$index];
		else
			throw new TInvalidDataValueException('propertyvalue_enumvalue_invalid',$value,implode(' | ',$enum));
	}
}

/**
 * TEventParameter class.
 * TEventParameter is the base class for all event parameter classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Revision: $  $Date: $
 * @package System
 * @since 3.0
 */
class TEventParameter extends TComponent
{
}

?>