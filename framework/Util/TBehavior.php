<?php
/**
 * TBehavior class file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @license http://www.yiiframework.com/license/
 */

namespace Prado\Util;

use Prado\TPropertyValue;

/**
 * TBehavior is a convenient base class for behavior classes.
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.2.3
 */
class TBehavior extends \Prado\TComponent implements IBehavior
{
	private $_enabled = true;
	private $_owner;

	/**
	 * This processes configuration elements from TBehaviorsModule.  This is usually
	 * called after attach but cannot be guaranteed to be called outside the {@link
	 * TBehaviorsModule} environment. This is only needed for complex behavior
	 * configurations.
	 * @param array|\Prado\Xml\TXmlElement $config any innards to the behavior configuration.
	 */
	public function init($config)
	{
	}

	/**
	 * Declares events and the corresponding event handler methods.
	 * The events are defined by the {@link owner} component, while the handler
	 * methods by the behavior class. The handlers will be attached to the corresponding
	 * events when the behavior is attached to the {@link owner} component; and they
	 * will be detached from the events when the behavior is detached from the component.
	 * @return array events (array keys) and the corresponding event handler methods (array values).
	 */
	public function events()
	{
		return [];
	}

	/**
	 * Attaches the behavior object to the component.
	 * The default implementation will set the {@link owner} property
	 * and attach event handlers as declared in {@link events}.
	 * Make sure you call the parent implementation if you override this method.
	 * @param \Prado\TComponent $owner the component that this behavior is to be attached to.
	 */
	public function attach($owner)
	{
		$this->_owner = $owner;
		foreach ($this->events() as $event => $handler) {
			$owner->attachEventHandler($event, [$this, $handler]);
		}
	}

	/**
	 * Detaches the behavior object from the component.
	 * The default implementation will unset the {@link owner} property
	 * and detach event handlers declared in {@link events}.
	 * Make sure you call the parent implementation if you override this method.
	 * @param \Prado\TComponent $owner the component that this behavior is to be detached from.
	 */
	public function detach($owner)
	{
		foreach ($this->events() as $event => $handler) {
			$owner->detachEventHandler($event, [$this, $handler]);
		}
		$this->_owner = null;
	}

	/**
	 * @return object the owner component that this behavior is attached to.
	 */
	public function getOwner()
	{
		return $this->_owner;
	}

	/**
	 * @return bool whether this behavior is enabled
	 */
	public function getEnabled()
	{
		return $this->_enabled;
	}

	/**
	 * @param bool $value whether this behavior is enabled
	 */
	public function setEnabled($value)
	{
		$this->_enabled = TPropertyValue::ensureBoolean($value);
	}

	/**
	 * This resets the Owner on cloning.
	 * @since 4.2.3
	 */
	public function __clone()
	{
		$this->_owner = null;
		parent::__clone();
	}

	/**
	 * Returns an array with the names of all variables of this object that should NOT be serialized
	 * because their value is the default one or useless to be cached for the next page loads.
	 * Reimplement in derived classes to add new variables, but remember to  also to call the parent
	 * implementation first.
	 * @param array $exprops by reference
	 * @since 4.2.3
	 */
	protected function _getZappableSleepProps(&$exprops)
	{
		parent::_getZappableSleepProps($exprops);
		if ($this->_enabled === true) {
			$exprops[] = "\0Prado\\Util\\TBehavior\0_enabled";
		}
		$exprops[] = "\0Prado\\Util\\TBehavior\0_owner";
	}
}
