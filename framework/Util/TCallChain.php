<?php
/**
 * TCallChain class file.
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

use Prado\Collections\TList;

/**
 * TCallChain is a recursive event calling mechanism.  This class implements
 * the {@link IDynamicMethods} class so that any 'dy' event calls can be caught
 * and patched through to the intended recipient
 * @author Brad Anderson <javalizard@gmail.com>
 * @package Prado\Util
 * @since 3.2.3
 */
class TCallChain extends TList implements IDynamicMethods
{
	/**
	 *	@var {@link ArrayIterator} for moving through the chained method calls
	 */
	private $_iterator;

	/**
	 *	@var string the method name of the call chain
	 */
	private $_method;

	/**
	 * This initializes the list and the name of the method to be called
	 *	@param string $method the name of the function call
	 */
	public function __construct($method)
	{
		$this->_method = $method;
		parent::__construct();
	}


	/**
	 * This initializes the list and the name of the method to be called
	 * @param array|string $method this is a callable function as a string or array with
	 *	the object and method name as string
	 * @param array $args The array of arguments to the function call chain
	 */
	public function addCall($method, $args)
	{
		$this->add([$method, $args]);
	}

	/**
	 * This method calls the next Callable in the list.  All of the method arguments
	 * coming into this method are substituted into the original method argument of
	 * call in the chain.
	 *
	 * If the original method call has these parameters
	 * <code>
	 * $originalobject->dyExampleMethod('param1', 'param2', 'param3')
	 * </code>
	 * <code>
	 * $callchain->dyExampleMethod('alt1', 'alt2')
	 * </code>
	 * then the next call in the call chain will recieve the parameters as if this were called
	 * <code>
	 * $behavior->dyExampleMethod('alt1', 'alt2', 'param3', $callchainobject)
	 * </code>
	 *
	 * When dealing with {@link IClassBehaviors}, the first parameter of the stored argument
	 * list in 'dy' event calls is always the object containing the behavior.  This modifies
	 * the parameter replacement mechanism slightly to leave the object containing the behavior
	 * alone and only replacing the other parameters in the argument list.  As per {@link __call},
	 * any calls to a 'dy' event do not need the object containing the behavior as the addition of
	 * the object to the argument list as the first element is automatic for IClassBehaviors.
	 *
	 * The last parameter of the method parameter list for any callable in the call chain
	 * will be the TCallChain object itself.  This is so that any behavior implementing
	 * these calls will have access to the call chain.  Each callable should either call
	 * the TCallChain call method internally for direct chaining or call the method being
	 * chained (in which case the dynamic handler will pass through to this call method).
	 *
	 * If the dynamic intra object/behavior event is not called in the behavior implemented
	 * dynamic method, it will return to this method and call the following behavior
	 * implementation so as no behavior with an implementation of the dynamic event is left
	 * uncalled.  This does break the call chain though and will not act as a "parameter filter".
	 *
	 * When there are no handlers or no handlers left, it returns the first parameter of the
	 * argument list.
	 *
	 */
	public function call()
	{
		$args = func_get_args();
		if ($this->getCount() === 0) {
			return $args[0] ?? null;
		}

		if (!$this->_iterator) {
			$chain_array = array_reverse($this->toArray());
			$this->_iterator = new \ArrayIterator($chain_array);
		}
		if ($this->_iterator->valid()) {
			do {
				$handler = $this->_iterator->current();
				$this->_iterator->next();
				if (is_array($handler[0]) && $handler[0][0] instanceof IClassBehavior) {
					array_splice($handler[1], 1, count($args), $args);
				} else {
					array_splice($handler[1], 0, count($args), $args);
				}
				$handler[1][] = $this;
				$result = call_user_func_array($handler[0], $handler[1]);
			} while ($this->_iterator->valid());
		} else {
			$result = $args[0];
		}
		return $result;
	}


	/**
	 * This catches all the unpatched dynamic events.  When the method call matches the
	 * call chain method, it passes the arguments to the original __call (of the dynamic
	 * event being unspecified in TCallChain) and funnels into the method {@link call},
	 * so the next dynamic event handler can be called.
	 * If the original method call has these parameters
	 * <code>
	 * $originalobject->dyExampleMethod('param1', 'param2', 'param3')
	 * </code>
	 * and within the chained dynamic events, this can be called
	 * <code>
	 * class DyBehavior extends TBehavior {
	 * public function dyExampleMethod($param1, $param2, $param3, $callchain)
	 * $callchain->dyExampleMethod($param1, $param2, $param3)
	 * }
	 * {
	 * </code>
	 * to call the next event in the chain.
	 * @param string $method method name of the unspecified object method
	 * @param array $args arguments to the unspecified object method
	 */
	public function __dycall($method, $args)
	{
		if ($this->_method == $method) {
			return call_user_func_array([$this, 'call'], $args);
		}
		return null;
	}
}
