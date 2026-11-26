<?php

/**
 * TTestPageStatePersister class file.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Test\Unit\Harness\Web\UI;

use Prado\TComponent;
use Prado\Web\UI\IPageStatePersister;
use Prado\Web\UI\TPage;

/**
 * TTestPageStatePersister keeps the page state of the last saved page in {@see $state}
 * and loads it for the next page. The client state of the saved page is {@see CLIENT_STATE}.
 *
 * @author Brad Anderson <belisoful@icloud.com>
 * @since 4.4.0
 */
class TTestPageStatePersister extends TComponent implements IPageStatePersister
{
	/**
	 * The client state of a saved page, which a callback response sends as its page state.
	 */
	public const CLIENT_STATE = 'state';

	/**
	 * @var mixed the last saved page state; a test resets it to null
	 */
	public static mixed $state = null;

	/**
	 * @var ?TPage the page
	 */
	private ?TPage $_page = null;

	/**
	 * @return ?TPage the page
	 */
	public function getPage()
	{
		return $this->_page;
	}

	/**
	 * @param TPage $page the page
	 */
	public function setPage(TPage $page)
	{
		$this->_page = $page;
	}

	/**
	 * Keeps the page state and sets the client state of the page to {@see CLIENT_STATE}.
	 * @param mixed $state the page state to keep
	 */
	public function save($state)
	{
		self::$state = $state;
		$this->_page?->setClientState(self::CLIENT_STATE);
	}

	/**
	 * @return mixed the last saved page state
	 */
	public function load()
	{
		return self::$state;
	}
}
