<?php
/**
 * TPage class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @copyright Copyright &copy; 2005-2016 The PRADO Group
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web\UI
 */

namespace Prado\Web\UI;

/**
 * IPageStatePersister interface.
 *
 * IPageStatePersister interface is required for all page state persister
 * classes.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web\UI
 * @since 3.1
 */
interface IPageStatePersister
{
	/**
	 * @param TPage the page that this persister works for
	 */
	public function getPage();
	/**
	 * @param TPage the page that this persister works for
	 */
	public function setPage(TPage $page);
	/**
	 * Saves state to persistent storage.
	 * @param mixed $state state to be stored
	 */
	public function save($state);
	/**
	 * Loads page state from persistent storage
	 * @return mixed the restored state
	 */
	public function load();
}
