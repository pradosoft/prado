<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado;

/**
 * IStatePersister class.
 *
 * This interface must be implemented by all state persister classes (such as
 * {@see \Prado\Web\UI\TPageStatePersister}, {@see \Prado\TApplicationStatePersister}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
interface IStatePersister
{
	/**
	 * Loads state from a persistent storage.
	 * @return mixed the state
	 */
	public function load();
	/**
	 * Saves state into a persistent storage.
	 * @param mixed $state the state to be saved
	 */
	public function save($state);
}
