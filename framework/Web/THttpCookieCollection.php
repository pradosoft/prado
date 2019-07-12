<?php
/**
 * THttpRequest, THttpCookie, THttpCookieCollection, TUri class file
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link https://github.com/pradosoft/prado
 * @license https://github.com/pradosoft/prado/blob/master/LICENSE
 * @package Prado\Web
 */

namespace Prado\Web;

use Prado\Exceptions\TInvalidDataTypeException;

/**
 * THttpCookieCollection class.
 *
 * THttpCookieCollection implements a collection class to store cookies.
 * Besides using all functionalities from {@link TList}, you can also
 * retrieve a cookie by its name using either {@link findCookieByName} or
 * simply:
 * <code>
 *   $cookie=$collection[$cookieName];
 * </code>
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @package Prado\Web
 * @since 3.0
 */
class THttpCookieCollection extends \Prado\Collections\TList
{
	/**
	 * @var mixed owner of this collection
	 */
	private $_o;

	/**
	 * Constructor.
	 * @param mixed $owner owner of this collection.
	 */
	public function __construct($owner = null)
	{
		$this->_o = $owner;
	}

	/**
	 * Inserts an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * operations for each newly added THttpCookie object.
	 * @param int $index the specified position.
	 * @param mixed $item new item
	 * @throws TInvalidDataTypeException if the item to be inserted is not a THttpCookie object.
	 */
	public function insertAt($index, $item)
	{
		if ($item instanceof THttpCookie) {
			parent::insertAt($index, $item);
			if ($this->_o instanceof THttpResponse) {
				$this->_o->addCookie($item);
			}
		} else {
			throw new TInvalidDataTypeException('httpcookiecollection_httpcookie_required');
		}
	}

	/**
	 * Removes an item at the specified position.
	 * This overrides the parent implementation by performing additional
	 * cleanup work when removing a TCookie object.
	 * @param int $index the index of the item to be removed.
	 * @return mixed the removed item.
	 */
	public function removeAt($index)
	{
		$item = parent::removeAt($index);
		if ($this->_o instanceof THttpResponse) {
			$this->_o->removeCookie($item);
		}
		return $item;
	}

	/**
	 * @param int|string $index index of the cookie in the collection or the cookie's name
	 * @return THttpCookie the cookie found
	 */
	public function itemAt($index)
	{
		if (is_int($index)) {
			return parent::itemAt($index);
		} else {
			return $this->findCookieByName($index);
		}
	}

	/**
	 * Finds the cookie with the specified name.
	 * @param string $name the name of the cookie to be looked for
	 * @return THttpCookie the cookie, null if not found
	 */
	public function findCookieByName($name)
	{
		foreach ($this as $cookie) {
			if ($cookie->getName() === $name) {
				return $cookie;
			}
		}
		return null;
	}
}
