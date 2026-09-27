<?php

/**
 * TPageStatePersister class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 */

namespace Prado\Web\UI;

use Prado\IO\Compression\ICompressionConfigurable;
use Prado\IO\Compression\TCompressionConfig;
use Prado\IO\Compression\TCompressionConfigTrait;
use Prado\Exceptions\THttpException;

/**
 * TPageStatePersister class
 *
 * TPageStatePersister implements a page state persistent method based on
 * form hidden fields.
 *
 * Since page state can be very big for complex pages, consider using
 * alternative persisters, such as {@see \Prado\Web\UI\TSessionPageStatePersister},
 * which store page state on the server side and thus reduce the network
 * traffic for transmitting bulky page state.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 3.0
 */
class TPageStatePersister extends \Prado\TComponent implements IPageStatePersister, ICompressionConfigurable
{
	use TCompressionConfigTrait;

	private $_page;

	/**
	 * Returns the compression settings this persister starts from: page state compresses
	 * by default, under the `deflate` coding and whatever its length.
	 * @return TCompressionConfig a new compression configuration.
	 * @since 4.4.0
	 */
	protected function newCompression(): TCompressionConfig
	{
		return new TPageStateCompressionConfig();
	}

	/**
	 * @return TPage the page that this persister works for
	 */
	public function getPage()
	{
		return $this->_page;
	}

	/**
	 * @param TPage $page the page that this persister works for
	 */
	public function setPage(TPage $page)
	{
		$this->_page = $page;
	}

	/**
	 * Saves state in hidden fields.
	 * @param mixed $state state to be stored
	 */
	public function save($state)
	{
		$this->_page->setClientState(TPageStateFormatter::serialize($this->_page, $state));
	}

	/**
	 * Loads page state from hidden fields.
	 * @throws THttpException if page state is corrupted
	 * @return mixed the restored state
	 */
	public function load()
	{
		if (($data = TPageStateFormatter::unserialize($this->_page, $this->_page->getRequestClientState())) !== null) {
			return $data;
		} else {
			throw new THttpException(400, 'pagestatepersister_pagestate_corrupted');
		}
	}
}
