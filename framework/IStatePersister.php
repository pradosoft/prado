<?php
/**
 * Core interfaces essential for TApplication class.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.pradosoft.com/
 * @copyright Copyright &copy; 2005-2014 PradoSoft
 * @license http://www.pradosoft.com/license/
 * @package Prado
 */

namespace Prado;

/**
 * IStatePersister class.
 *
 * This interface must be implemented by all state persister classes (such as
 * {@link TPageStatePersister}, {@link TApplicationStatePersister}.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado
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
	 * @param mixed the state to be saved
	 */
	public function save($state);
}