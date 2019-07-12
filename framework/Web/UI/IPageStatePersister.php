<?php
/**
 * TPage class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
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
	 * @return TPage the page that this persister works for
	 */
	public function getPage();
	/**
	 * @param TPage $page the page that this persister works for
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
