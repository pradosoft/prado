<?php
/**
 * TClassBehavior class file.
 *
 * @author Brad Anderson <javalizard@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Util;

/**
 * TClassBehavior is a convenient base class for whole class behaviors.
 * @author Brad Anderson <javalizard@gmail.com>
 * @since 3.2.3
 */
class TClassBehavior extends \Prado\TComponent implements IClassBehavior
{
	/**
	 * This processes configuration elements [from TBehaviorsModule].  This is usually
	 * called before attach but cannot be guaranteed to be called outside the {@link
	 * TBehaviorsModule} environment. This is only needed for complex behavior
	 * configurations.
	 * @param array|\Prado\Xml\TXmlElement $config any innards to the behavior configuration.
	 */
	public function init($config)
	{
	}

	/**
	 * Attaches the behavior object to the component.
	 * @param \Prado\TComponent $component the component that this behavior is to be attached to.
	 */
	public function attach($component)
	{
	}

	/**
	 * Detaches the behavior object from the component.
	 * @param \Prado\TComponent $component the component that this behavior is to be detached from.
	 */
	public function detach($component)
	{
	}
}
