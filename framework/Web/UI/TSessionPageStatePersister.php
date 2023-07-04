<?php
/**
 * TSessionPageStatePersister class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\TPropertyValue;
use Prado\Exceptions\THttpException;
use Prado\Exceptions\TInvalidDataValueException;

/**
 * TSessionPageStatePersister class
 *
 * TSessionPageStatePersister implements a page state persistent method based on
 * sessions. Page state are stored in user sessions and therefore, this persister
 * requires session to be started and available.
 *
 * TSessionPageStatePersister keeps limited number of history states in session,
 * mainly to preserve the precious server storage. The number is specified
 * by {@see setHistorySize HistorySize}, which defaults to 10.
 *
 * There are a couple of ways to use TSessionPageStatePersister.
 * One can override the page's {@see \Prado\Web\UI\TPage::getStatePersister()} method and
 * create a TSessionPageStatePersister instance there.
 * Or one can configure the pages to use TSessionPageStatePersister in page configurations
 * as follows,
 * ```xml
 *   <pages StatePersisterClass="Prado\Web\UI\TSessionPageStatePersister" />
 * ```
 * The above configuration will affect the pages under the directory containing
 * this configuration and all its subdirectories.
 * To configure individual pages to use TSessionPageStatePersister, use
 * ```xml
 *   <pages>
 *     <page id="PageID" StatePersisterClass="Prado\Web\UI\TSessionPageStatePersister" />
 *   </pages>
 * ```
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.1
 */
class TSessionPageStatePersister extends \Prado\TComponent implements IPageStatePersister
{
	public const STATE_SESSION_KEY = 'PRADO_SESSION_PAGESTATE';
	public const QUEUE_SESSION_KEY = 'PRADO_SESSION_STATEQUEUE';

	private $_page;
	private $_historySize = 10;

	/**
	 * @return TPage the page that this persister works for
	 */
	public function getPage()
	{
		return $this->_page;
	}

	/**
	 * @param TPage $page the page that this persister works for.
	 */
	public function setPage(TPage $page)
	{
		$this->_page = $page;
	}

	/**
	 * @return int maximum number of page states that should be kept in session. Defaults to 10.
	 */
	public function getHistorySize()
	{
		return $this->_historySize;
	}

	/**
	 * @param int $value maximum number of page states that should be kept in session
	 * @throws TInvalidDataValueException if the number is smaller than 1.
	 */
	public function setHistorySize($value)
	{
		if (($value = TPropertyValue::ensureInteger($value)) > 0) {
			$this->_historySize = $value;
		} else {
			throw new TInvalidDataValueException('sessionpagestatepersister_historysize_invalid');
		}
	}
	/**
	 * Saves state in session.
	 * @param mixed $state state to be stored
	 */
	public function save($state)
	{
		$session = $this->_page->getSession();
		$session->open();
		$data = serialize($state);
		$timestamp = (string) microtime(true);
		$key = self::STATE_SESSION_KEY . $timestamp;
		$session->add($key, $data);
		if (($queue = $session->itemAt(self::QUEUE_SESSION_KEY)) === null) {
			$queue = [];
		}
		$queue[] = $key;
		if (count($queue) > $this->getHistorySize()) {
			$expiredKey = array_shift($queue);
			$session->remove($expiredKey);
		}
		$session->add(self::QUEUE_SESSION_KEY, $queue);
		$this->_page->setClientState(TPageStateFormatter::serialize($this->_page, $timestamp));
	}

	/**
	 * Loads page state from session.
	 * @throws THttpException if page state is corrupted
	 * @return mixed the restored state
	 */
	public function load()
	{
		if (($timestamp = TPageStateFormatter::unserialize($this->_page, $this->_page->getRequestClientState())) !== null) {
			$session = $this->_page->getSession();
			$session->open();
			$key = self::STATE_SESSION_KEY . $timestamp;
			if (($data = $session->itemAt($key)) !== null) {
				return unserialize($data);
			}
		}
		throw new THttpException(400, 'sessionpagestatepersister_pagestate_corrupted');
	}
}
